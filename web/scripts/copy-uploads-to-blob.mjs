// Copiază uploads-urile de pe Hostico (live) în Vercel Blob, păstrând numele
// (uploads/<name>), + /favicon.png → uploads/favicon.png. Idempotent: sare peste
// fișierele deja existente în Blob cu aceeași mărime.
//
// Rulare (din web/):
//   node --env-file=.env.local --env-file=../migration/.env scripts/copy-uploads-to-blob.mjs

import { list, put } from "@vercel/blob";

const LIVE = "https://cursurilapahar.ro";
const UA = { "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36" };

const syncUrl = process.env.SYNC_URL;
if (!syncUrl) throw new Error("SYNC_URL lipsă (migration/.env)");
if (!process.env.BLOB_READ_WRITE_TOKEN) throw new Error("BLOB_READ_WRITE_TOKEN lipsă (.env.local)");

const bundle = await (await fetch(syncUrl, { headers: UA })).json();
const files = bundle.uploads_list ?? [];
if (!files.length) throw new Error("uploads_list gol — verifică sync-export pe live");

const existing = new Map();
for await (const page of listAll()) {
  for (const b of page) existing.set(b.pathname, b.size);
}
async function* listAll() {
  let cursor;
  do {
    const res = await list({ prefix: "uploads/", cursor, limit: 1000 });
    yield res.blobs;
    cursor = res.cursor;
  } while (cursor);
}

let copied = 0, skipped = 0, failed = 0, bytes = 0;

async function copy(srcUrl, destPath, expectedSize) {
  if (existing.has(destPath) && (expectedSize == null || existing.get(destPath) === expectedSize)) {
    skipped++;
    return;
  }
  const res = await fetch(srcUrl, { headers: UA });
  if (!res.ok) {
    console.error(`  ✗ ${srcUrl} → HTTP ${res.status}`);
    failed++;
    return;
  }
  const buf = Buffer.from(await res.arrayBuffer());
  await put(destPath, buf, {
    access: "public",
    addRandomSuffix: false,
    contentType: res.headers.get("content-type") ?? undefined,
  });
  copied++;
  bytes += buf.length;
  console.log(`  ✓ ${destPath} (${(buf.length / 1024).toFixed(0)} KB)`);
}

for (const f of files) {
  await copy(`${LIVE}/assets/images/uploads/${encodeURIComponent(f.name)}`, `uploads/${f.name}`, f.size);
}
await copy(`${LIVE}/favicon.png`, "uploads/favicon.png", null);

console.log(`\nGata: ${copied} copiate (${(bytes / 1048576).toFixed(1)} MB), ${skipped} existente, ${failed} eșuate din ${files.length + 1}.`);
if (failed) process.exit(1);
