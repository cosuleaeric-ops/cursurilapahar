#!/usr/bin/env bash
# Sincronizeaza datele din productie in data/ local.
# Necesita .sync-token (gitignored) cu URL-ul si tokenul:
#   SYNC_URL=https://cursurilapahar.ro/admin/sync-export.php
#   SYNC_TOKEN=xxxxxxxxxxxx

set -euo pipefail

cd "$(dirname "$0")"

if [ ! -f .sync-token ]; then
    echo "Lipseste .sync-token. Creeaza-l cu:"
    echo "  SYNC_URL=https://cursurilapahar.ro/admin/sync-export.php"
    echo "  SYNC_TOKEN=<tokenul-de-pe-server>"
    exit 1
fi

# shellcheck disable=SC1091
source .sync-token

: "${SYNC_URL:?SYNC_URL not set in .sync-token}"
: "${SYNC_TOKEN:?SYNC_TOKEN not set in .sync-token}"

mkdir -p data
TMP=$(mktemp)
trap 'rm -f "$TMP"' EXIT

echo "Pulling bundle from $SYNC_URL ..."
HTTP_CODE=$(curl -sS -o "$TMP" -w "%{http_code}" -H "X-Sync-Token: $SYNC_TOKEN" "$SYNC_URL")

if [ "$HTTP_CODE" != "200" ]; then
    echo "Esec: HTTP $HTTP_CODE"
    cat "$TMP"
    exit 1
fi

# Extrage fiecare key in propriul fisier JSON, daca nu e null
python3 <<PY
import json, os
with open("$TMP") as f:
    bundle = json.load(f)
mapping = {
    "settings":       "data/settings.json",
    "courses":        "data/courses.json",
    "vote_courses":   "data/vote_courses.json",
    "speakers":       "data/speakers.json",
    "locations":      "data/locations.json",
    "collaborations": "data/collaborations.json",
}
written = []
for key, path in mapping.items():
    val = bundle.get(key)
    if val is None:
        continue
    with open(path, "w", encoding="utf-8") as f:
        json.dump(val, f, ensure_ascii=False, indent=4)
    written.append(path)
print("OK ({}). Files: {}".format(bundle.get("exported_at", "?"), ", ".join(written)))
PY
