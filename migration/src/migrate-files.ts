/**
 * Migrare fișiere `admin/statistici/uploads/` -> Vercel Blob (+ metadata în Neon).
 *
 * Sursa: bundle-ul de la admin/sync-export.php (SYNC_URL) — lista de fișiere;
 *        binarul se ia de la `<SYNC_URL fără query>?token=...&file=<nume>`.
 * Ținta: `event_reports.blob_url`/`original_name` (rapoarte XLSX) și rânduri noi
 *        în `event_files` (file_type='viza'), plus re-extragerea seriilor în
 *        `viza_subtips` cu parserul portat din web/lib/rapoarte.ts.
 *
 * Rulează:  npm run migrate-files            (DRY-RUN, nu scrie nimic)
 *           npm run migrate-files -- --apply (urcă în Blob + scrie în Neon)
 *
 * Re-rulabil: sare peste rapoartele care au deja blob_url și peste cursurile
 * care au deja un event_files de tip 'viza' (pe live e maxim unul per curs).
 *
 * ORDINE: rulează DUPĂ `npm run migrate`. O re-rulare a lui migrate.ts golește
 * event_files și pune blob_url pe NULL -> re-rulează și scriptul ăsta după.
 */
import { readFileSync, existsSync } from "node:fs";
import { Client } from "pg";
import { put } from "@vercel/blob";
// Parserul de vize e cel din portul Next (o singură sursă de adevăr).
// tsx rezolvă `xlsx`/`pdfjs-dist` din web/node_modules, deci merge fără copie locală.
import { parseVizaSubtips, pdfToText } from "../../web/lib/rapoarte.ts";

// --- încărcare .env minimală (identică cu migrate.ts) ---
function loadEnv(): void {
  if (!existsSync(".env")) return;
  for (const line of readFileSync(".env", "utf8").split("\n")) {
    const m = line.match(/^\s*([A-Z_][A-Z0-9_]*)\s*=\s*(.*)\s*$/);
    if (m && process.env[m[1]] === undefined) process.env[m[1]] = m[2].replace(/^["']|["']$/g, "");
  }
}
loadEnv();

const APPLY = process.argv.includes("--apply");
const BUCHAREST = "Europe/Bucharest";

interface StatCourse { id: number; name: string; date: string; external_id?: string | null }
interface Bundle {
  statistici?: { courses?: StatCourse[] } | null;
  [key: string]: unknown;
}

/** Un fișier din uploads, normalizat. */
interface UploadFile {
  name: string;             // numele de pe disc (ce se cere endpoint-ului)
  size: number;
  courseId: number | null;  // id-ul din clp.sqlite (courses.id)
  kind: "raport" | "viza";
  originalName: string;
  uploadedAt: string | null;
}

async function loadBundle(): Promise<Bundle> {
  const file = process.env.LIVE_FILE;
  if (file) return JSON.parse(readFileSync(file, "utf8"));
  const url = process.env.SYNC_URL;
  if (!url) throw new Error("Setează SYNC_URL sau LIVE_FILE în .env");
  const res = await fetch(url, { headers: { "User-Agent": "Mozilla/5.0" } });
  if (!res.ok) throw new Error(`sync-export ${res.status} — verifică tokenul din admin → Config`);
  return (await res.json()) as Bundle;
}

/** `<SYNC_URL fără query>?token=...&file=<nume>` */
function fileUrl(name: string): string {
  const raw = process.env.SYNC_URL;
  if (!raw) throw new Error("Descărcarea fișierelor cere SYNC_URL în .env (LIVE_FILE n-are binarele)");
  const u = new URL(raw);
  const token = u.searchParams.get("token") ?? "";
  return `${u.origin}${u.pathname}?token=${encodeURIComponent(token)}&file=${encodeURIComponent(name)}`;
}

const str = (o: Record<string, unknown>, ...keys: string[]): string => {
  for (const k of keys) if (o[k] != null && o[k] !== "") return String(o[k]);
  return "";
};

/**
 * Lista fișierelor din bundle — `stats_uploads_list` (nume, size, kind, course_id,
 * original_name, uploaded_at). Celelalte chei și fallback-ul pe formă sunt plasă de
 * siguranță dacă sync-export.php redenumește cheia. `uploads_list` (imaginile din
 * assets/images/uploads) e exclusă explicit — aia se migrează separat.
 */
function extractFiles(bundle: Bundle): UploadFile[] {
  const KEYS = [
    "stats_uploads_list", "statistici_files", "statistici_uploads", "stats_files", "stats_uploads",
    "course_files_list", "course_files", "uploads_statistici", "files_list", "files",
  ];
  const containers: Record<string, unknown>[] = [bundle as Record<string, unknown>];
  if (bundle.statistici && typeof bundle.statistici === "object") {
    containers.push(bundle.statistici as Record<string, unknown>);
  }

  let raw: unknown;
  for (const c of containers) {
    for (const k of KEYS) {
      if (Array.isArray(c[k])) { raw = c[k]; break; }
    }
    if (raw) break;
  }
  // fallback: orice listă de obiecte cu nume de fișier pdf/xlsx (numele cheii se poate schimba)
  if (!raw) {
    for (const c of containers) {
      for (const [k, v] of Object.entries(c)) {
        if (k === "uploads_list" || !Array.isArray(v)) continue;
        const first = v[0] as Record<string, unknown> | undefined;
        if (first && typeof first === "object" && /\.(pdf|xlsx?)$/i.test(str(first, "name", "filename", "file"))) {
          raw = v;
          break;
        }
      }
      if (raw) break;
    }
  }
  if (!raw) return [];

  const out: UploadFile[] = [];
  for (const item of raw as Record<string, unknown>[]) {
    if (!item || typeof item !== "object") continue;
    const name = str(item, "name", "filename", "file", "fname");
    if (!name) continue;

    // `kind` din bundle: viza | raport | viza_debug (text brut) | orphan (fără rând în SQLite)
    const typeHint = str(item, "kind", "file_type", "type").toLowerCase();
    if (typeHint === "orphan" || typeHint.includes("debug")) continue;

    // extensia decide tipul (pe live vizele sunt .pdf, rapoartele .xlsx/.xls)
    const ext = (name.split(".").pop() ?? "").toLowerCase();
    const kind: UploadFile["kind"] | null =
      ext === "pdf" ? "viza" : ext === "xlsx" || ext === "xls" ? "raport" : null;
    if (!kind) continue; // viza_debug_*.txt & co.

    // course_id explicit din bundle; altfel din convenția de nume a PHP-ului:
    //   <hex20>-raport-<courseId>.xlsx  /  <hex20>-<courseId>.pdf
    const idRaw = str(item, "course_id", "courseId", "curs_id", "cid", "event_id");
    const fromName = name.match(/-(?:raport-)?(\d+)\.(?:pdf|xlsx?)$/i);
    const courseId = Number(idRaw) || Number(fromName?.[1]) || null;

    out.push({
      name,
      size: Number(str(item, "size", "filesize", "bytes")) || 0,
      courseId,
      kind,
      originalName: str(item, "original_name", "originalName", "orig") || name,
      uploadedAt: str(item, "uploaded_at", "uploadedAt", "mtime") || null,
    });
  }
  return out;
}

/** clp.sqlite courses.id -> events.id (external_id, altfel titlu + zi, ca în migrate.ts) */
async function mapCourses(db: Client, courses: StatCourse[]): Promise<Map<number, number>> {
  const map = new Map<number, number>();
  for (const c of courses) {
    let rows: { id: number }[] = [];
    if (c.external_id) {
      rows = (await db.query("SELECT id FROM events WHERE external_id = $1", [c.external_id])).rows;
    }
    if (!rows.length) {
      rows = (
        await db.query(
          `SELECT id FROM events
           WHERE title = $1 AND (starts_at AT TIME ZONE $3)::date = $2::date`,
          [c.name, c.date, BUCHAREST]
        )
      ).rows;
    }
    if (rows.length === 1) map.set(c.id, Number(rows[0].id));
    else if (rows.length > 1) console.warn(`  ! curs ambiguu (${rows.length} events): #${c.id} ${c.name} ${c.date}`);
  }
  return map;
}

const blobPath = (f: UploadFile, eventId: number): string => {
  const ts = f.uploadedAt ? Date.parse(f.uploadedAt.replace(" ", "T")) : NaN;
  const stamp = Number.isNaN(ts) ? Date.now() : ts;
  const safe = f.originalName.replace(/[/\\]/g, "-");
  return `${f.kind === "raport" ? "rapoarte" : "viza"}/${eventId}-${stamp}-${safe}`;
};

async function download(f: UploadFile): Promise<Buffer> {
  const res = await fetch(fileUrl(f.name), { headers: { "User-Agent": "Mozilla/5.0" } });
  if (!res.ok) throw new Error(`download ${res.status}`);
  const buf = Buffer.from(await res.arrayBuffer());
  if (!buf.length) throw new Error("fișier gol");
  return buf;
}

/** Aceeași regulă ca saveVizaSubtips din portul Next: rescrie doar dacă a ieșit text. */
async function saveVizaSubtips(db: Client, eventId: number, text: string): Promise<number> {
  if (!text.trim()) return 0;
  const subtips = parseVizaSubtips(text);
  await db.query("DELETE FROM viza_subtips WHERE event_id = $1", [eventId]);
  for (const s of subtips) {
    await db.query(
      `INSERT INTO viza_subtips (event_id, seria, tarif, nr_unitati, de_la, pana_la)
       VALUES ($1,$2,$3,$4,$5,$6)`,
      [eventId, s.seria, s.tarif, s.nr_unitati, s.de_la, s.pana_la]
    );
  }
  return subtips.length;
}

async function main(): Promise<void> {
  if (!process.env.DATABASE_URL) throw new Error("Setează DATABASE_URL în .env");
  if (APPLY && !process.env.BLOB_READ_WRITE_TOKEN) {
    throw new Error(
      "Setează BLOB_READ_WRITE_TOKEN în migration/.env sau în mediu " +
        "(valoarea e cea din web/.env.local, adusă cu `vercel env pull`)"
    );
  }

  const bundle = await loadBundle();
  const files = extractFiles(bundle);
  if (!files.length) {
    console.log("Bundle-ul nu conține lista de fișiere din admin/statistici/uploads/ — nimic de făcut.");
    console.log("(sync-export.php trebuie să o expună; scriptul acceptă chei de tip statistici_files/course_files/files.)");
    return;
  }

  const db = new Client({ connectionString: process.env.DATABASE_URL });
  await db.connect();

  const stats = { uploaded: 0, skipped: 0, unmatched: 0, failed: 0, subtips: 0 };
  try {
    const courseMap = await mapCourses(db, bundle.statistici?.courses ?? []);
    console.log(`${files.length} fișiere în listă; ${courseMap.size} cursuri mapate pe events.`);
    console.log(APPLY ? "MOD: --apply (se scrie în Blob și în Neon)\n" : "MOD: DRY-RUN (nimic nu se scrie; adaugă --apply)\n");

    for (const f of files) {
      const eventId = f.courseId ? courseMap.get(f.courseId) : undefined;
      if (!eventId) {
        console.warn(`  ? ${f.name} — fără curs în Neon (course_id=${f.courseId ?? "?"})`);
        stats.unmatched++;
        continue;
      }

      // idempotență: ce e deja migrat se sare
      if (f.kind === "raport") {
        const { rows } = await db.query("SELECT blob_url FROM event_reports WHERE event_id = $1", [eventId]);
        if (!rows.length) {
          console.warn(`  ? ${f.name} — event ${eventId} n-are rând în event_reports`);
          stats.unmatched++;
          continue;
        }
        if (rows[0].blob_url) { stats.skipped++; continue; }
      } else {
        const { rows } = await db.query(
          "SELECT 1 FROM event_files WHERE event_id = $1 AND file_type = 'viza' LIMIT 1",
          [eventId]
        );
        if (rows.length) { stats.skipped++; continue; }
      }

      const target = blobPath(f, eventId);
      if (!APPLY) {
        console.log(`  → ${f.kind.padEnd(6)} event ${String(eventId).padStart(4)}  ${f.name} → ${target}`);
        stats.uploaded++;
        continue;
      }

      try {
        const bytes = await download(f);
        const { url } = await put(target, bytes, {
          access: "public",
          addRandomSuffix: false,
          allowOverwrite: true,
          token: process.env.BLOB_READ_WRITE_TOKEN,
        });

        if (f.kind === "raport") {
          await db.query(
            `UPDATE event_reports
             SET blob_url = $1,
                 original_name = CASE WHEN $2 <> '' THEN $2 ELSE original_name END
             WHERE event_id = $3`,
            [url, f.originalName, eventId]
          );
          console.log(`  ✓ raport  event ${eventId}  ${f.originalName}`);
        } else {
          await db.query(
            `INSERT INTO event_files (event_id, blob_url, original_name, file_type, uploaded_at)
             VALUES ($1,$2,$3,'viza', coalesce($4::timestamp AT TIME ZONE $5, now()))`,
            [eventId, url, f.originalName, f.uploadedAt, BUCHAREST]
          );
          let text = "";
          try {
            text = await pdfToText(bytes);
          } catch {
            // fără text din PDF, seriile existente rămân neatinse (ca în PHP)
          }
          const n = await saveVizaSubtips(db, eventId, text);
          stats.subtips += n;
          console.log(`  ✓ viza    event ${eventId}  ${f.originalName}  (${n} serii)`);
        }
        stats.uploaded++;
      } catch (e) {
        console.error(`  ✗ ${f.name}: ${e instanceof Error ? e.message : e}`);
        stats.failed++;
      }
    }
  } finally {
    await db.end();
  }

  console.log(`\n${APPLY ? "✓ Gata" : "DRY-RUN"}: ${stats.uploaded} ${APPLY ? "urcate" : "de urcat"}, ` +
    `${stats.skipped} deja migrate, ${stats.unmatched} fără curs, ${stats.failed} eșuate` +
    (APPLY ? `, ${stats.subtips} serii extrase` : ""));
  if (stats.failed) process.exit(1);
}

main().catch((e) => {
  console.error("✗ Migrare fișiere eșuată:", e instanceof Error ? e.message : e);
  process.exit(1);
});
