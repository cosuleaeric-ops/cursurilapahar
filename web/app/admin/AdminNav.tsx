"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const TABS = [
  { href: "/admin", label: "Dashboard", exact: true },
  { href: "/admin/marketing", label: "Marketing" },
  { href: "/admin/cursuri", label: "Cursuri" },
  { href: "/admin/speakeri", label: "Speakeri" },
  { href: "/admin/locatii", label: "Locații" },
  { href: "/admin/voturi", label: "Voturi" },
  { href: "/admin/colaborari", label: "Colaborări" },
  { href: "/admin/pnl", label: "P&L", owner: true },
  { href: "/admin/setari", label: "Setări", owner: true },
];

export default function AdminNav({ role }: { role: string }) {
  const path = usePathname();
  return (
    <nav className="bc-botnav">
      {TABS.filter((t) => !t.owner || role === "owner").map((t) => {
        const active = t.exact ? path === t.href : path.startsWith(t.href);
        return (
          <Link key={t.href} href={t.href} className={active ? "active" : ""}>
            {t.label}
          </Link>
        );
      })}
    </nav>
  );
}
