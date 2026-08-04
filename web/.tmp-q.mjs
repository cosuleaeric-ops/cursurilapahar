import { neon } from "@neondatabase/serverless";
const sql = neon(process.env.DATABASE_URL);
console.log(await sql`SELECT column_name, data_type FROM information_schema.columns WHERE table_name='feedback' ORDER BY ordinal_position`);
console.log(await sql`SELECT count(*)::int AS n FROM feedback`);
console.log(JSON.stringify(await sql`SELECT * FROM feedback LIMIT 3`, null, 1));
