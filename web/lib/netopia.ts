// Plata cu cardul prin Netopia, API v2. Mergem pe pagina lor găzduită: cererea
// pleacă fără date de card, iar ei ne dau un URL de 3-D Secure la care trimitem
// omul. Nu atingem niciodată numărul cardului, deci rămânem în afara PCI-DSS.

/**
 * Adresele API, verificate una câte una cu o cheie invalidă:
 *
 * - `secure.sandbox.netopia-payments.com`  → 401 JSON, și cheia noastră de test
 *   chiar trece pe aici (plata din 1 aug a apărut în panoul de sandbox);
 * - `secure-sandbox.netopia-payments.com`  → 401 JSON, dar cheia noastră e
 *   respinsă: e alt mediu, nu al nostru;
 * - `secure.netopia-payments.com`          → 302 spre site. Nu e API.
 *
 * Producția e cea din SDK-ul lor (`netopia_sdk/config.py`). `NETOPIA_BASE`
 * pasează peste amândouă, dacă Netopia mută ceva.
 */
const BAZA = (
  process.env.NETOPIA_BASE ||
  (process.env.NETOPIA_SANDBOX === "1"
    ? "https://secure.sandbox.netopia-payments.com"
    : "https://secure.mobilpay.ro/pay")
).replace(/\/+$/, "");

export type StartRezultat =
  | { ok: true; paymentURL: string; ntpID: string }
  | { ok: false; mesaj: string };

/** Codul 101 înseamnă „redirect la 3-D Secure" — singurul răspuns bun aici. */
const COD_REDIRECT = "101";

export async function startPayment(opts: {
  cod: string;
  suma: number;
  descriere: string;
  nume: string;
  email: string;
  telefon?: string;
  redirectUrl: string;
  notifyUrl: string;
}): Promise<StartRezultat> {
  const apiKey = process.env.NETOPIA_API_KEY;
  const posSignature = process.env.NETOPIA_SIGNATURE;
  if (!apiKey || !posSignature) return { ok: false, mesaj: "Plata nu e configurată." };

  const [prenume, ...rest] = opts.nume.trim().split(/\s+/);
  const numeFamilie = rest.join(" ") || prenume;

  const body = {
    config: {
      notifyUrl: opts.notifyUrl,
      redirectUrl: opts.redirectUrl,
      language: "ro",
    },
    payment: {
      options: { installments: 0, bonus: 0 },
      // fără `instrument`: cardul se introduce pe pagina lor
      instrument: {},
      data: {},
    },
    order: {
      posSignature,
      dateTime: new Date().toISOString(),
      description: opts.descriere,
      orderID: opts.cod,
      amount: Number(opts.suma.toFixed(2)),
      currency: "RON",
      billing: {
        email: opts.email,
        phone: opts.telefon || "-",
        firstName: prenume || "-",
        lastName: numeFamilie || "-",
        city: "Bucuresti",
        country: 642,
        countryName: "Romania",
        state: "Bucuresti",
        postalCode: "-",
        details: "-",
      },
    },
  };

  try {
    const res = await fetch(`${BAZA}/payment/card/start`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: apiKey },
      body: JSON.stringify(body),
    });
    // Textul brut întâi: la erori (401 etc.) răspunsul poate să nu fie JSON, iar
    // atunci `res.json()` aruncă și pierdem exact explicația de care avem nevoie.
    const text = await res.text();
    let json: {
      payment?: { paymentURL?: string; ntpID?: string; status?: number };
      error?: { code?: string; message?: string };
    } = {};
    try {
      json = JSON.parse(text);
    } catch {
      /* răspuns care nu e JSON — rămâne în `text` */
    }

    const url = json.payment?.paymentURL;
    if (url && (!json.error?.code || json.error.code === COD_REDIRECT || json.error.code === "00")) {
      return { ok: true, paymentURL: url, ntpID: String(json.payment?.ntpID ?? "") };
    }
    const detaliu = json.error?.message || text.slice(0, 200) || "fără corp";
    return { ok: false, mesaj: `${res.status} de la ${BAZA}: ${detaliu}` };
  } catch (e) {
    return { ok: false, mesaj: e instanceof Error ? e.message : "Nu s-a putut porni plata." };
  }
}

/**
 * Statusurile din notificarea IPN. 3 = plătit, 5 = confirmat; restul înseamnă
 * că banii nu au intrat.
 */
export const PLATIT = new Set([3, 5]);

/**
 * Ce vede aplicația despre configurarea Netopia. Din afară nu se poate afla:
 * `vercel env pull` nu întoarce valorile variabilelor sensibile, așa că singurul
 * loc din care se poate răspunde e chiar procesul care le folosește.
 *
 * Întoarce doar forme — lungimi și da/nu — niciodată conținutul.
 */
export async function diagnostic() {
  const val = (k: string) => (process.env[k] ?? "").trim();
  const pem = val("NETOPIA_PUBLIC_KEY").replace(/\\n/g, "\n").trim();

  let cheiaSeCiteste: boolean | null = null;
  let felCheie = "lipsește";
  if (pem) {
    felCheie = pem.includes("BEGIN CERTIFICATE")
      ? "certificat X.509"
      : pem.includes("BEGIN PUBLIC KEY")
        ? "cheie publică PEM"
        : "text simplu, fără antet PEM";
    try {
      const { createPublicKey, X509Certificate } = await import("node:crypto");
      const k = pem.includes("BEGIN CERTIFICATE") ? new X509Certificate(pem).publicKey : createPublicKey(pem);
      const biti = k.asymmetricKeyDetails?.modulusLength;
      if (biti) felCheie += `, RSA ${biti} biți`;
      cheiaSeCiteste = true;
    } catch {
      cheiaSeCiteste = false;
    }
  }

  return {
    apiKey: val("NETOPIA_API_KEY").length,
    semnatura: val("NETOPIA_SIGNATURE").length,
    baza: BAZA,
    cheieLungime: pem.length,
    felCheie,
    cheiaSeCiteste,
  };
}

/**
 * Verifică că notificarea chiar vine de la Netopia. Fără asta, oricine își
 * pornește o comandă, își vede codul și își trimite singur un „am plătit".
 *
 * Netopia semnează fiecare notificare cu un JWT în headerul `Verification-token`:
 * `iss` = „NETOPIA Payments", `aud` = semnătura noastră POS, iar `sub` e hash-ul
 * corpului exact așa cum a venit — deci se verifică pe textul brut, nu pe JSON-ul
 * reparsat.
 *
 * Verificarea e pe `node:crypto`, nu pe `jose`, dinadins: certificatele de
 * sandbox ale Netopiei au cheie RSA de 1024 de biți, iar `jose` refuză din
 * politică proprie orice cheie sub 2048 la RS512 („RS512 requires key
 * modulusLength to be 2048 bits or larger"). Semnătura e validă, doar
 * biblioteca era mai strictă decât emitentul.
 */
export type Verificare = { ok: boolean; motiv: string };

export async function verificaNotificare(raw: string, token: string | null): Promise<Verificare> {
  const pem = (process.env.NETOPIA_PUBLIC_KEY ?? "").replace(/\\n/g, "\n").trim();
  const posSignature = process.env.NETOPIA_SIGNATURE ?? "";
  if (!token) return { ok: false, motiv: "lipsește headerul Verification-token" };
  if (!pem) return { ok: false, motiv: "NETOPIA_PUBLIC_KEY nu e setată" };
  if (!posSignature) return { ok: false, motiv: "NETOPIA_SIGNATURE nu e setată" };

  try {
    const parti = token.split(".");
    if (parti.length !== 3) return { ok: false, motiv: "token care nu are trei părți" };

    const antet = JSON.parse(Buffer.from(parti[0], "base64url").toString("utf8")) as { alg?: string };
    const alg = antet.alg ?? "RS512";
    const hash = ({ RS256: "sha256", RS384: "sha384", RS512: "sha512" } as const)[
      alg as "RS256" | "RS384" | "RS512"
    ];
    if (!hash) return { ok: false, motiv: `algoritm nesuportat: ${alg}` };

    const { createHash, createPublicKey, createVerify, X509Certificate } = await import("node:crypto");
    const cheie = pem.includes("BEGIN CERTIFICATE") ? new X509Certificate(pem).publicKey : createPublicKey(pem);

    const v = createVerify(hash);
    v.update(`${parti[0]}.${parti[1]}`);
    v.end();
    if (!v.verify(cheie, Buffer.from(parti[2], "base64url"))) {
      return { ok: false, motiv: "semnătura nu se potrivește cu cheia publică" };
    }

    const payload = JSON.parse(Buffer.from(parti[1], "base64url").toString("utf8")) as {
      iss?: string;
      aud?: string | string[];
      sub?: string;
      exp?: number;
    };

    if (payload.iss !== "NETOPIA Payments") return { ok: false, motiv: `emitent neașteptat: ${payload.iss}` };

    const aud = Array.isArray(payload.aud) ? payload.aud[0] : payload.aud;
    if (aud !== posSignature) return { ok: false, motiv: "audiența nu e semnătura noastră POS" };

    if (payload.exp && payload.exp * 1000 < Date.now() - 60_000) {
      return { ok: false, motiv: "token expirat" };
    }

    if (payload.sub !== createHash("sha512").update(raw, "utf8").digest("base64")) {
      return { ok: false, motiv: "hash-ul corpului nu se potrivește" };
    }
    return { ok: true, motiv: "ok" };
  } catch (e) {
    return { ok: false, motiv: `verificare picată: ${e instanceof Error ? e.message : "eroare"}` };
  }
}
