#!/bin/sh
# SessionStart hook: adds one line of LaraBeacon context in Laravel projects and prints nothing elsewhere.
# It never fails the session; a missing or outdated PHP is explained instead of surfacing a shell error.

hook_directory=$(dirname "$0")

if command -v php >/dev/null 2>&1; then
    php "$hook_directory/session-start.php" 2>/dev/null || true
elif [ -f "${CLAUDE_PROJECT_DIR:-.}/artisan" ]; then
    echo "The LaraBeacon plugin could not find PHP on the PATH, so its MCP tools are unavailable in this session. If LaraBeacon comes up, tell the user to make PHP 8.2+ available as \`php\` on the PATH Claude Code starts with (for example through Homebrew, Herd or the system package manager), then restart Claude Code."
fi

exit 0
