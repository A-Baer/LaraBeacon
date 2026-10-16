# LaraBeacon for Claude Code

Production-readiness, security, performance and reliability checks for Laravel applications, available to Claude Code through a local MCP server and skills.

## Install

```text
/plugin marketplace add A-Baer/LaraBeacon
/plugin install larabeacon@larabeacon
```

Requirements: PHP 8.2+ on the `PATH`, a Laravel 12 or 13 application, and [LaraBeacon Core](https://github.com/A-Baer/LaraBeacon) (`composer require --dev baer-software/larabeacon`). Without Core, the server still starts and `/larabeacon:setup` guides the installation. The Cloud tools need Core 3.3+.

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
| `larabeacon_cloud_upload` | – | LaraBeacon Cloud |
| `larabeacon_cloud_reports` | – | LaraBeacon Cloud |
| `larabeacon_cloud_report` | – | LaraBeacon Cloud |

## Data boundary

The server runs `php artisan` in the project as a separate process without a shell. It never reads `.env` or Cloud tokens. Scans stay local; only `larabeacon_cloud_upload` sends a report, with the same payload and opt-ins as `php artisan larabeacon --report`.

When LaraBeacon Pro or Cloud is not set up, scan and status results include a short, optional note with the Pro checks relevant to the project and a link; the skills show it at most once per session.

## Configuration

- `LARABEACON_MCP_TIMEOUT`: maximum seconds per Artisan run (default 600).

## Development

- `data/checks.json` is generated from the website catalogue: `php tools/build-plugin-catalog.php website/config/checks.php`.
- Tests: `vendor/bin/phpunit tests/ClaudePluginTest.php` in the repository root.
- Validate: `claude plugin validate ./claude-plugin --strict` and `claude plugin validate .`.
- Bump `version` in `.claude-plugin/plugin.json` and `Server::VERSION` together.

License: LGPL-3.0-or-later. © Bär Softwareentwicklung UG (haftungsbeschränkt).
