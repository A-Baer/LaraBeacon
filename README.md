# LaraBeacon

[![tests](https://github.com/A-Baer/LaraBeacon/actions/workflows/tests.yaml/badge.svg)](https://github.com/A-Baer/LaraBeacon/actions/workflows/tests.yaml)

LaraBeacon checks Laravel applications for production-readiness problems across performance, security, and reliability. It is a maintained fork of the open-source [Enlightn](https://github.com/enlightn/enlightn) package.

## Compatibility

| LaraBeacon | PHP | Laravel | Larastan | PHPStan |
| --- | --- | --- | --- | --- |
| `dev-main` | 8.2–8.5 | 12–13 | 3.x | 2.x |

Laravel 13 requires PHP 8.3 or newer.

## Installation

Until the first Packagist release is published, add the repository as a VCS source:

```bash
composer config repositories.larabeacon vcs https://github.com/A-Baer/LaraBeacon
composer require --dev baer-software/larabeacon:dev-main
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

The public package stays at the repository root so Composer and Packagist can install it directly. Analyzer discovery supports additional namespaces and paths, including the planned private package `baer-software/larabeacon-pro`.

- **LaraBeacon**: LGPL-licensed analyzer engine and public checks in this repository.
- **LaraBeacon Pro**: planned private Composer package containing commercial analyzers.
- **LaraBeacon Cloud**: planned SaaS for report history, teams, and repository integrations.

Cloud reporting is disabled by default. The public analyzer remains fully usable offline. See [Product architecture](docs/PRODUCT_ARCHITECTURE.md) for the intended boundaries.

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
