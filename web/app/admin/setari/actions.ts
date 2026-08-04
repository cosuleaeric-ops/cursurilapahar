"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import bcrypt from "bcryptjs";
import { randomBytes } from "node:crypto";
import { sql } from "@/lib/db";
import { getSession, type Session } from "@/lib/auth";
import { citesteMod } from "@/lib/checkout";
import { startPayment, diagnostic, verificaNotificare, statusPlata, PLATIT } from "@/lib/netopia";
import { confirmaComanda } from "@/lib/comenzi";
import { trimiteEmailComanda } from "@/lib/trimite";

async function requireOwner(): Promise<Session> {
  const s = await getSession();
  if (!s) redirect("/login");
  if (s.role !== "owner") redirect("/admin");
  return s;
}

const g = (fd: FormData, k: string) => String(fd.get(k) ?? "").trim();

async function setSetting(key: string, value: unknown): Promise<void> {
  await sql`
    INSERT INTO settings (key, value) VALUES (${key}, ${JSON.stringify(value)})
    ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = now()
  `;
}

export async function saveQuickLinks(formData: FormData): Promise<void> {
  await requireOwner();
  const icons = formData.getAll("ql_icon").map(String);
  const labels = formData.getAll("ql_label").map(String);
  const urls = formData.getAll("ql_url").map(String);
  const links = [];
  for (let i = 0; i < labels.length; i++) {
    const label = labels[i].trim();
    const url = (urls[i] ?? "").trim();
    // PHP (actions.php:317): 🔗 e fallback doar dacă indexul lipsește din POST; câmpul golit rămâne gol
    if (label && url) links.push({ label, url, icon: (icons[i] ?? "🔗").trim() });
  }
  await setSetting("quick_links", links);
  revalidatePath("/admin");
  redirect("/admin/setari?saved=1");
}

export async function saveKit(formData: FormData): Promise<void> {
  await requireOwner();
  await setSetting("kit_api_key", g(formData, "kit_api_key"));
  await setSetting("kit_form_id", g(formData, "kit_form_id"));
  redirect("/admin/setari?saved=1");
}

export async function saveBrevo(formData: FormData): Promise<void> {
  await requireOwner();
  await setSetting("brevo_api_key", g(formData, "brevo_api_key"));
  redirect("/admin/setari?saved=1");
}

export async function saveMetaAds(formData: FormData): Promise<void> {
  await requireOwner();
  await setSetting("meta_ads_token", g(formData, "meta_ads_token"));
  redirect("/admin/setari?saved=1");
}

export async function saveCheckout(formData: FormData): Promise<void> {
  await requireOwner();
  const mod = citesteMod(g(formData, "checkout_propriu"));

  // Fără cheia publică nu putem verifica notificările Netopia, deci nicio plată
  // nu s-ar confirma: clientul ar plăti și n-ar primi biletul. Mai bine nu se
  // aprinde deloc decât să se aprindă rupt.
  if (mod === "on") {
    const lipsa = (["NETOPIA_API_KEY", "NETOPIA_SIGNATURE", "NETOPIA_PUBLIC_KEY"] as const).filter(
      (k) => !(process.env[k] ?? "").trim(),
    );
    if (lipsa.length) redirect(`/admin/setari?cfg=${encodeURIComponent(lipsa.join(", "))}`);
  }

  await setSetting("checkout_propriu", mod);
  // Coșul și pagina de curs se randează la cerere, dar layout-ul public ține
  // setările memoizate — golim cache-ul ca schimbarea să se vadă imediat.
  revalidatePath("/", "layout");
  redirect("/admin/setari?saved=1");
}

/**
 * Cere Netopiei o plată de probă și raportează ce a răspuns. Nu creează comandă
 * și nu rezervă bilete — doar arată dacă cheia și adresa sunt bune, fără să
 * treci prin tot coșul.
 */
export async function testeazaNetopia(): Promise<void> {
  await requireOwner();
  const cod = `TEST-${randomBytes(4).toString("hex").toUpperCase()}`;
  const r = await startPayment({
    cod,
    suma: 1,
    descriere: "Test conexiune",
    nume: "Test Test",
    email: "test@cursurilapahar.ro",
    redirectUrl: "https://cursurilapahar.ro/cos/plata",
    notifyUrl: "https://cursurilapahar.ro/api/netopia/confirm",
  });
  const mesaj = r.ok ? `✅ Conexiune reușită - Netopia a dat pagina de plată (${r.ntpID})` : `❌ ${r.mesaj}`;
  // Formele cheilor intră în jurnal odată cu rezultatul: la un refuz, prima
  // întrebare e mereu „ce e de fapt în variabile", iar din afară nu se poate citi.
  const d = await diagnostic();
  const forme = `[apiKey ${d.apiKey}c, semnătură ${d.semnatura}c, cheie publică ${d.felCheie} ${d.cheieLungime}c]`;
  await sql`
    INSERT INTO webhook_log (ok, motiv, cod)
    VALUES (${r.ok}, ${`test conexiune: ${mesaj} ${forme}`.slice(0, 300)}, ${cod})
  `;
  redirect(`/admin/setari?net=${encodeURIComponent(mesaj)}`);
}

/**
 * Reia ultima notificare respinsă și o verifică din nou cu cheia publică de
 * acum. Așa se poate proba o cheie nouă fără să mai treacă cineva printr-o
 * plată: notificarea e păstrată exact cum a venit.
 */
export async function verificaUltimaNotificare(): Promise<void> {
  await requireOwner();
  const [r] = (await sql`
    SELECT cod, token, corp FROM webhook_log
    WHERE token IS NOT NULL AND corp IS NOT NULL ORDER BY id DESC LIMIT 1
  `) as { cod: string | null; token: string; corp: string }[];

  if (!r) redirect(`/admin/setari?net=${encodeURIComponent("Nu am nicio notificare păstrată de reverificat.")}`);

  const v = await verificaNotificare(r.corp, r.token);
  const d = await diagnostic();
  const mesaj = v.ok
    ? `✅ Semnătura notificării ${r.cod} se verifică cu cheia de acum (${d.felCheie})`
    : `❌ ${r.cod}: ${v.motiv} — cheia de acum e ${d.felCheie}`;
  // Și în jurnal, nu doar pe ecran: altfel rezultatul se pierde odată cu pagina.
  await sql`
    INSERT INTO webhook_log (ok, motiv, cod)
    VALUES (${v.ok}, ${`reverificare: ${mesaj}`.slice(0, 300)}, ${r.cod})
  `;
  redirect(`/admin/setari?net=${encodeURIComponent(mesaj)}`);
}

/**
 * Întreabă Netopia despre fiecare comandă rămasă neconfirmată și le închide pe
 * cele plătite. Plasa de siguranță pentru notificările pierdute: banii au intrat,
 * dar noi n-am aflat.
 */
export async function recupereazaComenzi(): Promise<void> {
  await requireOwner();
  const comenzi = (await sql`
    SELECT id, cod, ntp_id FROM orders
    WHERE status IN ('noua', 'esuata') AND ntp_id IS NOT NULL AND ntp_id <> ''
      AND created_at > now() - interval '7 days'
    ORDER BY id
  `) as { id: number; cod: string; ntp_id: string }[];

  let platite = 0;
  let emise = 0;
  const note: string[] = [];
  for (const c of comenzi) {
    const s = await statusPlata(c.ntp_id, c.cod);
    if (!s.ok) {
      note.push(`${c.cod}: ${s.mesaj}`);
      continue;
    }
    if (!PLATIT.has(s.status)) {
      note.push(`${c.cod}: status ${s.status}, neplătită`);
      continue;
    }
    platite++;
    if (await confirmaComanda(c.id, c.ntp_id)) {
      emise++;
      await trimiteEmailComanda(c.id);
    } else {
      note.push(`${c.cod}: plătită, dar nu s-au putut emite biletele`);
    }
  }

  const mesaj = comenzi.length
    ? `Verificate ${comenzi.length}: ${platite} plătite, ${emise} cu bilete emise. ${note.join("; ")}`.slice(0, 400)
    : "Nu e nicio comandă neconfirmată din ultimele 7 zile.";
  redirect(`/admin/setari?net=${encodeURIComponent(mesaj)}`);
}

/**
 * Generează o cheie nouă de previzualizare a plății. Cea veche moare pe loc,
 * deci linkurile date până acum nu mai deschid nimic.
 */
export async function regenereazaPreview(): Promise<void> {
  await requireOwner();
  await setSetting("checkout_preview_token", randomBytes(16).toString("hex"));
  redirect("/admin/setari?saved=1");
}

export async function saveHeadScripts(formData: FormData): Promise<void> {
  await requireOwner();
  await setSetting("head_scripts", String(formData.get("head_scripts") ?? ""));
  revalidatePath("/", "layout");
  redirect("/admin/setari?saved=1");
}

export async function addRecurring(): Promise<void> {
  await requireOwner();
  await sql`
    INSERT INTO recurring_tasks (legacy_id, type, title, assigned_to, days, position)
    VALUES (${"rec_" + Math.random().toString(16).slice(2, 14)}, 'monthly', 'Task nou', 'eric6', '{}',
            (SELECT COALESCE(MAX(position), 0) + 1 FROM recurring_tasks))
  `;
  redirect("/admin/setari?rec=ok#rec");
}

export async function saveRecurring(formData: FormData): Promise<void> {
  await requireOwner();
  const id = Number(g(formData, "id"));
  const title = g(formData, "title");
  if (!id) redirect("/admin/setari#rec");
  let assigned = g(formData, "assigned_to");
  const valid = (await sql`SELECT username FROM users`) as { username: string }[];
  if (!valid.some((u) => u.username === assigned)) assigned = "eric6";
  const days = [...new Set(formData.getAll("days").map(Number).filter((d) => d >= 1 && d <= 31))].sort((a, b) => a - b);
  // PHP (actions.php:366-369): titlul gol păstrează titlul vechi, dar responsabilul și zilele se salvează oricum
  await sql`
    UPDATE recurring_tasks
    SET title = COALESCE(NULLIF(${title}::text, ''), title), assigned_to = ${assigned}, days = ${days}
    WHERE id = ${id} AND type = 'monthly'
  `;
  redirect("/admin/setari?rec=ok#rec");
}

export async function saveRecurringSystemTitle(formData: FormData): Promise<void> {
  await requireOwner();
  const id = Number(g(formData, "id"));
  const title = g(formData, "title");
  if (!id) redirect("/admin/setari#rec");
  // PHP (actions.php:385-387): titlul gol nu suprascrie nimic, dar salvarea raportează tot ?rec=ok
  if (title) await sql`UPDATE recurring_tasks SET title = ${title} WHERE id = ${id} AND type = 'system'`;
  redirect("/admin/setari?rec=ok#rec");
}

export async function deleteRecurring(formData: FormData): Promise<void> {
  await requireOwner();
  const id = Number(g(formData, "id"));
  if (!id) redirect("/admin/setari#rec");
  await sql`DELETE FROM recurring_tasks WHERE id = ${id} AND type = 'monthly'`;
  redirect("/admin/setari?rec=ok#rec");
}

export async function changePassword(formData: FormData): Promise<void> {
  const s = await requireOwner();
  const pw = g(formData, "new_password");
  const confirm = g(formData, "confirm_password");
  if (!pw || pw !== confirm || pw.length < 6) redirect("/admin/setari?error=1");
  const hash = await bcrypt.hash(pw, 10);
  await sql`UPDATE users SET password_hash = ${hash} WHERE username = ${s.username}`;
  redirect("/admin/setari?saved=1");
}

/** Regenerează tokenul folosit de sync-export.php (fost `regenerate_sync_token`). */
export async function regenerateSyncToken(): Promise<void> {
  await requireOwner();
  const token = randomBytes(32).toString("hex");
  await sql`
    INSERT INTO settings(key, value) VALUES('sync_token', ${JSON.stringify(token)}::jsonb)
    ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value
  `;
  redirect("/admin/setari?saved=1");
}
