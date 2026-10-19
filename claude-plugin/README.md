# LaraBeacon for Claude Code

Production-readiness, security, performance and reliability checks for Laravel applications, available to Claude Code through a local MCP server and skills.

## Install

```text
/plugin marketplace add A-Baer/LaraBeacon
/plugin install larabeacon@larabeacon
```

Requirements: PHP 8.2+ on the `PATH` (or `LARABEACON_PHP`), a Laravel 12 or 13 application, and [LaraBeacon Core](https://github.com/A-Baer/LaraBeacon) (`composer require --dev baer-software/larabeacon`). Without Core, the server still starts and `/larabeacon:setup` guides the installation. The Cloud tools need Core 3.3+.

## Skills

| Skill | Purpose |
| --- | --- |
| `/larabeacon:scan [profile] [checks]` | Scan, fix findings related to the current change within scope, rerun affected checks |
| `/larabeacon:setup [core\|pro\|cloud]` | Install Core; explain Pro and Cloud setup without secrets in chat |
| `/larabeacon:cloud [upload\|latest\|number]` | Upload on request, list reports, review new and resolved findings |

## MCP tools

| Tool | Writes | Network |
| --- | --- | --- |
| `larabeacon_status` | – | – |
| `larabeacon_scan` | – | – |
| `larabeacon_preflight` | – | – |
| `larabeacon_checks` | – | – |
| `larabeacon_baseline` | one JSON file | – |
| `larabeacon_update` | `composer.lock`, `vendor/` | Packagist / Composer repositories |
| `larabeacon_cloud_upload` | – | LaraBeacon Cloud |
| `larabeacon_cloud_reports` | – | LaraBeacon Cloud |
| `larabeacon_cloud_report` | – | LaraBeacon Cloud |

## Data boundary

The server runs `php artisan` in the project as a separate process without a shell. It never reads `.env` or Cloud tokens. Scans stay local; only `larabeacon_cloud_upload` sends a report, with the same payload and opt-ins as `php artisan larabeacon --report`.

When LaraBeacon Pro or Cloud is not set up, scan and status results include a short, optional note with the Pro checks relevant to the project and a link; the skills show it at most once per session.

## Configuration

- `LARABEACON_MCP_TIMEOUT`: maximum seconds per Artisan or Composer run (default 600).
- `LARABEACON_AUTO_UPDATE=1`: update Core (and Pro) within the `composer.json` constraint once per session before the first status or scan, without asking. Off by default.
- `LARABEACON_UPDATE_CHECK=0`: never contact Packagist for the newest version.
- `LARABEACON_PHP`: PHP binary for the MCP server and the session hint when `php` on the `PATH` is missing or too old (for example a Herd or Homebrew path).
- `LARABEACON_COMPOSER`: path to Composer when `composer` is not on the `PATH`.

Set them in the `env` block of `~/.claude/settings.json` or the project's `.claude/settings.json`.

## Updates

`larabeacon_status` compares the installed Core version with the newest release on Packagist (cached for one day) and the session hint mentions an available update. Claude asks before calling `larabeacon_update`, which runs `composer update baer-software/larabeacon [baer-software/larabeacon-pro] --with-dependencies` and lists every package that changed in `composer.lock`. New major versions are reported but never applied.

## Platforms

Tested on macOS with PHP 8.2, 8.4 and 8.5, Laravel 12 and 13. Windows is supported by design but not yet tested: hooks run through Git Bash, Composer is started through `cmd.exe`, and paths with drive letters are rejected as absolute in tool arguments.

## Development

- `data/checks.json` is generated from the website catalogue: `php tools/build-plugin-catalog.php website/config/checks.php`.
- Tests: `vendor/bin/phpunit tests/ClaudePluginTest.php` in the repository root.
- Validate: `claude plugin validate ./claude-plugin --strict` and `claude plugin validate .`.
- Bump `version` in `.claude-plugin/plugin.json` and `Server::VERSION` together.

License: LGPL-3.0-or-later. © Bär Softwareentwicklung UG (haftungsbeschränkt).
