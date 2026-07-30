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
  // layout-nav.php:53 + :58 — pe paginile de statistici se aprind simultan „Dashboard"
  // (tab-ul e 'dashboard') și „Test A/B" (deci și triggerul „Site", :55).
  const abActive = isStatsPage(path);
  const isActive = (t: Item) => {
    if (t.href === "/admin/ab") return abActive;
    if (t.exact) return path === t.href || abActive;
    return path.startsWith(t.href);
  };

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
