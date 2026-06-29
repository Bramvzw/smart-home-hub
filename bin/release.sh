#!/usr/bin/env bash
#
# One-button release for the Smart Home Hub.
#
#   make release            # auto commit message with timestamp
#   make release m="..."    # custom commit message
#
# Pipeline (run from your Mac):
#   1. build front-end assets
#   2. commit + push to GitHub (history/backup)
#   3. NAS pulls the new code INSIDE the container (public repo, no token)
#   4. migrate --force + optimize (build caches with the new code)
#   5. restart the container (resets the frozen opcache so new code goes live)
#   6. health check
#
set -euo pipefail

# ---- config (single-NAS setup) -------------------------------------------
NAS_SSH="${NAS_SSH:-bramvzw@192.168.178.250}"
NAS_KEY="${NAS_KEY:-$HOME/.ssh/id_ed25519}"
HEALTH_URL="${HEALTH_URL:-http://192.168.178.250:8080/}"
PROJECT="/volume1/docker/smart-home-hub"
COMPOSE="/var/packages/ContainerManager/target/usr/bin/docker-compose"
DC="$COMPOSE -f $PROJECT/docker-compose.yml --project-directory $PROJECT"

MSG="${1:-release: $(date '+%Y-%m-%d %H:%M')}"

# repo root (this script lives in <root>/bin)
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

step() { printf '\n\033[36m==> %s\033[0m\n' "$1"; }

step "1/6  Building front-end assets"
npm run build

step "2/6  Committing"
git add -A
if git diff --cached --quiet; then
  echo "    no changes to commit"
else
  git commit -m "$MSG"
fi

step "3/6  Pushing to GitHub (main)"
git push origin main

step "4/6  NAS: pull + migrate + optimize (in container)"
# Synology sprinkles @eaDir metadata dirs across the volume, incl. .git/refs,
# which makes git choke on "bad object refs/heads/@eaDir/...". Strip them first.
ssh -i "$NAS_KEY" "$NAS_SSH" \
  "sudo $DC exec -T hub sh -c 'cd /app && (git config --global --get-all safe.directory 2>/dev/null | grep -qx /app || git config --global --add safe.directory /app) && find .git -name @eaDir -prune -exec rm -rf {} + 2>/dev/null; git pull --ff-only origin main && php artisan migrate --force && php artisan optimize'"

step "5/6  NAS: restart container (reset opcache)"
ssh -i "$NAS_KEY" "$NAS_SSH" "sudo $DC restart hub"

step "6/6  Health check"
sleep 3
code="$(curl -s -o /dev/null -m 20 -w '%{http_code}' "$HEALTH_URL" || echo 000)"
if [ "$code" = "200" ]; then
  printf '\033[32m    OK — %s returned 200\033[0m\n' "$HEALTH_URL"
  printf '\033[32m==> RELEASE DONE\033[0m\n'
else
  printf '\033[31m    WARNING — health check returned %s\033[0m\n' "$code"
  echo "    Check logs:  ssh $NAS_SSH \"sudo $DC logs --tail=80 hub\""
  exit 1
fi
