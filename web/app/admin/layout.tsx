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
        <span className={styles.brand}>Admin · Curs la Pahar</span>
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
