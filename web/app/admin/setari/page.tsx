import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import {
  saveKit,
  saveBrevo,
  saveMetaAds,
  saveHeadScripts,
  saveCheckout,
  testeazaNetopia,
  verificaUltimaNotificare,
  recupereazaComenzi,
  changePassword,
} from "./actions";
import { citesteMod } from "@/lib/checkout";
import { diagnostic } from "@/lib/netopia";
import SyncToken from "./SyncToken";
import QuickLinksEditor, { type QuickLink } from "./QuickLinksEditor";
import RecurringEditor, { type RecTask } from "./RecurringEditor";

export const dynamic = "force-dynamic";

export default async function SetariPage({
  searchParams,
}: {
  searchParams: Promise<{ saved?: string; error?: string; rec?: string; cfg?: string; net?: string }>;
}) {
  const session = await getSession();
  if (!session) redirect("/login");
  if (session.role !== "owner") redirect("/admin");

  const { saved, error, rec, cfg, net } = await searchParams;
  const rows = (await sql`
    SELECT key, value FROM settings
    WHERE key IN ('quick_links', 'kit_api_key', 'kit_form_id', 'brevo_api_key', 'meta_ads_token', 'head_scripts', 'sync_token', 'checkout_propriu')
  `) as { key: string; value: unknown }[];
  const recTasks = (await sql`
    SELECT id, type, system_key, assigned_to, title, schedule, description, days
    FROM recurring_tasks ORDER BY position, id
  `) as RecTask[];
  const users = ((await sql`SELECT username FROM users ORDER BY id`) as { username: string }[]).map((u) => u.username);
  const s = Object.fromEntries(rows.map((r) => [r.key, r.value]));
  const str = (k: string) => (typeof s[k] === "string" ? (s[k] as string) : "");
  const quickLinks = Array.isArray(s.quick_links) ? (s.quick_links as QuickLink[]) : [];
  const modCheckout = citesteMod(s.checkout_propriu);
  const netopia = await diagnostic();
  const notificari = (await sql`
    SELECT created_at, ok, motiv, cod, status FROM webhook_log ORDER BY created_at DESC LIMIT 5
  `) as { created_at: string; ok: boolean; motiv: string; cod: string | null; status: number | null }[];

  return (
    <>
      <h1 className="wp-page-title">Setări</h1>

      {saved && <div className="notice notice-success">Setările au fost salvate.</div>}
      {error && <div className="notice notice-error">Parolele nu coincid sau sunt prea scurte (minim 6 caractere).</div>}
      {cfg && (
        <div className="notice notice-error">
          Nu pot porni plata pe site: lipsesc din Vercel {cfg}. Fără ele nu se poate verifica notificarea de plată,
          iar clienții ar plăti fără să primească biletele. Setarea a rămas neschimbată.
        </div>
      )}

      <div className="card">
        <div className="card-title">🔌 Netopia - ce vede aplicația</div>
        <p style={{ fontSize: 13, color: "var(--text-muted)", marginBottom: 16 }}>
          Citit din interiorul serverului, nu din panoul Vercel - variabilele sensibile nu se pot citi înapoi de
          acolo. Se arată doar dacă sunt puse și ce formă au, niciodată conținutul. După ce schimbi ceva în Vercel,
          trebuie un redeploy ca să se vadă aici.
        </p>
        <table className="wp-table">
          <tbody>
            <tr>
              <td>Cheie API</td>
              <td>{netopia.apiKey ? `pusă (${netopia.apiKey} caractere)` : "❌ lipsește"}</td>
            </tr>
            <tr>
              <td>Semnătură POS</td>
              <td>{netopia.semnatura ? `pusă (${netopia.semnatura} caractere)` : "❌ lipsește"}</td>
            </tr>
            <tr>
              <td>Cheie publică</td>
              <td>
                {netopia.cheieLungime === 0
                  ? "❌ lipsește"
                  : `${netopia.felCheie}, ${netopia.cheieLungime} caractere - ${
                      netopia.cheiaSeCiteste ? "✅ se citește" : "❌ nu se poate citi ca cheie"
                    }`}
              </td>
            </tr>
            <tr>
              <td>Cerem la</td>
              <td>
                <code>{netopia.baza}</code> - mediul (test sau real) îl decide cheia API, nu adresa
              </td>
            </tr>
          </tbody>
        </table>

        <div style={{ display: "flex", gap: 8, marginTop: 16, flexWrap: "wrap" }}>
          <form action={testeazaNetopia}>
            <button type="submit" className="btn">
              Testează conexiunea
            </button>
          </form>
          <form action={verificaUltimaNotificare}>
            <button type="submit" className="btn">
              Reverifică ultima notificare
            </button>
          </form>
          <form action={recupereazaComenzi}>
            <button type="submit" className="btn">
              Recuperează comenzile neconfirmate
            </button>
          </form>
        </div>
        <p className="form-desc" style={{ marginTop: 6 }}>
          Primul cere o plată de probă - nu creează comandă și nu rezervă bilete. Al doilea reia ultima notificare
          respinsă și o verifică din nou cu cheia publică de acum. Al treilea întreabă Netopia despre fiecare comandă
          rămasă neconfirmată din ultimele 7 zile și emite biletele pentru cele plătite - plasa de siguranță când o
          notificare se pierde.
        </p>
        {net && (
          <p style={{ marginTop: 12, fontSize: 13, wordBreak: "break-word" }}>
            <strong>{net}</strong>
          </p>
        )}

        <div style={{ marginTop: 20, fontSize: 13, fontWeight: 700 }}>Ultimele notificări primite</div>
        {notificari.length === 0 ? (
          <p className="form-desc" style={{ marginTop: 6 }}>
            Nicio notificare până acum. Dacă ai plătit și aici e gol, Netopia nu a ajuns deloc la noi.
          </p>
        ) : (
          <table className="wp-table" style={{ marginTop: 8 }}>
            <tbody>
              {notificari.map((n, i) => (
                <tr key={i}>
                  <td style={{ whiteSpace: "nowrap" }}>
                    {new Intl.DateTimeFormat("ro-RO", {
                      timeZone: "Europe/Bucharest",
                      day: "2-digit",
                      month: "2-digit",
                      hour: "2-digit",
                      minute: "2-digit",
                    }).format(new Date(n.created_at))}
                  </td>
                  <td>{n.ok ? "✅" : "❌"}</td>
                  <td>{n.cod ?? "-"}</td>
                  <td>{n.motiv}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      <form action={saveCheckout}>
        <div className="card">
          <div className="card-title">🎟️ Vânzarea biletelor</div>
          <p style={{ fontSize: 13, color: "var(--text-muted)", marginBottom: 16 }}>
            Unde ajunge cineva care apasă „Cumpără bilete". Prețurile de pe pagina de curs se văd la fel în toate
            cele trei cazuri - se schimbă doar unde se plătește.
          </p>
          {(
            [
              ["off", "LiveTickets", "Cumpărătorii merg pe LiveTickets, ca până acum."],
              ["test", "Doar pentru mine (test)", "Plata pe site apare doar dacă ești logat în admin. Cumpărătorii merg tot pe LiveTickets."],
              ["on", "Plata pe site", "Toată lumea plătește cu cardul direct la noi, prin Netopia."],
            ] as const
          ).map(([val, titlu, desc]) => (
            <label key={val} className="form-group" style={{ display: "flex", gap: 10, alignItems: "flex-start", cursor: "pointer" }}>
              <input type="radio" name="checkout_propriu" value={val} defaultChecked={modCheckout === val} style={{ marginTop: 3 }} />
              <span>
                <strong>{titlu}</strong>
                <p className="form-desc" style={{ margin: 0 }}>
                  {desc}
                </p>
              </span>
            </label>
          ))}
          <button type="submit" className="btn btn-primary">
            Salvează
          </button>
        </div>
      </form>

      <div className="card">
        <div className="card-title">🔗 Linkuri rapide - Dashboard</div>
        <p style={{ fontSize: 13, color: "var(--text-muted)", marginBottom: 16 }}>
          Aceste linkuri apar ca butoane în partea de sus a dashboard-ului.
        </p>
        <QuickLinksEditor links={quickLinks} />
      </div>

      <RecurringEditor tasks={recTasks} users={users} notice={rec} />

      <form action={saveKit}>
        <div className="card">
          <div className="card-title">📧 Kit (Email Marketing)</div>
          <div className="form-group">
            <label>API Key</label>
            <input type="text" name="kit_api_key" defaultValue={str("kit_api_key")} />
            <p className="form-desc">
              Găsești API Key-ul în{" "}
              <a href="https://app.kit.com/account_settings/developer_settings" target="_blank" style={{ color: "var(--accent)" }}>
                Kit → Settings → Developer
              </a>
              .
            </p>
          </div>
          <div className="form-group">
            <label>Form ID (opțional)</label>
            <input type="text" name="kit_form_id" defaultValue={str("kit_form_id")} />
            <p className="form-desc">
              Dacă vrei să adaugi abonații la un form specific. Lasă gol pentru a adăuga direct ca subscriber.
            </p>
          </div>
          <button type="submit" className="btn btn-primary">
            Salvează
          </button>
        </div>
      </form>

      <form action={saveBrevo}>
        <div className="card">
          <div className="card-title">✉️ Brevo (confirmări formulare)</div>
          <div className="form-group">
            <label>API Key</label>
            <input type="text" name="brevo_api_key" defaultValue={str("brevo_api_key")} />
            <p className="form-desc">
              Cheia din{" "}
              <a href="https://app.brevo.com/settings/keys/api" target="_blank" style={{ color: "var(--accent)" }}>
                Brevo → SMTP &amp; API → API Keys
              </a>
              . Trimite un email de confirmare automat celui care completează un formular. Lasă gol pentru a dezactiva.
            </p>
          </div>
          <button type="submit" className="btn btn-primary">
            Salvează
          </button>
        </div>
      </form>

      <form action={saveMetaAds}>
        <div className="card">
          <div className="card-title">📣 Meta Ads</div>
          <div className="form-group">
            <label>Token System User</label>
            <input type="text" name="meta_ads_token" defaultValue={str("meta_ads_token")} />
            <p className="form-desc">
              Tokenul de System User din{" "}
              <a
                href="https://business.facebook.com/settings/system-users"
                target="_blank"
                style={{ color: "var(--accent)" }}
              >
                Business Manager → Utilizatori de sistem
              </a>
              , cu permisiunea <code>ads_management</code> pe contul de reclame. Folosit de pagina{" "}
              <strong>Meta Ads</strong> din admin.
            </p>
          </div>
          <button type="submit" className="btn btn-primary">
            Salvează
          </button>
        </div>
      </form>

      <form action={saveHeadScripts}>
        <div className="card">
          <div className="card-title">📊 Analytics &amp; Tracking</div>
          <div className="form-group">
            <label>
              Cod <code>&lt;head&gt;</code>
            </label>
            <textarea
              name="head_scripts"
              rows={10}
              style={{ fontFamily: "monospace", fontSize: 12, lineHeight: 1.7 }}
              defaultValue={str("head_scripts")}
            ></textarea>
            <p className="form-desc">
              Lipește aici codul de tracking pentru <strong>Umami</strong>, <strong>Google Analytics (GA4)</strong> sau
              orice alt script. Va fi inserat automat în <code>&lt;head&gt;</code> pe <strong>toate paginile</strong>{" "}
              site-ului.
              <br />
              <span style={{ color: "#d63638" }}>⚠ Codul este inserat fără filtrare - adaugă doar scripturi de încredere.</span>
            </p>
          </div>
          <button type="submit" className="btn btn-primary">
            Salvează
          </button>
        </div>
      </form>

      <div className="card">
        <div className="card-title">🔒 Schimbă parola de admin</div>
        <form action={changePassword} style={{ maxWidth: 400 }}>
          <div className="form-group">
            <label htmlFor="new_password">Parolă nouă</label>
            <input type="password" id="new_password" name="new_password" autoComplete="new-password" />
          </div>
          <div className="form-group">
            <label htmlFor="confirm_password">Confirmă parola</label>
            <input type="password" id="confirm_password" name="confirm_password" autoComplete="new-password" />
          </div>
          <button type="submit" className="btn btn-primary">
            Schimbă parola
          </button>
        </form>
        <p className="form-desc" style={{ marginTop: 12 }}>
          Parola se schimbă pentru contul tău ({session.username}) în baza Neon.
        </p>
      </div>

      <SyncToken token={str("sync_token")} />
    </>
  );
}
