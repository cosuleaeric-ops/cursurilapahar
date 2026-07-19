import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { saveKit, saveBrevo, saveHeadScripts, changePassword } from "./actions";
import QuickLinksEditor, { type QuickLink } from "./QuickLinksEditor";

export const dynamic = "force-dynamic";

export default async function SetariPage({
  searchParams,
}: {
  searchParams: Promise<{ saved?: string; error?: string }>;
}) {
  const session = await getSession();
  if (!session) redirect("/login");
  if (session.role !== "owner") redirect("/admin");

  const { saved, error } = await searchParams;
  const rows = (await sql`
    SELECT key, value FROM settings
    WHERE key IN ('quick_links', 'kit_api_key', 'kit_form_id', 'brevo_api_key', 'head_scripts')
  `) as { key: string; value: unknown }[];
  const s = Object.fromEntries(rows.map((r) => [r.key, r.value]));
  const str = (k: string) => (typeof s[k] === "string" ? (s[k] as string) : "");
  const quickLinks = Array.isArray(s.quick_links) ? (s.quick_links as QuickLink[]) : [];

  return (
    <>
      <h1 className="wp-page-title">Setări</h1>

      {saved && <div className="notice notice-success">Setările au fost salvate.</div>}
      {error && <div className="notice notice-error">Parolele nu coincid sau sunt prea scurte (minim 6 caractere).</div>}

      <div className="card">
        <div className="card-title">🔗 Linkuri rapide — Dashboard</div>
        <p style={{ fontSize: 13, color: "var(--text-muted)", marginBottom: 16 }}>
          Aceste linkuri apar ca butoane în partea de sus a dashboard-ului.
        </p>
        <QuickLinksEditor links={quickLinks} />
      </div>

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
              <span style={{ color: "#d63638" }}>⚠ Codul este inserat fără filtrare — adaugă doar scripturi de încredere.</span>
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
    </>
  );
}
