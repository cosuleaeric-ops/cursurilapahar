"use client";

import { Fragment, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";

// Linkuri simple + două grupuri pliabile („Organizare" și „Site"). To-dos rămâne
// doar pe dashboard.
type Item = { href: string; label: string; exact?: boolean; owner?: boolean };
type Group = { label: string; items: Item[] };
type Entry = Item | Group;

const isGroup = (e: Entry): e is Group => "items" in e;

const NAV: Entry[] = [
  { href: "/admin", label: "Dashboard", exact: true },
  { href: "/admin/cursuri", label: "Cursuri" },
  { href: "/admin/mesaje", label: "Mesaje" },
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
    // layout-nav.php:72-77 — exact 4 linkuri; „Cursuri posibile" e doar în harta de
    // breadcrumb-uri (:98), nu în nav.
    items: [
      { href: "/admin/voturi", label: "Voturi" },
      { href: "/admin/imagini", label: "Imagini" },
      { href: "/admin/aspect", label: "Aspect" },
      { href: "/admin/ab", label: "Test A/B" },
    ],
  },
  // Meta Ads (/admin/meta-ads) și Videoclipuri (/admin/videoclipuri) rămân
  // accesibile direct pe URL, dar scoase din meniu.
  { href: "/admin/pnl", label: "P&L", owner: true },
  { href: "/admin/setari", label: "Setări", owner: true },
];

// admin/statistici/layout_nav.php:8-13 — orice pagină de sub /admin/statistici care
// nu e pnl rulează cu $tab='dashboard'. În port: Test A/B (ab_headline.php) și
// detaliile unui curs (statistici/cursuri/view.php).
const STATS_PAGES = [/^\/admin\/ab$/, /^\/admin\/cursuri\/[^/]+\/detalii$/];
export const isStatsPage = (path: string) => STATS_PAGES.some((re) => re.test(path));

export default function AdminNav({ role }: { role: string }) {
  const path = usePathname();
  const [open, setOpen] = useState<Record<string, boolean>>({});
  // layout-nav.php:53 + :58 — pe Test A/B se aprind simultan „Dashboard" (tab-ul e
  // 'dashboard') și linkul „Test A/B". Detaliile unui curs rămân pe „Cursuri", care
  // acum are link propriu în meniu.
  const abActive = path === "/admin/ab";
  const isActive = (t: Item) => {
    if (t.href === "/admin/ab") return abActive;
    if (t.exact) return path === t.href || abActive;
    // Fără boundary, „/admin/cursuri" ar aprinde și „/admin/cursuri-posibile".
    return path === t.href || path.startsWith(`${t.href}/`);
  };

  return (
    <aside className="wp-sidebar">
      <nav>
        {NAV.map((entry) => {
          if (isGroup(entry)) {
            const items = entry.items.filter((t) => !t.owner || role === "owner");
            if (!items.length) return null;
            // Grupul stă închis, dar se deschide singur pe pagina lui.
            const isOpen = open[entry.label] ?? items.some(isActive);
            return (
              <Fragment key={entry.label}>
                <div
                  className={`sidebar-section collapsible${isOpen ? "" : " collapsed"}`}
                  onClick={() => setOpen((o) => ({ ...o, [entry.label]: !isOpen }))}
                >
                  {entry.label}
                </div>
                <div className={`sidebar-collapse-content${isOpen ? "" : " collapsed"}`}>
                  {items.map((t) => (
                    <Link key={t.href} href={t.href} className={isActive(t) ? "active" : ""}>
                      {t.label}
                    </Link>
                  ))}
                </div>
              </Fragment>
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
    </aside>
  );
}
