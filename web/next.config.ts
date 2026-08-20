import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Root explicit: repo-ul are și un package-lock.json în rădăcină (app PHP legacy),
  // așa că Turbopack ar ghici greșit root-ul fără asta.
  turbopack: { root: import.meta.dirname },
  // Upload imagini hero la rezoluție mare prin server action.
  experimental: { serverActions: { bodySizeLimit: "25mb" } },
  // pdfjs face require("canvas") pentru randare — noi extragem doar text, deci îl
  // lăsăm extern ca bundler-ul să nu încerce să rezolve dependința opțională.
  serverExternalPackages: ["pdfjs-dist"],
  // Biblioteca de imagini listează public/assets/images cu fs la runtime.
  outputFileTracingIncludes: {
    "/admin/imagini": ["./public/assets/images/**"],
  },
  // URL vechi indexat, redirecționat 301 și în .htaccess-ul PHP.
  async redirects() {
    return [
      { source: "/sustine-un-curs", destination: "/prezinta-un-curs", statusCode: 301 },
      // Votarea temelor e scoasă din meniu și închisă publicului; pagina și datele
      // rămân în cod, doar accesul e redirecționat (temporar, deci 307).
      { source: "/voteaza-cursuri", destination: "/", permanent: false },
    ];
  },
  // Upload-urile vechi de pe PHP au fost copiate în Blob (scripts/copy-uploads-to-blob.mjs);
  // referințele /assets/images/uploads/* din settings rămân valabile prin fallback.
  // API-ul PostHog cere căi CU slash final (/ingest/e/).
  skipTrailingSlashRedirect: true,
  async rewrites() {
    return {
      beforeFiles: [],
      // PostHog proxy-uit same-origin, ca adblockerele să nu taie telemetria.
      afterFiles: [
        {
          source: "/ingest/static/:path*",
          destination: "https://eu-assets.i.posthog.com/static/:path*",
        },
        {
          source: "/ingest/:path*",
          destination: "https://eu.i.posthog.com/:path*",
        },
      ],
      fallback: [
        {
          source: "/assets/images/uploads/:path*",
          destination: "https://jn2ztrmmqtkkwxv6.public.blob.vercel-storage.com/uploads/:path*",
        },
        {
          source: "/favicon.png",
          destination: "https://jn2ztrmmqtkkwxv6.public.blob.vercel-storage.com/uploads/favicon.png",
        },
      ],
    };
  },
};

export default nextConfig;
