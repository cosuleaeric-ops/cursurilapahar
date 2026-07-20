import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Root explicit: repo-ul are și un package-lock.json în rădăcină (app PHP legacy),
  // așa că Turbopack ar ghici greșit root-ul fără asta.
  turbopack: { root: import.meta.dirname },
  // Upload imagini hero la rezoluție mare prin server action.
  experimental: { serverActions: { bodySizeLimit: "25mb" } },
  // Biblioteca de imagini listează public/assets/images cu fs la runtime.
  outputFileTracingIncludes: {
    "/admin/imagini": ["./public/assets/images/**"],
  },
  // Upload-urile făcute pe PHP-ul live nu există în web/public — până la
  // comutarea domeniului le servim de pe site-ul live (doar dacă lipsesc local).
  async rewrites() {
    return {
      beforeFiles: [],
      afterFiles: [],
      fallback: [
        {
          source: "/assets/images/uploads/:path*",
          destination: "https://cursurilapahar.ro/assets/images/uploads/:path*",
        },
      ],
    };
  },
};

export default nextConfig;
