import type { Metadata } from "next";
import Link from "next/link";
import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import { metaToken } from "@/lib/meta";
import { createCampaignAction } from "./actions";
import SubmitButton from "../SubmitButton";

export const dynamic = "force-dynamic";
export const metadata: Metadata = { title: "Campanie nouă — Meta Ads" };

const LABEL: React.CSSProperties = {
  display: "block",
  fontSize: 10,
  textTransform: "uppercase",
  letterSpacing: ".05em",
  color: "var(--text-muted)",
  fontWeight: 700,
  marginBottom: 4,
};

export default async function CampanieNouaPage({ searchParams }: { searchParams: Promise<{ err?: string }> }) {
  const session = await getSession();
  if (!session) redirect("/login");
  if (session.role !== "owner") redirect("/admin");
  if (!(await metaToken())) redirect("/admin/meta-ads");
  const { err } = await searchParams;

  return (
    <>
      <div style={{ marginBottom: 14, fontSize: 13 }}>
        <Link href="/admin/meta-ads">← Toate campaniile</Link>
      </div>

      {err && (
        <div className="card" style={{ borderLeft: "4px solid #d63638" }}>
          <strong>Eroare:</strong> {err}
        </div>
      )}

      <form action={createCampaignAction}>
        <div className="card">
          <div className="card-title">✨ Campanie nouă</div>
          <p style={{ fontSize: 13, marginBottom: 16 }}>
            Copiază configurația validată (audiențele, optimizarea pe checkout, București +40 km, îmbunătățirile AI
            oprite) și schimbă doar ce e specific cursului. Campania se creează <strong>pe pauză</strong> — o pornești
            din listă după ce o verifici.
          </p>

          <div style={{ display: "flex", gap: 14, flexWrap: "wrap", marginBottom: 14 }}>
            <div style={{ flex: "2 1 300px" }}>
              <label style={LABEL}>Nume campanie</label>
              <input type="text" name="name" required style={{ width: "100%" }} />
            </div>
            <div style={{ flex: "0 1 120px" }}>
              <label style={LABEL}>Buget (lei/zi)</label>
              <input type="number" name="budget_lei" defaultValue={40} min={1} step={1} style={{ width: "100%" }} />
            </div>
          </div>

          <div style={{ marginBottom: 14 }}>
            <label style={LABEL}>Link LiveTickets</label>
            <input type="url" name="link" required style={{ width: "100%" }} />
          </div>

          <div style={{ marginBottom: 14 }}>
            <label style={LABEL}>Afiș (1080×1350, PNG/JPG)</label>
            <input type="file" name="image" accept="image/png,image/jpeg" required />
          </div>

          {[1, 2, 3].map((i) => (
            <div key={i} style={{ marginBottom: 14 }}>
              <label style={LABEL}>
                Text principal {i} {i > 1 && "(opțional)"}
              </label>
              <textarea name={`body${i}`} rows={5} required={i === 1} style={{ width: "100%", fontSize: 13, lineHeight: 1.5 }} />
            </div>
          ))}

          <div style={{ display: "flex", gap: 14, flexWrap: "wrap", marginBottom: 14 }}>
            {[1, 2, 3].map((i) => (
              <div key={i} style={{ flex: "1 1 220px" }}>
                <label style={LABEL}>
                  Titlu {i} {i > 1 && "(opțional)"}
                </label>
                <input type="text" name={`title${i}`} maxLength={255} required={i === 1} style={{ width: "100%" }} />
              </div>
            ))}
          </div>

          <div style={{ marginBottom: 18 }}>
            <label style={LABEL}>Descriere (sub titlu)</label>
            <input type="text" name="description" maxLength={255} style={{ width: "100%" }} />
          </div>

          <SubmitButton className="btn btn-primary" label="Creează campania (pe pauză)" pendingLabel="Se creează…" />
          <p style={{ fontSize: 12, color: "var(--text-muted)", marginTop: 10 }}>
            Durează 10-20 de secunde: se urcă imaginea și se creează campania, setul și reclama prin API.
          </p>
        </div>
      </form>
    </>
  );
}
