import { getSettings } from "@/lib/settings";

/**
 * Comutatorul care mută vânzarea de pe LiveTickets pe site. Cât timp e stins,
 * pagina de curs și coșul arată prețurile reale, dar butonul duce tot la
 * LiveTickets — ca să nu existe un drum care se termină fără plată.
 * Se aprinde din setări, după ce există contul de procesator.
 */
export async function checkoutPropriuActiv(): Promise<boolean> {
  const v = (await getSettings()).checkout_propriu;
  return v === true || v === 1 || v === "1" || v === "true";
}
