#!/usr/bin/env bash
# Publish the committed claude-plugin/ folder of this repository to the standalone plugin repository that the
# Claude directory and the plugin marketplace read. Usage: tools/publish-claude-plugin.sh [--dry-run]
set -Eeuo pipefail

readonly TARGET_REPOSITORY='git@github.com:Baer-Software/larabeacon-claude-plugin.git'
readonly SOURCE_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
dry_run=0
[[ "${1:-}" == '--dry-run' ]] && dry_run=1

cd "$SOURCE_ROOT"

# Publish exactly what is committed and pushed, never local working-tree files.
[[ -z "$(git status --porcelain -- claude-plugin)" ]] || { echo 'ERROR: claude-plugin/ has uncommitted or untracked changes.' >&2; exit 1; }
git fetch --quiet origin main
[[ "$(git rev-parse HEAD)" == "$(git rev-parse origin/main)" ]] || { echo 'ERROR: HEAD is not the pushed origin/main.' >&2; exit 1; }

source_commit="$(git rev-parse HEAD)"
version="$(php -r 'echo json_decode(file_get_contents("claude-plugin/.claude-plugin/plugin.json"), true)["version"];')"
[[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo 'ERROR: plugin.json has no valid version.' >&2; exit 1; }

work="$(mktemp -d)"
trap 'rm -rf -- "$work"' EXIT

git clone --quiet "$TARGET_REPOSITORY" "$work/target"
git -C "$work/target" ls-remote --exit-code --tags origin "refs/tags/v$version" >/dev/null 2>&1 \
    && { echo "ERROR: v$version is already published; bump the plugin version first." >&2; exit 1; }

# Replace everything except .git with the committed plugin folder (file modes such as the hook's +x are kept).
find "$work/target" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf -- {} +
git archive --format=tar "HEAD:claude-plugin" | tar -x -C "$work/target"

# The repository is its own marketplace, so `/plugin marketplace add Baer-Software/larabeacon-claude-plugin` works.
cat > "$work/target/.claude-plugin/marketplace.json" <<'JSON'
{
  "$schema": "https://json.schemastore.org/claude-code-marketplace.json",
  "name": "larabeacon",
  "description": "LaraBeacon plugins for Claude Code.",
  "owner": {
    "name": "Bär Softwareentwicklung UG (haftungsbeschränkt)",
    "email": "anthony.baer@baer-software.com",
    "url": "https://larabeacon.com"
  },
  "plugins": [
    {
      "name": "larabeacon",
      "source": "./",
      "description": "Run LaraBeacon production-readiness scans for Laravel from Claude Code, fix findings within scope and read LaraBeacon Cloud report history.",
      "category": "development",
      "tags": ["laravel", "php", "security", "performance", "reliability"]
    }
  ]
}
JSON

# Validating the folder only checks marketplace.json once it exists, so validate the plugin manifest explicitly.
claude plugin validate "$work/target/.claude-plugin/plugin.json" --strict
claude plugin validate "$work/target" --strict

git -C "$work/target" add -A
if git -C "$work/target" diff --cached --quiet; then
    echo "No file changes since the last publication; tagging the current state as v$version."
else
    git -C "$work/target" commit --quiet -m "Publish LaraBeacon plugin $version" -m "Source: A-Baer/LaraBeacon@$source_commit (claude-plugin/)"
fi
git -C "$work/target" tag -a "v$version" -m "LaraBeacon plugin $version"

if [[ "$dry_run" == 1 ]]; then
    git -C "$work/target" show --stat --oneline HEAD | head -40
    echo "Dry run: nothing pushed."
    exit 0
fi

git -C "$work/target" push --quiet --atomic origin HEAD:main "v$version"
echo "Published LaraBeacon plugin $version from $source_commit."
