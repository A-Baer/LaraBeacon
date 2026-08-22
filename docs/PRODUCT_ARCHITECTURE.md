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

The planned private package will use the Composer name `baer-software/larabeacon-pro` and the namespace `BaerSoftware\LaraBeaconPro`. It will depend on the public core. Its service provider will add the private analyzer namespace and path to the existing `larabeacon.analyzer_paths` configuration only when Pro is installed.

Pro must remain a separate private repository. Proprietary analyzer code must not be committed to this public fork.

## LaraBeacon Cloud

The planned SaaS will be a separate application and repository. This package only owns the client-side report contract and transport. The Cloud endpoint and credentials are optional `LARABEACON_CLOUD_*` environment variables.

No application source code is sent unless the report schema explicitly adds and documents such a field. The intended default is to send analyzer results and minimal repository metadata only.

## Compatibility policy

The first LaraBeacon release targets currently supported Laravel generations rather than preserving all historical Enlightn combinations. Its CI matrix is the source of truth for supported PHP and Laravel pairs.
