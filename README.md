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

Some analyzers inspect runtime-specific configuration. Run LaraBeacon in a production-like environment for a complete result. Use `--ci` for checks that are safe and deterministic in continuous integration.

## Extensions and product boundaries

The public package stays at the repository root so Composer and Packagist can install it directly. Analyzer discovery supports additional namespaces and paths, including the separately implemented private package `baer-software/larabeacon-pro`.

- **LaraBeacon**: LGPL-licensed analyzer engine and public checks in this repository.
- **LaraBeacon Pro**: private Composer package containing 64 clean-room historical Pro checks plus modern Laravel checks.
- **LaraBeacon Cloud**: invitation-only SaaS for report history and teams in the private website deployment; repository integrations remain a later extension.

Cloud reporting is disabled by default and only runs with `--report`. Report payloads contain project metadata and analyzer findings; source snippets and exception stack traces are excluded unless they are enabled through separate explicit configuration flags. The public analyzer remains fully usable offline. See [Product architecture](docs/PRODUCT_ARCHITECTURE.md) for the intended boundaries.

LaraBeacon Pro is released separately as a private Composer package and requires LaraBeacon Core `^3.0`.

## Website

The standalone Laravel application in `website/` contains the LaraBeacon marketing site, documentation, legal notices, complete Free/Pro check catalogue, and invitation-only Cloud application. It is a modular monolith with one deployment and database, while keeping Core and the private Pro package in their own repositories. The public catalogue still derives metadata from the real analyzers without including proprietary Pro source.

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
