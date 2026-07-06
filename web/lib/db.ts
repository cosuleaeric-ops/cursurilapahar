import { neon } from "@neondatabase/serverless";

if (!process.env.DATABASE_URL) {
  throw new Error("DATABASE_URL lipsește (vezi web/.env.local)");
}

// Client HTTP Neon — ideal pentru funcții serverless pe Vercel.
export const sql = neon(process.env.DATABASE_URL);
