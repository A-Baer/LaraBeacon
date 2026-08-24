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

The planned SaaS will be a separate application and repository. This package only owns the client-side report contract and transport. The Cloud endpoint and credentials are optional `LARABEACON_CLOUD_*` environment variables; the client accepts only absolute HTTPS endpoints and follows redirects only over HTTPS.

The report contract includes project metadata, analyzer results, paths, line numbers, and finding details. Source snippets and exception stack traces are removed before transport by default and require separate explicit opt-ins through `LARABEACON_CLOUD_INCLUDE_CODE_SNIPPETS` and `LARABEACON_CLOUD_INCLUDE_EXCEPTION_STACK_TRACES`.

## Public website

The local `website/` checkout is a standalone Laravel 13 application with its own private deployment repository, Composer lock file, and npm lock file. The Core repository ignores that checkout just as it ignores the private Pro package. The application contains the public marketing pages, documentation, legal pages, and a server-rendered catalogue of every Core and Pro check.

The catalogue is generated from analyzer metadata, but the website never contains proprietary Pro implementation source. It may describe and link to Pro and the future Cloud application; authentication, subscriptions, payments, repository integrations, and the Cloud dashboard remain outside this application until those product boundaries are designed and implemented explicitly.

The website is deployed independently from the Composer packages. It must remain useful and indexable without JavaScript, while optional interactivity such as catalogue filtering enhances the server-rendered content. The future Cloud application will use a third repository and is not implemented inside the marketing site.

## Compatibility policy

The first LaraBeacon release targets currently supported Laravel generations rather than preserving all historical Enlightn combinations. Its CI matrix is the source of truth for supported PHP and Laravel pairs.
