import { cookies } from "next/headers";
import { getSettings } from "@/lib/settings";
import { getSession } from "@/lib/auth";

/** Cookie-ul pus de linkul de previzualizare. Ține cheia, nu un simplu „da". */
export const COOKIE_PREVIZUALIZARE = "clp_plata_preview";

/**
 * Comutatorul care mută vânzarea de pe LiveTickets pe site:
 *
 * - `off`  - toată lumea cumpără de pe LiveTickets;
 * - `test` - plata pe site se vede doar dacă ești logat în admin, ca să poți
 *            proba tot drumul pe domeniul real fără s-o vadă cumpărătorii;
 * - `on`   - plata pe site, pentru toți.
 *
 * Cât timp nu e pornit, pagina de curs și coșul arată prețurile reale, dar
 * butonul duce tot la LiveTickets - ca să nu existe un drum care se termină
 * fără plată.
 */
export type ModCheckout = "off" | "test" | "on";

/** Valorile vechi (`true`, `1`, `"1"`) însemnau „pornit pentru toți". */
export function citesteMod(v: unknown): ModCheckout {
  if (v === "test") return "test";
  if (v === true || v === 1 || v === "1" || v === "true" || v === "on") return "on";
  return "off";
}

export async function modCheckout(): Promise<ModCheckout> {
  return citesteMod((await getSettings()).checkout_propriu);
}

/**
 * Cheia care deschide fluxul de plată cuiva din afară — verificatorului de la
 * procesatorul de plăți, care trebuie să vadă drumul complet, dar pe care nu
 * vrem să-l vadă și cumpărătorii.
 *
 * Cookie-ul păstrează cheia, nu un marcaj de tip „da”: dacă se regenerează
 * cheia din setări, toate linkurile date până atunci încetează să funcționeze.
 */
async function arePreviewValid(): Promise<boolean> {
  const cheie = (await getSettings()).checkout_preview_token;
  if (typeof cheie !== "string" || !cheie) return false;
  return (await cookies()).get(COOKIE_PREVIZUALIZARE)?.value === cheie;
}

/** Poate omul care se uită acum la pagină să plătească pe site? */
export async function checkoutPropriuActiv(): Promise<boolean> {
  const mod = await modCheckout();
  if (mod === "on") return true;
  if (mod === "off") return false;
  return !!(await getSession()) || (await arePreviewValid());
}
