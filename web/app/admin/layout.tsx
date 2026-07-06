import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import { logout } from "./actions";
import AdminNav from "./AdminNav";

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const session = await getSession();
  if (!session) redirect("/login");
  const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);

  return (
    <>
      <link rel="stylesheet" href="/assets/css/admin.css" />
      <header className="wp-header">
        <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
          <a href="/admin" className="brand">
            Cursuri la Pahar <span>— Admin</span>
          </a>
          <a href="/" className="wp-header-site-link">
            🌐 Vezi site
          </a>
        </div>
        <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
          <span style={{ fontSize: 12, color: "#a0aec0" }}>
            {cap(session.username)} · {session.role}
          </span>
          <form action={logout} style={{ margin: 0 }}>
            <button type="submit" className="btn-logout">
              Deconectează-te
            </button>
          </form>
        </div>
      </header>

      <AdminNav role={session.role} />

      <div className="wp-layout">
        <main className="wp-main">
          <div className="bc-doc">{children}</div>
        </main>
      </div>
    </>
  );
}
