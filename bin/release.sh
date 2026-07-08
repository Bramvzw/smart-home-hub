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
# Machine-specific values live in an untracked .release.env at the repo root:
#   NAS_SSH=user@nas-host
#   HEALTH_URL=http://nas-host:8080/
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
[ -f "$REPO_ROOT/.release.env" ] && . "$REPO_ROOT/.release.env"
NAS_SSH="${NAS_SSH:?Set NAS_SSH (user@host) in .release.env or the environment}"
NAS_KEY="${NAS_KEY:-$HOME/.ssh/id_ed25519}"
HEALTH_URL="${HEALTH_URL:?Set HEALTH_URL in .release.env or the environment}"
PROJECT="/volume1/docker/smart-home-hub"
COMPOSE="/var/packages/ContainerManager/target/usr/bin/docker-compose"
DC="$COMPOSE -f $PROJECT/docker-compose.yml --project-directory $PROJECT"

MSG="${1:-release: $(date '+%Y-%m-%d %H:%M')}"

# repo root (this script lives in <root>/bin)
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

step() { printf '\n\033[36m==> %s\033[0m\n' "$1"; }

# ---- semantic version: bump the minor of the latest v* tag ----------------
LAST_TAG="$(git describe --tags --abbrev=0 --match 'v*' 2>/dev/null || echo v0.0.0)"
VERSION="$(printf '%s' "$LAST_TAG" | awk -F. '{sub(/^v/, "", $1); printf "v%d.%d.0", $1, $2 + 1}')"

step "1/7  Building front-end assets"
npm run build

step "2/7  Rolling CHANGELOG.md into $VERSION"
if awk '/^## \[Unreleased\]/{found=1; next} /^## \[/{exit} found && NF {exit 42}' CHANGELOG.md; then
  echo "    WARNING: no entries under [Unreleased] — release notes will be empty"
else
  perl -0pi -e "s/^## \[Unreleased\]/## [Unreleased]\n\n## [${VERSION#v}] - $(date '+%Y-%m-%d')/m" CHANGELOG.md
  perl -0pi -e "s{^\[Unreleased\]: (.*)/compare/.*\.\.\.HEAD$}{[Unreleased]: \$1/compare/$VERSION...HEAD\n[${VERSION#v}]: \$1/compare/$LAST_TAG...$VERSION}m" CHANGELOG.md
  echo "    CHANGELOG rolled to ${VERSION#v}"
fi

step "3/7  Committing"
git add -A
if git diff --cached --quiet; then
  echo "    no changes to commit"
else
  git commit -m "$MSG"
fi

step "4/7  Tagging $VERSION and pushing to GitHub (main)"
git tag -a "$VERSION" -m "$MSG"
git push origin main "$VERSION"

if command -v gh >/dev/null 2>&1; then
  notes="$(awk "/^## \[${VERSION#v}\]/{found=1; next} found && /^## \[/{exit} found" CHANGELOG.md)"
  gh release create "$VERSION" --title "$VERSION" --notes "$notes" || echo "    WARNING: gh release failed (continuing)"
fi

step "5/7  NAS: pull + migrate + optimize (in container)"
# Synology sprinkles @eaDir metadata dirs across the volume, incl. .git/refs,
# which makes git choke on "bad object refs/heads/@eaDir/...". Strip them first.
ssh -i "$NAS_KEY" "$NAS_SSH" \
  "sudo $DC exec -T hub sh -c 'cd /app && (git config --global --get-all safe.directory 2>/dev/null | grep -qx /app || git config --global --add safe.directory /app) && find .git -name @eaDir -prune -exec rm -rf {} + 2>/dev/null; git checkout -- docker-compose.yml 2>/dev/null; git pull --ff-only origin main && composer install --no-dev --optimize-autoloader --no-interaction && php artisan migrate --force && php artisan optimize'"

step "6/7  NAS: restart container (reset opcache)"
ssh -i "$NAS_KEY" "$NAS_SSH" "sudo $DC restart hub"

step "7/7  Health check"
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
