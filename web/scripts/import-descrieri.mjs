import { neon } from "@neondatabase/serverless";

// Descrierile cursurilor există deja în LiveTickets (scrise de noi acolo). Le
// aducem o dată, curățate, ca pagina de curs să nu pornească goală.

const sql = neon(process.env.DATABASE_URL);
const UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/124 Safari/537.36";

const ALLOWED = new Set(["p", "br", "strong", "b", "em", "i", "u", "ul", "ol", "li", "h2", "h3", "h4", "a", "blockquote"]);

function cleanDescriere(raw) {
  if (!raw) return "";
  let html = raw
    .replace(/<(script|style|head|title)\b[^>]*>[\s\S]*?<\/\1>/gi, "")
    .replace(/<!--[\s\S]*?-->/g, "");
  html = html.replace(/<\/?([a-z0-9]+)\b([^>]*)>/gi, (tag, name, attrs) => {
    const t = name.toLowerCase();
    if (!ALLOWED.has(t)) return "";
    if (tag.startsWith("</")) return `</${t}>`;
    if (t === "a") {
      const href = /href\s*=\s*["']([^"']*)["']/i.exec(attrs)?.[1] ?? "";
      return /^https?:\/\//i.test(href)
        ? `<a href="${href.replace(/"/g, "&quot;")}" target="_blank" rel="noopener nofollow">`
        : "<a>";
    }
    return t === "br" ? "<br>" : `<${t}>`;
  });
  return html
    .replace(/<p>(\s|&nbsp;|<br>)*<\/p>/gi, "")
    .replace(/(<br>\s*){3,}/gi, "<br><br>")
    .trim();
}

await sql`ALTER TABLE events ADD COLUMN IF NOT EXISTS description text`;

async function slugFor(url) {
  const parts = new URL(url).pathname.split("/").filter(Boolean);
  const i = parts.indexOf("bilete");
  if (i !== -1 && parts[i + 1]) return parts[i + 1];
  const e = parts.indexOf("e");
  if (e !== -1 && parts[e + 1]) {
    const r = await fetch(`https://api.livetickets.ro/public/events/get-url?code=${parts[e + 1]}`, {
      headers: { "User-Agent": UA },
    });
    if (r.ok) return (await r.json())?.url ?? "";
  }
  return "";
}

const events = await sql`
  SELECT id, title, livetickets_url FROM events
  WHERE livetickets_url IS NOT NULL AND livetickets_url <> ''
    AND (description IS NULL OR description = '')
  ORDER BY starts_at DESC NULLS LAST
`;

let luate = 0;
for (const e of events) {
  try {
    const slug = await slugFor(e.livetickets_url);
    if (!slug) continue;
    const res = await fetch(`https://api.livetickets.ro/public/events/getbyurl?url=${encodeURIComponent(slug)}`, {
      headers: { "User-Agent": UA },
    });
    if (!res.ok) continue;
    const desc = cleanDescriere(String((await res.json())?.description ?? ""));
    if (!desc) continue;
    await sql`UPDATE events SET description = ${desc} WHERE id = ${e.id}`;
    luate++;
    console.log(`✓ ${e.title.slice(0, 48)} — ${desc.length} caractere`);
  } catch {
    console.log(`✗ ${e.title.slice(0, 48)}`);
  }
}
console.log(`\n${luate} din ${events.length} descrieri importate`);
