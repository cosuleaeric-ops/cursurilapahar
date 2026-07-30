import { neon } from "@neondatabase/serverless";

const sql = neon(process.env.DATABASE_URL);

// Bilete de intrare la spectacole — regim HG 846/2002 + art. 481 Cod fiscal.
// Pool-ul se vizează la primărie ÎNAINTE de vânzare, deci biletele se generează
// toate odată (status 'liber') și vânzarea doar le atribuie. Nu se alocă serii
// la momentul plății: ce nu e vizat nu se poate vinde.

await sql`
  CREATE TABLE IF NOT EXISTS ticket_types (
    id serial PRIMARY KEY,
    event_id integer NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    name text NOT NULL,
    price numeric(10,2) NOT NULL,
    stock integer NOT NULL,
    serie char(3) NOT NULL,
    position integer NOT NULL DEFAULT 0,
    created_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (event_id, serie)
  )
`;

// Un rând = un bilet numerotat. `numar` merge de la 1 la stock, afișat 0001.
await sql`
  CREATE TABLE IF NOT EXISTS ticket_pool (
    id serial PRIMARY KEY,
    event_id integer NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    type_id integer NOT NULL REFERENCES ticket_types(id) ON DELETE CASCADE,
    serie char(3) NOT NULL,
    numar integer NOT NULL,
    status text NOT NULL DEFAULT 'liber',
    buyer_name text,
    buyer_email text,
    qr_token text,
    sold_at timestamptz,
    used_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (event_id, serie, numar)
  )
`;

await sql`CREATE UNIQUE INDEX IF NOT EXISTS ticket_pool_qr ON ticket_pool (qr_token) WHERE qr_token IS NOT NULL`;
await sql`CREATE INDEX IF NOT EXISTS ticket_pool_event_status ON ticket_pool (event_id, status)`;

// Cota de impozit pe spectacole: 2% la spectacolele din art. 481 alin. (1),
// 5% la cele „cu caracter ocazional" (alin. 2). Se setează per eveniment.
await sql`ALTER TABLE events ADD COLUMN IF NOT EXISTS impozit_cota numeric(4,2) NOT NULL DEFAULT 2`;
// % din încasări reprezentând timbre culturale, scăzut din baza impozabilă.
await sql`ALTER TABLE events ADD COLUMN IF NOT EXISTS timbru_cota numeric(4,2) NOT NULL DEFAULT 0`;
// Când s-a depus cererea de vizare la primărie (blochează regenerarea pool-ului).
await sql`ALTER TABLE events ADD COLUMN IF NOT EXISTS vizat_at timestamptz`;

console.log("OK — ticket_types, ticket_pool, coloane events");
