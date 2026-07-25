"use client";

export type VC = {
  id: number;
  name: string;
  emoji: string | null;
  description: string | null;
  likes: number;
  views: number;
  active: boolean;
};

/** clp_format_vote_conversion(): o zecimală cu virgulă, „—" când nu sunt vizite. */
function conversion(likes: number, views: number): string {
  if (views <= 0) return "—";
  return `${(Math.round((likes / views) * 1000) / 10).toFixed(1).replace(".", ",")}%`;
}

export default function VoteRows({
  list,
  toggle,
  remove,
}: {
  list: VC[];
  toggle: (fd: FormData) => void | Promise<void>;
  remove: (fd: FormData) => void | Promise<void>;
}) {
  return (
    <>
      {list.map((vc) => {
        const conv = conversion(vc.likes, vc.views);
        return (
          <tr key={vc.id} style={vc.active ? undefined : { opacity: 0.45 }}>
            <td style={{ fontSize: "1.4rem", textAlign: "center" }}>{vc.emoji || "📚"}</td>
            <td style={{ fontWeight: 600 }}>
              {vc.name}
              {!vc.active && (
                <span style={{ fontSize: 11, color: "var(--text-muted)", fontWeight: 400, marginLeft: 6 }}>
                  (dezactivat)
                </span>
              )}
              {vc.description && (
                <div
                  style={{
                    fontSize: 12,
                    color: "var(--text-muted)",
                    fontWeight: 400,
                    marginTop: 2,
                    whiteSpace: "nowrap",
                    overflow: "hidden",
                    textOverflow: "ellipsis",
                    maxWidth: 360,
                  }}
                >
                  {vc.description.slice(0, 80)}…
                </div>
              )}
            </td>
            <td
              style={{
                textAlign: "center",
                fontVariantNumeric: "tabular-nums",
                ...(vc.views === 0 ? { color: "var(--text-muted)" } : {}),
              }}
            >
              {vc.views}
            </td>
            <td style={{ textAlign: "center" }}>
              <span className="likes-badge">❤️ {vc.likes}</span>
            </td>
            <td
              title="Voturi ÷ vizite"
              style={{
                textAlign: "center",
                fontVariantNumeric: "tabular-nums",
                ...(conv === "—" ? { color: "var(--text-muted)", fontWeight: 400 } : { fontWeight: 600 }),
              }}
            >
              {conv}
            </td>
            <td>
              <div className="row-actions">
                <a href={`/admin/voturi?edit=${vc.id}`} className="btn btn-sm btn-secondary">
                  Editează
                </a>
                <form action={toggle} style={{ display: "inline" }}>
                  <input type="hidden" name="id" value={vc.id} />
                  <button type="submit" className={`btn btn-sm ${vc.active ? "btn-secondary" : "btn-primary"}`}>
                    {vc.active ? "Dezactivează" : "Activează"}
                  </button>
                </form>
                <form
                  action={remove}
                  onSubmit={(e) => {
                    if (!confirm("Ștergi această idee de curs?")) e.preventDefault();
                  }}
                  style={{ display: "inline" }}
                >
                  <input type="hidden" name="id" value={vc.id} />
                  <button type="submit" className="btn btn-sm btn-danger">
                    Șterge
                  </button>
                </form>
              </div>
            </td>
          </tr>
        );
      })}
    </>
  );
}
