# LaraBeacon product architecture

LaraBeacon is intentionally split into independently deployable products.

## Public core

This repository contains the Composer-installable public package at its root:

```text
config/                 Published Laravel configuration
src/Analyzers/          Public performance, security, and reliability checks
src/Inspection/         Static source inspection
src/PHPStan/            LaraBeacon-specific PHPStan rules and types
src/Reporting/          Optional transport boundary for LaraBeacon Cloud
tests/                  Package and analyzer regression tests
```

The core owns analyzer discovery, execution, formatting, baselines, and local CI output. It must remain fully functional without Pro or Cloud.

## LaraBeacon Pro

The private package uses the Composer name `baer-software/larabeacon-pro` and the namespace `BaerSoftware\LaraBeaconPro`. It depends on the public core. Its service provider adds the private analyzer namespace and path to the existing `larabeacon.analyzer_paths` configuration only when Pro is installed.

Pro must remain a separate private repository. Proprietary analyzer code must not be committed to this public fork.

## LaraBeacon Cloud

The invitation-only SaaS is implemented as a module of the private `website/` Laravel application and is deployed from that application's independent repository. This public package owns only the client-side report contract and transport. The Cloud endpoint and credentials are optional `LARABEACON_CLOUD_*` environment variables; the client accepts only absolute HTTPS endpoints and follows redirects only over HTTPS.

The report contract includes project metadata, analyzer results, paths, line numbers, and finding details. Source snippets and exception stack traces are removed before transport by default and require separate explicit opt-ins through `LARABEACON_CLOUD_INCLUDE_CODE_SNIPPETS` and `LARABEACON_CLOUD_INCLUDE_EXCEPTION_STACK_TRACES`.

## Public website

The local `website/` checkout is a standalone Laravel 13 application with its own private deployment repository, Composer lock file, npm lock file, and production database. The Core repository ignores that checkout just as it ignores the private Pro package. The application is a modular monolith containing public marketing pages, documentation, legal pages, the server-rendered Core/Pro catalogue, and the authenticated Cloud module.

The catalogue is generated from analyzer metadata, but the website never contains proprietary Pro implementation source. Cloud authentication, teams, project licence allocations, project API tokens, report ingestion, queued processing, report history, and retention live inside this application. Checkout, subscription billing, automatic Stripe fulfilment, package delivery, and repository-provider integrations remain disabled or unimplemented until those boundaries are reviewed separately.

The website is deployed independently from the Composer packages. Its public pages remain useful and indexable without JavaScript, while the authenticated Cloud area may use progressive Livewire navigation. Public routes stay stateless; sessions and browser storage are limited to authentication and Cloud routes.

## Compatibility policy

The first LaraBeacon release targets currently supported Laravel generations rather than preserving all historical Enlightn combinations. Its CI matrix is the source of truth for supported PHP and Laravel pairs.
