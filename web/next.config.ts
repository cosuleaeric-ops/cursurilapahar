import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Root explicit: repo-ul are și un package-lock.json în rădăcină (app PHP legacy),
  // așa că Turbopack ar ghici greșit root-ul fără asta.
  turbopack: { root: import.meta.dirname },
};

export default nextConfig;
