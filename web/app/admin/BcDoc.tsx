"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";
import { isStatsPage } from "./AdminNav";

// Wrapper-ul de conținut din admin/partials/layout-nav.php: pe dashboard clasa
// `bc-doc--home`, pe restul paginilor breadcrumb-ul „Dashboard".
// Include și scurtăturile de tastatură: C = Cursuri, M = Mesaje, D = Dashboard.
export default function BcDoc({ children }: { children: React.ReactNode }) {
  const path = usePathname();
  // layout-nav.php:90 — $__bc_is_home ține de $tab, iar paginile de sub /admin/statistici
  // rulează tot cu $tab='dashboard', deci nici ele nu au breadcrumb.
  const isHome = path === "/admin" || isStatsPage(path);
  const router = useRouter();

  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      const t = e.target as HTMLElement | null;
      if (t && (t.tagName === "INPUT" || t.tagName === "TEXTAREA" || t.tagName === "SELECT" || t.isContentEditable))
        return;
      const k = e.key.toLowerCase();
      if (k === "c") router.push("/admin/cursuri");
      if (k === "m") router.push("/admin/mesaje");
      if (k === "d") router.push("/admin");
    }
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [router]);

  return (
    <div className={`bc-doc${isHome ? " bc-doc--home" : ""}`}>
      {!isHome && (
        <div className="bc-doc-top">
          <Link href="/admin" className="bc-home-link">
            Dashboard
          </Link>
        </div>
      )}
      {children}
    </div>
  );
}
