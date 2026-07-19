// Randează scripturile din settings.head_scripts (Umami, GA4 etc.).
// dangerouslySetInnerHTML pe un container NU execută <script>-uri, așa că
// parsăm tag-urile și le redăm ca elemente <script> reale în HTML-ul inițial.

type ParsedScript = { attrs: Record<string, string | boolean>; inner: string };

function parseScripts(html: string): ParsedScript[] {
  const out: ParsedScript[] = [];
  const re = /<script\b([^>]*)>([\s\S]*?)<\/script>/gi;
  let m: RegExpExecArray | null;
  while ((m = re.exec(html)) !== null) {
    const attrs: Record<string, string | boolean> = {};
    const attrRe = /([a-zA-Z][\w-]*)(?:\s*=\s*"([^"]*)")?/g;
    let a: RegExpExecArray | null;
    while ((a = attrRe.exec(m[1])) !== null) {
      attrs[a[1].toLowerCase()] = a[2] ?? true;
    }
    out.push({ attrs, inner: m[2].trim() });
  }
  return out;
}

export default function HeadScripts({ html }: { html: string }) {
  if (!html.trim()) return null;
  return (
    <>
      {parseScripts(html).map((s, i) => {
        const { src, async: isAsync, defer, ...rest } = s.attrs;
        const dataAttrs: Record<string, string> = {};
        for (const [k, v] of Object.entries(rest)) {
          if (typeof v === "string") dataAttrs[k] = v;
        }
        return src ? (
          <script key={i} src={String(src)} async={!!isAsync} defer={!!defer} {...dataAttrs} />
        ) : (
          <script key={i} {...dataAttrs} dangerouslySetInnerHTML={{ __html: s.inner }} />
        );
      })}
    </>
  );
}
