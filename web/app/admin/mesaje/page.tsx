import { sql } from "@/lib/db";
import { toggleRead, deleteMessage } from "./actions";

export const dynamic = "force-dynamic";

type Msg = {
  id: number;
  category: string;
  name: string | null;
  email: string | null;
  payload: Record<string, unknown>;
  read: boolean;
  created_at: string;
};

const CATS: { key: string; label: string; icon: string }[] = [
  { key: "contact", label: "Contact", icon: "💬" },
  { key: "sustine", label: "Speakeri", icon: "🎤" },
  { key: "gazduieste", label: "Locații", icon: "📍" },
  { key: "parteneriat", label: "Parteneriate", icon: "🤝" },
];

const dtFmt = new Intl.DateTimeFormat("ro-RO", { timeZone: "Europe/Bucharest", day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });

function MessageCard({ m }: { m: Msg }) {
  const fields = Object.entries(m.payload ?? {}).filter(([, v]) => String(v ?? "").trim() !== "");
  return (
    <div
      style={{
        border: "1px solid var(--border)",
        borderLeft: `3px solid ${m.read ? "var(--border)" : "var(--accent)"}`,
        borderRadius: 10,
        padding: "12px 14px",
        marginBottom: 10,
        background: m.read ? "var(--surface)" : "var(--accent-soft)",
      }}
    >
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", gap: 10 }}>
        <div style={{ minWidth: 0 }}>
          <span style={{ fontWeight: 700, fontSize: 14 }}>{m.name || m.email || "—"}</span>
          {m.email && <span style={{ fontSize: 12, color: "var(--text-muted)", marginLeft: 8 }}>{m.email}</span>}
        </div>
        <span style={{ fontSize: 11, color: "var(--text-muted)", whiteSpace: "nowrap", flexShrink: 0 }}>
          {dtFmt.format(new Date(m.created_at))}
        </span>
      </div>
      {fields.map(([k, v]) => (
        <div key={k} style={{ fontSize: 13, color: "var(--text)", marginTop: 6, whiteSpace: "pre-wrap" }}>
          {String(v)}
        </div>
      ))}
      <div style={{ display: "flex", gap: 6, marginTop: 10 }}>
        <form action={toggleRead} style={{ margin: 0 }}>
          <input type="hidden" name="id" value={m.id} />
          <button type="submit" className="btn btn-sm btn-secondary">
            {m.read ? "Marchează necitit" : "Marchează citit"}
          </button>
        </form>
        <form action={deleteMessage} style={{ margin: 0 }}>
          <input type="hidden" name="id" value={m.id} />
          <button type="submit" className="btn btn-sm btn-danger">
            Șterge
          </button>
        </form>
      </div>
    </div>
  );
}

export default async function MesajePage() {
  const all = (await sql`
    SELECT id, category, name, email, payload, read, created_at
    FROM messages ORDER BY created_at DESC
  `) as Msg[];

  const totalUnread = all.filter((m) => !m.read).length;

  return (
    <>
      <h1 className="wp-page-title">Mesaje</h1>

      {all.length === 0 ? (
        <div className="card">
          <p style={{ color: "var(--text-muted)", margin: 0 }}>Niciun mesaj încă.</p>
        </div>
      ) : (
        CATS.map((cat) => {
          const msgs = all.filter((m) => m.category === cat.key);
          if (msgs.length === 0) return null;
          const unread = msgs.filter((m) => !m.read).length;
          return (
            <div className="card" key={cat.key}>
              <div className="card-title">
                {cat.icon} {cat.label} ({msgs.length}){unread > 0 ? ` · ${unread} necitite` : ""}
              </div>
              {msgs.map((m) => (
                <MessageCard key={m.id} m={m} />
              ))}
            </div>
          );
        })
      )}

      {all.length > 0 && totalUnread === 0 && (
        <p style={{ color: "var(--text-muted)", fontSize: 13, textAlign: "center" }}>Toate mesajele sunt citite. ✅</p>
      )}
    </>
  );
}
