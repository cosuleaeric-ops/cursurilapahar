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
  // Upload-urile vechi de pe PHP au fost copiate în Blob (scripts/copy-uploads-to-blob.mjs);
  // referințele /assets/images/uploads/* din settings rămân valabile prin fallback.
  async rewrites() {
    return {
      beforeFiles: [],
      afterFiles: [],
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
