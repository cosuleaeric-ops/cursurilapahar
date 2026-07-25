"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

// Structura din admin/partials/layout-nav.php: linkuri simple + două grupuri cu
// dropdown („Organizare" și „Site"). Cursuri, To-dos și Mesaje se accesează din
// dashboard, ca pe site-ul live.
type Item = { href: string; label: string; exact?: boolean; owner?: boolean };
type Group = { label: string; items: Item[] };
type Entry = Item | Group;

const isGroup = (e: Entry): e is Group => "items" in e;

const NAV: Entry[] = [
  { href: "/admin", label: "Dashboard", exact: true },
  { href: "/admin/marketing", label: "Marketing" },
  { href: "/admin/speakeri", label: "Speakeri" },
  {
    label: "Organizare",
    items: [
      { href: "/admin/locatii", label: "Locații" },
      { href: "/admin/colaborari", label: "Colaborări" },
    ],
  },
  {
    label: "Site",
    items: [
      { href: "/admin/voturi", label: "Voturi" },
      { href: "/admin/imagini", label: "Imagini" },
      { href: "/admin/aspect", label: "Aspect" },
      { href: "/admin/cursuri-posibile", label: "Cursuri posibile" },
      { href: "/admin/ab", label: "Test A/B" },
    ],
  },
  { href: "/admin/pnl", label: "P&L", owner: true },
  { href: "/admin/setari", label: "Setări", owner: true },
];

export default function AdminNav({ role }: { role: string }) {
  const path = usePathname();
  const isActive = (t: Item) => (t.exact ? path === t.href : path.startsWith(t.href));

  return (
    <nav className="bc-botnav">
      {NAV.map((entry) => {
        if (isGroup(entry)) {
          const items = entry.items.filter((t) => !t.owner || role === "owner");
          if (!items.length) return null;
          return (
            <div className="bc-nav-group" key={entry.label}>
              <span className={`bc-nav-trigger${items.some(isActive) ? " active" : ""}`}>{entry.label}</span>
              <div className="bc-nav-dropdown">
                {items.map((t) => (
                  <Link key={t.href} href={t.href} className={isActive(t) ? "active" : ""}>
                    {t.label}
                  </Link>
                ))}
              </div>
            </div>
          );
        }
        if (entry.owner && role !== "owner") return null;
        return (
          <Link key={entry.href} href={entry.href} className={isActive(entry) ? "active" : ""}>
            {entry.label}
          </Link>
        );
      })}
    </nav>
  );
}
