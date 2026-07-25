"use client";

import { usePathname } from "next/navigation";

/** Linkul „Editează pagina" apare doar pe /cursuri-posibile, ca în bar.php. */
export default function AdminBarEditLink() {
  const path = usePathname();
  if (!path?.startsWith("/cursuri-posibile")) return null;
  return <a href="/admin/cursuri-posibile">✏️ Editează pagina</a>;
}
