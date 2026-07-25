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
}): Metadata {
  const ogTitle = opts.ogTitle ?? opts.title;
  const ogDescription = opts.ogDescription ?? opts.description;
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
      images: [OG_IMAGE],
    },
    twitter: {
      card: "summary_large_image",
      title: ogTitle,
      description: ogDescription,
      images: [OG_IMAGE.url],
    },
  };
}
