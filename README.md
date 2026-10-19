# LaraBeacon

[![tests](https://github.com/A-Baer/LaraBeacon/actions/workflows/tests.yaml/badge.svg)](https://github.com/A-Baer/LaraBeacon/actions/workflows/tests.yaml)

LaraBeacon checks Laravel applications for production-readiness problems across performance, security, and reliability. It is a maintained fork of the open-source [Enlightn](https://github.com/enlightn/enlightn) package.

## Compatibility

| LaraBeacon | PHP | Laravel | Larastan | PHPStan |
| --- | --- | --- | --- | --- |
| `3.x` / `dev-main` | 8.2–8.5 | 12–13 | 3.x | 2.x |

Laravel 13 requires PHP 8.3 or newer.

## Installation

```bash
composer require --dev baer-software/larabeacon:^3.0
```

Publish the configuration if you want to customize analyzer selection or baselines:

```bash
php artisan vendor:publish --tag=larabeacon
```

## Usage

Run all applicable checks:

```bash
php artisan larabeacon
```

Useful options:

```bash
php artisan larabeacon --details
php artisan larabeacon --ci
php artisan larabeacon:baseline
```

Run an individual analyzer by passing its fully qualified class name:

```bash
php artisan larabeacon 'BaerSoftware\LaraBeacon\Analyzers\Security\CSRFAnalyzer'
```

The framework and PHP support checks fail when security support for the installed Laravel or PHP release ends within `support_warning_days` (default: 180 days), so upgrades can be planned early. They run in default and `--profile=production` scans, not in CI mode or the `local`/`ci` profiles, because CI runtimes rarely match production.

Some analyzers inspect runtime-specific configuration. Run LaraBeacon in a production-like environment for a complete result. Use `--ci` for checks that are safe and deterministic in continuous integration.

To inspect headers on a deployed application while running LaraBeacon locally, provide a full guest URL:

```bash
LARABEACON_GUEST_URL=https://example.com/login php artisan larabeacon
```

License findings identify packages that need project-specific review; they do not prove a violation. Add licensed proprietary packages to `commercial_packages` in the published configuration instead of globally allowing the `proprietary` license label.

## AI coding agents

### Claude Code plugin

Install the LaraBeacon plugin in Claude Code:

```text
/plugin marketplace add Baer-Software/larabeacon-claude-plugin
/plugin install larabeacon@larabeacon
```

The plugin adds a local MCP server (`larabeacon_scan`, `larabeacon_status`, `larabeacon_checks`, `larabeacon_baseline` and read-only Cloud history tools) and the skills `/larabeacon:scan`, `/larabeacon:setup` and `/larabeacon:cloud`. It needs PHP 8.2+ on the `PATH`, runs every scan through the project's own `php artisan`, never reads Cloud credentials and uploads only when you ask. See [claude-plugin/README.md](claude-plugin/README.md) and the [Claude Code documentation](https://larabeacon.com/docs/claude-code).

The plugin source lives in `claude-plugin/` and is tested here (`vendor/bin/phpunit tests/ClaudePluginTest.php`, `claude plugin validate ./claude-plugin --strict`). After pushing a version bump of `claude-plugin/.claude-plugin/plugin.json` (keep `Server::VERSION` in sync), publish it to [Baer-Software/larabeacon-claude-plugin](https://github.com/Baer-Software/larabeacon-claude-plugin) with `tools/publish-claude-plugin.sh`. Regenerate `claude-plugin/data/checks.json` from the website catalogue with `php tools/build-plugin-catalog.php website/config/checks.php`.

Read Cloud history from scripts as JSON with `php artisan larabeacon:cloud status|reports|report [number|latest]`.

### Laravel Boost

LaraBeacon ships [Laravel Boost](https://laravel.com/docs/boost) guidelines and a `larabeacon-release-check` agent skill. After installing LaraBeacon, run `php artisan boost:update` (or `boost:install`) so Claude Code, Codex, Cursor and other Boost-supported agents run LaraBeacon, read its JSON findings and fix only confirmed issues within scope.

## Remediation and CI workflows

With Core 3.1.0 or newer, export findings locally, choose an environment profile, accept known findings individually, and review deterministic patch proposals:

```bash
php artisan larabeacon --profile=local --details
php artisan larabeacon --profile=ci --format=markdown --output=larabeacon.md
php artisan larabeacon:baseline --profile=ci --output=larabeacon-baseline.json --reason="Scheduled remediation"
php artisan larabeacon --profile=ci --baseline=larabeacon-baseline.json --only-new --fail-on=major
php artisan larabeacon --details --fix-preview
```

GitHub projects can use the bundled action to show findings in GitHub code scanning:

```yaml
- uses: A-Baer/LaraBeacon@v3
  with:
    fail-on: major
```

See [the remediation workflow](docs/REMEDIATION_WORKFLOW.md) for JSON/SARIF exports, acceptance expiry, GitHub Actions, scan limitations and Cloud comparison behavior. Fix previews never edit source files.

## Share feedback, get LaraBeacon Pro

Using LaraBeacon in a Laravel project? I'm looking for the first five external projects willing to try it and share 20 minutes of honest feedback about installation, useful findings, and false positives. In return, I'll provide a free LaraBeacon Pro licence for one project per participant.

Email [anthony.baer@baer-software.com](mailto:anthony.baer@baer-software.com?subject=LaraBeacon%20feedback) with your Laravel version and a short description of your project. Private projects are welcome; you do not need to share your source code. I'll confirm your place and arrange Pro access personally.

## Extensions and product boundaries

The public package stays at the repository root so Composer and Packagist can install it directly. Analyzer discovery supports additional namespaces and paths, including the separately implemented private package `baer-software/larabeacon-pro`.

- **LaraBeacon**: LGPL-licensed analyzer engine and public checks in this repository.
- **LaraBeacon Pro**: private Composer package containing 64 clean-room historical Pro checks plus modern Laravel checks.
- **LaraBeacon Cloud**: self-service SaaS for report history and teams, included with Pro project licences in the private website deployment; repository integrations remain a later extension.

Cloud reporting is disabled by default and only runs with `--report`. Report payloads contain project metadata and analyzer findings; source snippets and exception stack traces are excluded unless they are enabled through separate explicit configuration flags. The public analyzer remains fully usable offline. See [Product architecture](docs/PRODUCT_ARCHITECTURE.md) for the intended boundaries.

Optional Claude-powered AI work plans turn supported Cloud findings into prioritized actions with verification guidance. One new plan per team per UTC day is shared across projects and members; AI is enabled by default and can be disabled in Team settings; data is sent only for a requested plan, and source code and diagnostic messages are excluded. See [AI work plan documentation](https://larabeacon.com/docs/ai-work-plans).

For shared report history and team access, [create a LaraBeacon Cloud account](https://larabeacon.com/register). Interactive terminal scans display a short Cloud hint; CI runs, non-interactive or quiet runs, preflight output, and JSON/Markdown/SARIF exports omit it.

LaraBeacon Pro is released separately as a private Composer package and requires LaraBeacon Core `^3.0`.

## Website

The standalone Laravel application in `website/` contains the LaraBeacon marketing site, documentation, legal notices, complete Free/Pro check catalogue, and self-service Cloud application. It is a modular monolith with one deployment and database, while keeping Core and the private Pro package in their own repositories. The public catalogue still derives metadata from the real analyzers without including proprietary Pro source.

Nothing in this repository deploys or publishes the website automatically. See `website/README.md` and `website/docs/LAUNCH_CHECKLIST.md` before preparing a public launch.

## Development

```bash
composer install
composer test
composer format:check
composer validate --strict
```

The CI matrix covers Laravel 12 and 13 across their supported PHP versions.

## Origin and license

LaraBeacon is derived from Enlightn and retains its Git history and copyright. The public package is licensed under the [GNU LGPL v3 or later](LICENSE.md). See [NOTICE.md](NOTICE.md) for attribution.
