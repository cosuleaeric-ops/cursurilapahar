// Liniile unei comenzi, ca sursă de adevăr separată de rezervare: dacă o
// încercare de plată eșuată eliberează biletele, tot mai știm ce trebuie emis
// când vine confirmarea.
import { neon } from "@neondatabase/serverless";
import { readFileSync } from "node:fs";

const url = /^DATABASE_URL=["']?(.+?)["']?$/m.exec(readFileSync(".env.local", "utf8"))[1];
const sql = neon(url);

await sql`
  CREATE TABLE IF NOT EXISTS order_items (
    id serial PRIMARY KEY,
    order_id integer NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    type_id integer NOT NULL REFERENCES ticket_types(id),
    qty integer NOT NULL,
    unit_price numeric NOT NULL
  )
`;
await sql`CREATE INDEX IF NOT EXISTS order_items_order_idx ON order_items (order_id)`;
console.log("order_items:", await sql`SELECT column_name FROM information_schema.columns WHERE table_name='order_items' ORDER BY ordinal_position`);
