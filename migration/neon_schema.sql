-- ============================================================================
-- Curs la Pahar — schema Neon (Postgres)  [v2 — model curat, termen lung]
-- Migrare Hostico (PHP + SQLite + JSON) -> Vercel + Neon
--
-- Sursele mapate:
--   SQLite clp.sqlite  -> ticketing + fișiere + rapoarte
--   SQLite pnl.sqlite  -> P&L (venituri/cheltuieli/categorii)
--   data/*.json (14)   -> conținut site, voturi, A/B, marketing, ops
--
-- PRINCIPII (rescriere completă => alegem modelul corect, nu cel de compromis):
--  1. Chei surogat BIGINT IDENTITY peste tot. ID-urile string opace din JSON
--     ("c69e...", "sp69...") NU devin PK — se păstrează ca `legacy_id`/`slug`
--     doar pentru trasabilitate la migrare și URL-uri.
--  2. Un curs = O entitate: `events` (unifică site card + ancora de ticketing).
--  3. Bani: NUMERIC(10,2). Timp: TIMESTAMPTZ (fusul se face în app).
--  4. Categoriile de cheltuieli/venituri sunt FK, nu string liber.
--  5. Fișierele binare stau pe Vercel Blob; în Neon rămâne doar metadata + URL.
-- ============================================================================


-- ============================================================================
-- CORE — evenimentul (unificat: card public + ancoră ticketing/stats)
-- ============================================================================
CREATE TABLE events (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    slug                TEXT UNIQUE,                 -- din livetickets, pt URL & matching
    legacy_card_id      TEXT,                        -- fost courses.json id (trasabilitate)
    external_id         TEXT UNIQUE,                 -- fost clp.courses.external_id

    title               TEXT NOT NULL,
    starts_at           TIMESTAMPTZ,                 -- date_raw + time unificate
    location            TEXT,
    livetickets_url     TEXT,
    image_url           TEXT,                        -- URL (Blob/CDN), nu fișier local

    active              BOOLEAN NOT NULL DEFAULT false,  -- afișat pe site
    sold_out            BOOLEAN NOT NULL DEFAULT false,  -- fost soldout_cache.json
    sold_out_checked_at TIMESTAMPTZ,
    clicks              INTEGER NOT NULL DEFAULT 0,       -- fost course_clicks.json
    viza_done           BOOLEAN NOT NULL DEFAULT false,   -- din clp.courses.viza_done
    position            INTEGER,

    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE tickets (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    event_id         BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    participant_name TEXT NOT NULL,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- metadata fișiere (binarul e pe Vercel Blob -> `blob_url`)
CREATE TABLE event_files (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    event_id      BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    blob_url      TEXT NOT NULL,                -- înlocuiește `filename` local
    original_name TEXT NOT NULL,
    file_type     TEXT NOT NULL DEFAULT 'viza',
    uploaded_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE event_reports (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    event_id       BIGINT NOT NULL UNIQUE REFERENCES events(id) ON DELETE CASCADE,
    total_bilete   NUMERIC(10,2) NOT NULL DEFAULT 0,
    total_incasari NUMERIC(10,2) NOT NULL DEFAULT 0,
    blob_url       TEXT,                        -- fost filename local
    original_name  TEXT NOT NULL DEFAULT '',
    types_json     JSONB NOT NULL DEFAULT '[]',
    uploaded_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE viza_subtips (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    event_id   BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    seria      TEXT NOT NULL,
    tarif      NUMERIC(10,2) NOT NULL,
    nr_unitati INTEGER NOT NULL,
    de_la      TIMESTAMPTZ NOT NULL,
    pana_la    TIMESTAMPTZ NOT NULL
);


-- ============================================================================
-- CONȚINUT SITE
-- ============================================================================
CREATE TABLE locations (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    legacy_id  TEXT,
    name       TEXT NOT NULL,
    phone      TEXT,
    maps_link  TEXT,
    days       TEXT,
    notes      TEXT,
    position   INTEGER,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- CRM colaborări (branduri/parteneri) — tab admin, sursă collaborations.json
CREATE TABLE collaborations (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    legacy_id    TEXT,
    name         TEXT NOT NULL,
    contact      TEXT,
    contact_info TEXT,
    status       TEXT,
    notes        TEXT,
    position     INTEGER,
    updated_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE speakers (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    legacy_id  TEXT,
    name       TEXT NOT NULL,
    email      TEXT,
    phone      TEXT,
    status     TEXT,                          -- RECURENT / URMEAZĂ / etc.
    notes      TEXT,
    topics     TEXT[] NOT NULL DEFAULT '{}',  -- fost speakers[].courses (teme)
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- config site (60 chei heterogene, valori string sau array) -> key/value JSONB
CREATE TABLE settings (
    key        TEXT PRIMARY KEY,
    value      JSONB NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);


-- ============================================================================
-- VOTARE & EXPERIMENTE A/B
-- ============================================================================
CREATE TABLE vote_courses (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    legacy_id   TEXT,
    name        TEXT NOT NULL,
    emoji       TEXT,
    description TEXT,
    likes       INTEGER NOT NULL DEFAULT 0,
    active      BOOLEAN NOT NULL DEFAULT true,
    position    INTEGER,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ab_button.json + ab_headline.json unificate
-- experiment = 'button' | 'headline'; variant = 'A'/'B'/'C'/'on'/...
CREATE TABLE ab_experiments (
    experiment  TEXT NOT NULL,
    variant     TEXT NOT NULL,
    views       INTEGER NOT NULL DEFAULT 0,
    conversions INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (experiment, variant)
);


-- ============================================================================
-- P&L  (pnl.sqlite) — categorii ca FK
-- ============================================================================
CREATE TABLE venit_categorii (
    id   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nume TEXT NOT NULL UNIQUE
);

CREATE TABLE cheltuiala_categorii (
    id   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nume TEXT NOT NULL UNIQUE
);

CREATE TABLE venituri (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    data         DATE NOT NULL,
    descriere    TEXT NOT NULL,
    suma         NUMERIC(10,2) NOT NULL,
    categorie_id BIGINT REFERENCES venit_categorii(id),   -- opțional (sursa nu-l avea)
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE cheltuieli (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    data         DATE NOT NULL,
    descriere    TEXT NOT NULL,
    suma         NUMERIC(10,2) NOT NULL,
    categorie_id BIGINT NOT NULL REFERENCES cheltuiala_categorii(id),
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
    -- migrare: string `categorie` din SQLite -> upsert în cheltuiala_categorii, apoi FK
);


-- ============================================================================
-- OPS / ADMIN
-- ============================================================================
CREATE TABLE users (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'admin',
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE todos (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    legacy_id   TEXT,
    title       TEXT NOT NULL,
    assigned_to TEXT,
    completed   BOOLEAN NOT NULL DEFAULT false,
    created_by  TEXT,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE recurring_tasks (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    legacy_id   TEXT,                          -- 'sys_pc_1' etc.
    type        TEXT NOT NULL,                 -- 'system' / ...
    system_key  TEXT,                          -- 'post_course' / ...
    assigned_to TEXT,
    title       TEXT NOT NULL,
    schedule    TEXT,
    description TEXT
);

CREATE TABLE marketing_sections (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    slug       TEXT UNIQUE,                    -- 'video', ...
    title      TEXT NOT NULL,
    is_default BOOLEAN NOT NULL DEFAULT false,
    position   INTEGER
);

CREATE TABLE marketing_items (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    section_id BIGINT NOT NULL REFERENCES marketing_sections(id) ON DELETE CASCADE,
    payload    JSONB NOT NULL DEFAULT '{}',    -- shape încă neînghețat (items: [])
    position   INTEGER,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);


-- mesaje din formularele publice (contact + colaborări)
-- category: contact / sustine (speakeri) / gazduieste (locații) / parteneriat
CREATE TABLE messages (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    category   TEXT NOT NULL,
    name       TEXT,
    email      TEXT,
    payload    JSONB NOT NULL DEFAULT '{}',   -- toate câmpurile formularului
    read       BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);


-- ============================================================================
-- INDECȘI
-- ============================================================================
CREATE INDEX idx_messages_category     ON messages(category);
CREATE INDEX idx_events_starts_at      ON events(starts_at);
CREATE INDEX idx_events_active         ON events(active);
CREATE INDEX idx_tickets_event         ON tickets(event_id);
CREATE INDEX idx_event_files_event     ON event_files(event_id);
CREATE INDEX idx_viza_subtips_event    ON viza_subtips(event_id);
CREATE INDEX idx_venituri_data         ON venituri(data);
CREATE INDEX idx_cheltuieli_data       ON cheltuieli(data);
CREATE INDEX idx_cheltuieli_categorie  ON cheltuieli(categorie_id);
CREATE INDEX idx_speakers_topics_gin   ON speakers USING GIN (topics);
