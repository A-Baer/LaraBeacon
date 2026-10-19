# LaraBeacon for Claude Code

Production-readiness, security, performance and reliability checks for Laravel applications, available to Claude Code through a local MCP server, three skills and a short session hint. Claude runs [LaraBeacon](https://larabeacon.com/claude-code) in your project, explains the findings, fixes the ones that belong to your current change and can gate new findings in CI.

## Install

From the Claude directory, or with the plugin marketplace in Claude Code:

```text
/plugin marketplace add Baer-Software/larabeacon-claude-plugin
/plugin install larabeacon@larabeacon
```

Requirements:

- PHP 8.2 or newer available as `php` on the `PATH` that Claude Code starts with
- A Laravel 12 or 13 application
- [LaraBeacon Core](https://github.com/A-Baer/LaraBeacon), the free LGPL package: `composer require --dev baer-software/larabeacon`. Without it the plugin still starts and `/larabeacon:setup` guides the installation. The Cloud tools need Core 3.3 or newer.

## Skills

| Skill | Purpose |
| --- | --- |
| `/larabeacon:scan [profile] [checks]` | Scan, fix findings related to the current change within scope, rerun the affected checks |
| `/larabeacon:setup [core\|pro\|cloud]` | Install Core; explain the Pro and Cloud setup without asking for secrets in chat |
| `/larabeacon:cloud [upload\|latest\|number]` | Upload a scan on request, list reports, review new and resolved findings |

## MCP tools

| Tool | Changes files | Network |
| --- | --- | --- |
| `larabeacon_status` | – | Packagist version lookup, at most once a day |
| `larabeacon_scan` | – | Dependency audits: package names and versions to Packagist and the npm or Yarn registry (see "Data and network") |
| `larabeacon_preflight` | – | – |
| `larabeacon_checks` | – | – |
| `larabeacon_baseline` | one JSON baseline file you name | Dependency audits, as `larabeacon_scan` |
| `larabeacon_update` | `composer.lock`, `vendor/` | Composer repositories configured in the project |
| `larabeacon_cloud_upload` | – | LaraBeacon Cloud (larabeacon.com), plus the dependency audits of the scan |
| `larabeacon_cloud_reports` | – | LaraBeacon Cloud (larabeacon.com) |
| `larabeacon_cloud_report` | – | LaraBeacon Cloud (larabeacon.com) |

Claude Code asks before each tool call unless you allow the tools. The skills ask you before `larabeacon_update` and only upload when you ask for an upload.

## Data and network

- **Runs locally.** The MCP server is a dependency-free PHP script. It starts `php artisan` and `composer` in your project as separate processes without a shell (on Windows Composer is started through `cmd.exe`). Scans read your code and configuration on your machine and upload no code or results.
- **Dependency audits during scans.** LaraBeacon's vulnerable-dependency checks run `composer audit` and, when the project has a frontend lockfile, `npm`, `pnpm` or `yarn audit`. These send the names and versions of your installed packages, not your code, to Packagist's security advisory API and the npm or Yarn registry. To skip them, add `BaerSoftware\LaraBeacon\Analyzers\Security\VulnerableDependencyAnalyzer` and `BaerSoftware\LaraBeacon\Analyzers\Security\FrontendVulnerableDependencyAnalyzer` to `exclude_analyzers` in `config/larabeacon.php`; checks you request explicitly by name still run.
- **No telemetry.** The plugin collects no usage data and sends nothing about you or your project to Bär Software unless you upload a report.
- **Version check.** `larabeacon_status` asks Packagist (repo.packagist.org) for the newest public LaraBeacon release at most once a day and caches the answer. The request contains no project data. Set `LARABEACON_UPDATE_CHECK=0` to disable it.
- **Cloud uploads only on request.** `larabeacon_cloud_upload` sends one scan report to LaraBeacon Cloud with the project credentials configured in your application (`LARABEACON_CLOUD_*` in `.env`): project metadata, check results, file paths, line numbers and finding details. Source snippets and exception stack traces are excluded unless you enable them. The Cloud read tools fetch your project's report history with the same credentials. The plugin never reads `.env` or the token itself; Core does that inside your application. Details: [Cloud reporting](https://larabeacon.com/docs/cloud-reporting) and the [privacy policy](https://larabeacon.com/privacy).
- **Composer.** `larabeacon_update` runs `composer update` for the LaraBeacon packages, which contacts the Composer repositories your project already uses.
- **Production profile.** Only a scan with `profile: "production"` runs the checks that request your application's configured URL (`APP_URL`, or `LARABEACON_GUEST_URL`) to inspect HTTP headers and that connect to your database, cache or Redis. Use it only on a production-like environment.

## Product information in results

LaraBeacon Core is free. When LaraBeacon Pro or Cloud is not set up, the first status or scan result of a session includes a short, labelled note with the Pro checks that apply to the project and a link. The skills show it at most once per session, after the results, and never present Pro checks as findings.

## Configuration

Set these in the `env` block of `~/.claude/settings.json` or the project's `.claude/settings.json`:

- `LARABEACON_MCP_TIMEOUT`: maximum seconds per Artisan or Composer run (default 600).
- `LARABEACON_UPDATE_CHECK=0`: never contact Packagist.
- `LARABEACON_AUTO_UPDATE=1`: update Core (and Pro) within the `composer.json` constraint once per session without asking. Off by default.
- `LARABEACON_COMPOSER`: path to Composer when `composer` is not on the `PATH`.

## Platforms

Tested on macOS with PHP 8.2, 8.4 and 8.5 and Laravel 12 and 13. Windows is supported by design but not yet tested: the hook runs through Git Bash and Composer is started through `cmd.exe`.

## Support

Questions, bugs and false positives: [GitHub issues](https://github.com/Baer-Software/larabeacon-claude-plugin/issues) or anthony.baer@baer-software.com. Documentation: [larabeacon.com/docs/claude-code](https://larabeacon.com/docs/claude-code).

## License

LGPL-3.0-or-later, see [LICENSE.md](LICENSE.md). © Bär Softwareentwicklung UG (haftungsbeschränkt), Bolongarostraße 101H, 65929 Frankfurt am Main.

This repository is published from the plugin source in [A-Baer/LaraBeacon](https://github.com/A-Baer/LaraBeacon/tree/main/claude-plugin); please send code changes there.
