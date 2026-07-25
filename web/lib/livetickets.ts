// Port din lib/livetickets.php + lib/iabilet.php — meta unui curs dintr-un link
// de bilete (LiveTickets via API publică, iaBilet via og:image).

const UA =
  "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36";

export type CourseMeta = {
  title: string;
  date_raw: string;
  time: string;
  location: string;
  image_url: string;
};

export type MetaResult = { success: true; data: CourseMeta } | { success: false; message: string };

async function httpGet(url: string): Promise<string | null> {
  try {
    const res = await fetch(url, { headers: { "User-Agent": UA, Accept: "*/*" }, cache: "no-store" });
    return res.ok ? await res.text() : null;
  } catch {
    return null;
  }
}

async function ltSlugFromUrl(url: string): Promise<string> {
  let path = "";
  try {
    path = new URL(url).pathname;
  } catch {
    return "";
  }
  const parts = path.split("/").filter(Boolean);

  const iB = parts.indexOf("bilete");
  if (iB !== -1 && parts[iB + 1]) return parts[iB + 1];

  const iE = parts.indexOf("e");
  if (iE !== -1 && parts[iE + 1]) {
    const resp = await httpGet(
      `https://api.livetickets.ro/public/events/get-url?code=${encodeURIComponent(parts[iE + 1])}`,
    );
    if (resp) {
      try {
        return String(JSON.parse(resp)?.url ?? "");
      } catch {
        return "";
      }
    }
  }
  return "";
}

type LtImage = { path?: string; token?: string; name?: string; size?: string };

function ltImageUrl(images: LtImage[]): string {
  let fallback = "";
  for (const img of images) {
    if (!img?.path) continue;
    const cdn = `https://livetickets-cdn.azureedge.net/itemimages/${img.path}${img.token ? `?${img.token}` : ""}`;
    if (!fallback) fallback = cdn;
    if (img.name === "Background" && img.size === "MEDIUM") return cdn;
  }
  return fallback;
}

function ogContent(html: string, property: string): string {
  const re = new RegExp(
    `<meta[^>]+(?:property|name)=["']${property}["'][^>]*content=["']([^"']+)["']`,
    "i",
  );
  const m = html.match(re);
  if (m) return m[1];
  const re2 = new RegExp(
    `<meta[^>]+content=["']([^"']+)["'][^>]*(?:property|name)=["']${property}["']`,
    "i",
  );
  return html.match(re2)?.[1] ?? "";
}

const EMPTY: CourseMeta = { title: "", date_raw: "", time: "", location: "", image_url: "" };

async function iabiletFetch(url: string): Promise<MetaResult> {
  const html = await httpGet(url);
  if (!html) return { success: false, message: "Nu s-a putut accesa pagina iaBilet." };
  const image = ogContent(html, "og:image");
  if (!image) return { success: false, message: "Nu s-a găsit imaginea pe pagina iaBilet." };
  return { success: true, data: { ...EMPTY, title: ogContent(html, "og:title"), image_url: image } };
}

/** Rutează după provider, ca clp_fetch_course_meta_by_url(). */
export async function fetchCourseMeta(rawUrl: string): Promise<MetaResult> {
  const url = rawUrl.trim();
  if (!url) return { success: false, message: "URL lipsă." };
  if (/iabilet\.ro/i.test(url)) return iabiletFetch(url);

  const slug = await ltSlugFromUrl(url);
  if (!slug) return { success: false, message: "Evenimentul nu a fost găsit în LiveTickets." };

  const resp = await httpGet(`https://api.livetickets.ro/public/events/getbyurl?url=${encodeURIComponent(slug)}`);
  if (!resp) return { success: false, message: "Evenimentul nu a fost găsit în LiveTickets." };

  let ev: Record<string, unknown>;
  try {
    ev = JSON.parse(resp);
  } catch {
    return { success: false, message: "Răspuns invalid de la LiveTickets." };
  }
  if (!ev?.id) return { success: false, message: "Evenimentul nu a fost găsit în LiveTickets." };

  let date_raw = "";
  let time = "";
  const start = String(ev.start_date ?? ev.startDate ?? "");
  if (start) {
    const d = new Date(start);
    if (!Number.isNaN(d.getTime())) {
      date_raw = new Intl.DateTimeFormat("en-CA", { timeZone: "Europe/Bucharest" }).format(d);
      time = new Intl.DateTimeFormat("ro-RO", {
        timeZone: "Europe/Bucharest",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      }).format(d);
    }
  }

  const loc = ev.location;
  let location = "";
  if (typeof loc === "string") location = loc;
  else if (loc && typeof loc === "object") {
    const o = loc as Record<string, unknown>;
    location = [o.name, o.address, o.city].filter(Boolean).map(String).join(", ");
  }

  return {
    success: true,
    data: {
      title: String(ev.name ?? ""),
      date_raw,
      time,
      location,
      image_url: ltImageUrl(Array.isArray(ev.images) ? (ev.images as LtImage[]) : []),
    },
  };
}
