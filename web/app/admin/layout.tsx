import { redirect } from "next/navigation";
import { sql } from "@/lib/db";
import { getRealSession, getSession } from "@/lib/auth";
import { logout } from "./actions";
import AdminNav from "./AdminNav";
import UserSwitcher from "./UserSwitcher";
import BcDoc from "./BcDoc";

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const real = await getRealSession();
  if (!real) redirect("/login");
  const session = (await getSession())!;
  const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);

  let users: string[] = [];
  if (real.role === "owner") {
    const rows = (await sql`SELECT username FROM users ORDER BY id`) as { username: string }[];
    users = rows.map((r) => r.username);
  }

  return (
    <div data-theme="corporate">
      {/* Aceleași stiluri ca admin/index.php — DaisyUI + Tailwind (fără preflight) + Coloris */}
      <link href="https://cdn.jsdelivr.net/npm/daisyui@4/dist/full.min.css" rel="stylesheet" />
      <script dangerouslySetInnerHTML={{ __html: "tailwind={config:{corePlugins:{preflight:false}}}" }} />
      <script src="https://cdn.tailwindcss.com"></script>
      <link rel="stylesheet" href="/assets/css/coloris.min.css" />
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
        {/* layout-nav.php:13-49 — header-ul are TREI copii direcți (brand, user, logout),
            deci space-between așază blocul de user la mijlocul barei. */}
        {real.role === "owner" ? (
          <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
            <UserSwitcher realUsername={real.username} viewUsername={session.username} users={users} />
          </div>
        ) : (
          // layout-nav.php:47 — non-owner-ul vede doar numele, cu prima literă mare.
          <span style={{ fontSize: 12, color: "#a0aec0" }}>{cap(session.username)}</span>
        )}
        <form action={logout} style={{ margin: 0 }}>
          <button type="submit" className="btn-logout">
            Deconectează-te
          </button>
        </form>
      </header>

      <AdminNav role={session.role} />

      <div className="wp-layout">
        <main className="wp-main">
          <BcDoc>{children}</BcDoc>
        </main>
      </div>
    </div>
  );
}
