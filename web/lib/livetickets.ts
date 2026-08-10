// Port din lib/livetickets.php — meta unui curs dintr-un link de bilete
// LiveTickets, via API publică.

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
    // CURLOPT_TIMEOUT = 12 în lib/livetickets.php: LiveTickets blocat nu are voie
    // să țină pagina publică, degradează în „nu e sold out"
    const res = await fetch(url, {
      headers: { "User-Agent": UA, Accept: "*/*" },
      cache: "no-store",
      signal: AbortSignal.timeout(12_000),
    });
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
type LtItem = { soldout?: unknown };

export type LtEvent = {
  id?: unknown;
  name?: unknown;
  start_date?: unknown;
  startDate?: unknown;
  location?: unknown;
  images?: unknown;
  items?: unknown;
  remaining_count?: unknown;
  ticket_count?: unknown;
};

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

/**
 * Port lt_get_event_by_url(): evenimentul după slug; dacă `items` lipsește din
 * getbyurl, biletele vin separat de la get-tickets.
 */
export async function ltGetEventByUrl(rawUrl: string): Promise<LtEvent | null> {
  const slug = await ltSlugFromUrl(rawUrl.trim());
  if (!slug) return null;

  const resp = await httpGet(`https://api.livetickets.ro/public/events/getbyurl?url=${encodeURIComponent(slug)}`);
  if (!resp) return null;

  let ev: LtEvent;
  try {
    ev = JSON.parse(resp) as LtEvent;
  } catch {
    return null;
  }
  if (!ev || typeof ev !== "object" || ev.id === undefined || ev.id === null) return null;

  // get-tickets e lista de bilete la zi; getbyurl întoarce `items` inconstant
  // (uneori lipsă, alteori învechit), deci întrebăm mereu și preferăm răspunsul lui.
  const tresp = await httpGet(`https://api.livetickets.ro/public/events/get-tickets?url=${encodeURIComponent(slug)}`);
  if (tresp) {
    try {
      const tickets = JSON.parse(tresp);
      if (Array.isArray(tickets) && tickets.length > 0) ev.items = tickets;
    } catch {
      // răspuns invalid — rămânem cu ce avem din getbyurl
    }
  }

  return ev;
}

/** Port lt_image_url_from_event(). */
export function ltImageUrlFromEvent(event: LtEvent): string {
  return ltImageUrl(Array.isArray(event.images) ? (event.images as LtImage[]) : []);
}

/**
 * Epuizat dacă TOATE biletele au soldout. Fără listă de bilete ne bazăm pe
 * remaining_count/ticket_count, iar dacă nici alea nu spun nimic întoarcem
 * `null` = „nu se poate ști”.
 *
 * `null` contează: LiveTickets trimite `ticket_count: 0` și pentru evenimente
 * care chiar au bilete, deci vechiul „0 rămase din 0 → nu e epuizat” transforma
 * un răspuns fără informație într-un fals „mai sunt locuri”.
 */
export function ltIsSoldOut(event: LtEvent): boolean | null {
  const items = Array.isArray(event.items) ? (event.items as LtItem[]) : [];
  if (items.length > 0) {
    for (const item of items) {
      if (!item || typeof item !== "object") continue;
      // empty() din PHP: 0, "", "0", false, null → nu e epuizat
      if (!item.soldout || item.soldout === "0") return false;
    }
    return true;
  }

  const total = Number(event.ticket_count);
  if (!Number.isFinite(total) || total <= 0) return null;
  return event.remaining_count === 0;
}

/** Meta cursului dintr-un link LiveTickets, ca clp_fetch_course_meta_by_url(). */
export async function fetchCourseMeta(rawUrl: string): Promise<MetaResult> {
  const url = rawUrl.trim();
  if (!url) return { success: false, message: "URL lipsă." };

  const ev = await ltGetEventByUrl(url);
  if (!ev) return { success: false, message: "Evenimentul nu a fost găsit în LiveTickets." };

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
      image_url: ltImageUrlFromEvent(ev),
    },
  };
}
