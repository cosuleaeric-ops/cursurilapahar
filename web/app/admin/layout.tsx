import Link from "next/link";
import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import { logout } from "./actions";
import styles from "./admin.module.css";

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const session = await getSession();
  if (!session) redirect("/login");

  return (
    <div className={styles.shell}>
      <header className={styles.header}>
        <div className={styles.left}>
          <span className={styles.brand}>Admin · Curs la Pahar</span>
          <nav className={styles.nav}>
            <Link className={styles.navLink} href="/admin">
              Dashboard
            </Link>
            <Link className={styles.navLink} href="/admin/cursuri">
              Cursuri
            </Link>
            <Link className={styles.navLink} href="/admin/speakeri">
              Speakeri
            </Link>
          </nav>
        </div>
        <div className={styles.userbox}>
          <span className={styles.user}>
            {session.username} <span className={styles.role}>{session.role}</span>
          </span>
          <form action={logout}>
            <button className={styles.logout} type="submit">
              Ieși
            </button>
          </form>
        </div>
      </header>
      <main className={styles.main}>{children}</main>
    </div>
  );
}
