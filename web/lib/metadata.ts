import type { Metadata } from "next";

// Meta OG/Twitter pentru paginile publice. `openGraph`/`twitter` sunt înlocuite
// integral de ultimul segment care le definește (nu se merge-uiesc), deci fiecare
// pagină trebuie să le construiască complet — de aici helper-ul.

const OG_IMAGE = {
  url: "/assets/images/og-image.jpg?v=2",
  width: 1200,
  height: 630,
  alt: "Cursuri la Pahar – curs ținut într-un bar plin din București",
};

export function pageMetadata(opts: {
  title: string;
  description: string;
  path: string;
  ogTitle?: string;
  ogDescription?: string;
  // Pe PHP twitter:description e scris separat și e adesea mai scurt decât
  // og:description (ex. parteneri.php:44 vs :52, cursuri-posibile.php:37 vs :45).
  twitterDescription?: string;
  // parteneri.php:46 și :53 folosesc og-image.jpg FĂRĂ `?v=2`.
  ogImage?: string;
}): Metadata {
  const ogTitle = opts.ogTitle ?? opts.title;
  const ogDescription = opts.ogDescription ?? opts.description;
  const twitterDescription = opts.twitterDescription ?? ogDescription;
  const imageUrl = opts.ogImage ?? OG_IMAGE.url;
  return {
    metadataBase: new URL("https://cursurilapahar.ro"),
    title: opts.title,
    description: opts.description,
    openGraph: {
      type: "website",
      siteName: "Cursuri la Pahar",
      locale: "ro_RO",
      title: ogTitle,
      description: ogDescription,
      url: opts.path,
      images: [{ ...OG_IMAGE, url: imageUrl }],
    },
    twitter: {
      card: "summary_large_image",
      title: ogTitle,
      description: twitterDescription,
      images: [imageUrl],
    },
  };
}
