// Descrierile vin din LiveTickets ca HTML scris într-un editor WYSIWYG: <meta>,
// stiluri inline, entități. Le curățăm o dată, la import sau la salvare, și
// păstrăm în bază doar HTML-ul pe care îl afișăm.

const ALLOWED = new Set(["p", "br", "strong", "b", "em", "i", "u", "ul", "ol", "li", "h2", "h3", "h4", "a", "blockquote"]);

/**
 * Păstrează doar tag-urile din lista de mai sus, fără atribute (excepție: href
 * pe link-uri, doar http/https). Restul se elimină, inclusiv script/style/meta.
 */
export function cleanDescriere(raw: string): string {
  if (!raw) return "";

  let html = raw
    // conținutul lor, nu doar tag-ul: un <script> golit de tag ar lăsa codul ca text
    .replace(/<(script|style|head|title)\b[^>]*>[\s\S]*?<\/\1>/gi, "")
    .replace(/<!--[\s\S]*?-->/g, "");

  html = html.replace(/<\/?([a-z0-9]+)\b([^>]*)>/gi, (tag, name: string, attrs: string) => {
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
    // paragrafe goale rămase după curățare
    .replace(/<p>(\s|&nbsp;|<br>)*<\/p>/gi, "")
    .replace(/(<br>\s*){3,}/gi, "<br><br>")
    .trim();
}

/** Text simplu pentru meta description, din HTML-ul deja curățat. */
export function descriereText(html: string, max = 160): string {
  const txt = html
    .replace(/<[^>]+>/g, " ")
    .replace(/&nbsp;/g, " ")
    .replace(/&amp;/g, "&")
    .replace(/&icirc;/g, "î")
    .replace(/&acirc;/g, "â")
    .replace(/&(?:#\d+|[a-z]+);/gi, "")
    .replace(/\s+/g, " ")
    .trim();
  return txt.length > max ? `${txt.slice(0, max - 1).trimEnd()}…` : txt;
}
