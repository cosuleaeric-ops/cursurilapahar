import { getRealSession } from "@/lib/auth";
import { logout } from "@/app/admin/actions";
import AdminBarEditLink from "./AdminBarEditLink";

// Bara de admin de pe paginile publice — port din admin/bar.php.
// Apare doar când ești logat; împinge conținutul cu 32px (navbar-ul coboară).
const CSS = `
#clp-adminbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 99999;
    height: 32px; background: #1d2327; color: #a7aaad;
    display: flex; align-items: center; gap: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.4);
}
#clp-adminbar a, #clp-adminbar button.bar-link {
    color: #a7aaad; text-decoration: none; padding: 0 12px;
    height: 100%; display: flex; align-items: center; gap: 5px;
    border-right: 1px solid rgba(255,255,255,.07); transition: background .15s, color .15s;
    white-space: nowrap; font-size: 12px;
    background: none; border-top: none; border-bottom: none; border-left: none; cursor: pointer; font-family: inherit;
}
#clp-adminbar a:hover, #clp-adminbar button.bar-link:hover { background: #2c3338; color: #fff; }
#clp-adminbar .bar-brand { font-weight: 600; color: #fff; }
#clp-adminbar .bar-sep { flex: 1; }
#clp-adminbar .bar-logout { border-right: none; border-left: 1px solid rgba(255,255,255,.07); }
/* bar.php:43-44 — cu bara prezentă conținutul coboară la 120px (32px bară + 88px navbar).
   În port padding-ul stă pe wrapper-ul din (site)/layout.tsx, nu pe <body>. */
body:has(#clp-adminbar) .clp-site-shell { padding-top: 120px !important; }
body:has(#clp-adminbar) .navbar { top: 32px !important; }
`;

export default async function AdminBar() {
  // bar.php:16-21 — bara se randează doar pentru userul din cookie cu rol 'owner'
  // (rolul REAL, impersonarea nu contează: bar.php citește direct clp_auth).
  const real = await getRealSession();
  if (real?.role !== "owner") return null;

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: CSS }} />
      <div id="clp-adminbar">
        <a href="/admin" className="bar-brand">
          ⚙ Admin
        </a>
        <a href="/admin/cursuri">📋 Cursuri</a>
        <a href="/admin/aspect">🎨 Aspect</a>
        <a href="/admin/imagini">🖼 Imagini</a>
        <a href="/admin/mesaje">💬 Mesaje</a>
        <a href="/admin/voturi">❤️ Vot</a>
        <AdminBarEditLink />
        <span className="bar-sep"></span>
        <form action={logout} style={{ margin: 0, height: "100%" }}>
          <button type="submit" className="bar-link bar-logout">
            Logout
          </button>
        </form>
      </div>
    </>
  );
}
