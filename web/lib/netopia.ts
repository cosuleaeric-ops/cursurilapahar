// Plata cu cardul prin Netopia, API v2. Mergem pe pagina lor găzduită: cererea
// pleacă fără date de card, iar ei ne dau un URL de 3-D Secure la care trimitem
// omul. Nu atingem niciodată numărul cardului, deci rămânem în afara PCI-DSS.

const BAZA = process.env.NETOPIA_SANDBOX === "1"
  ? "https://secure.sandbox.netopia-payments.com"
  : "https://secure.netopia-payments.com";

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
    const json = (await res.json()) as {
      payment?: { paymentURL?: string; ntpID?: string; status?: number };
      error?: { code?: string; message?: string };
    };

    const url = json.payment?.paymentURL;
    if (url && (!json.error?.code || json.error.code === COD_REDIRECT || json.error.code === "00")) {
      return { ok: true, paymentURL: url, ntpID: String(json.payment?.ntpID ?? "") };
    }
    return { ok: false, mesaj: json.error?.message || `Netopia a răspuns ${res.status}` };
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
 * Verifică că notificarea chiar vine de la Netopia. Fără asta, oricine își
 * pornește o comandă, își vede codul și își trimite singur un „am plătit".
 *
 * Netopia semnează fiecare notificare cu un JWT în headerul `Verification-token`:
 * `iss` = „NETOPIA Payments", `aud` = semnătura noastră POS, iar `sub` e hash-ul
 * corpului exact așa cum a venit — deci se verifică pe textul brut, nu pe JSON-ul
 * reparsat.
 */
export async function verificaNotificare(raw: string, token: string | null): Promise<boolean> {
  const pem = (process.env.NETOPIA_PUBLIC_KEY ?? "").replace(/\\n/g, "\n").trim();
  const posSignature = process.env.NETOPIA_SIGNATURE ?? "";
  if (!token || !pem || !posSignature) return false;

  try {
    const { importSPKI, importX509, jwtVerify, decodeProtectedHeader } = await import("jose");
    const { createHash } = await import("node:crypto");

    const alg = decodeProtectedHeader(token).alg ?? "RS512";
    const key = pem.includes("BEGIN CERTIFICATE")
      ? await importX509(pem, alg)
      : await importSPKI(pem, alg);

    const { payload } = await jwtVerify(token, key, { algorithms: [alg] });
    if (payload.iss !== "NETOPIA Payments") return false;

    const aud = Array.isArray(payload.aud) ? payload.aud[0] : payload.aud;
    if (aud !== posSignature) return false;

    return payload.sub === createHash("sha512").update(raw, "utf8").digest("base64");
  } catch {
    return false;
  }
}
