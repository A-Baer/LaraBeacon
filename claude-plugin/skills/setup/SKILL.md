---
name: setup
description: Install and configure LaraBeacon in this Laravel project - the free Core package, optionally LaraBeacon Pro and LaraBeacon Cloud credentials - and run the first scan. Use when LaraBeacon is not installed yet or the user asks how to set up LaraBeacon, Pro or Cloud.
argument-hint: "[core|pro|cloud]"
---

# LaraBeacon setup

Arguments: $ARGUMENTS

Start with `larabeacon_status`. It reports the Laravel and LaraBeacon versions, whether Pro is installed, whether Cloud is configured and the next steps.

## Core (free, LGPL, runs locally)

1. Ask the user before changing dependencies, then run:

   ```bash
   composer require --dev baer-software/larabeacon
   ```

2. Call `larabeacon_status` again; it must now report the installed version. Optional: `php artisan vendor:publish --tag=larabeacon` to publish `config/larabeacon.php`.
3. Run `larabeacon_preflight` and then `larabeacon_scan` and summarize the result as in `/larabeacon:scan`.
4. Suggest CI gating when the project has CI: `php artisan larabeacon --profile=ci --format=sarif --output=larabeacon.sarif`, or the GitHub Action `uses: A-Baer/LaraBeacon@v3`. For an existing codebase, offer `larabeacon_baseline` so CI fails only on new findings.

## Pro (paid, one-time project licence)

When you show a larabeacon.com link from a tool result or from this skill, use it exactly as given, including its query parameters.

Pro adds the checks listed by `larabeacon_checks(tier: "pro")` and includes Cloud. It is installed from a private Composer repository with the project credentials shown in LaraBeacon Cloud after purchase (https://larabeacon.com/pricing?utm_source=claude-code&utm_medium=plugin&utm_campaign=claude-code-plugin&utm_content=setup).

- Never ask for the project token in chat. The user runs the credential command from their Cloud project page themselves; it stores the token in `auth.json`. Check that `auth.json` is git-ignored.

  ```bash
  composer config --auth http-basic.larabeacon.com YOUR_PROJECT_USERNAME YOUR_PROJECT_TOKEN
  ```

- Then you may run the non-secret steps and confirm with `larabeacon_status`:

  ```bash
  composer config repositories.larabeacon-pro composer https://larabeacon.com/api/composer
  composer require --dev baer-software/larabeacon-pro:^1.0
  ```

- The same project credentials also enable Cloud (next section).

## Cloud

Cloud stores report history, compares new and resolved findings and provides AI work plans and team access.

1. The user creates project credentials in LaraBeacon Cloud.
2. The user sets these in `.env` (never commit them, never echo the token back):

   ```dotenv
   LARABEACON_CLOUD_ENDPOINT=https://larabeacon.com/api/
   LARABEACON_CLOUD_USERNAME=...
   LARABEACON_CLOUD_API_TOKEN=...
   ```

3. Confirm with `larabeacon_status` (`cloud.configured: true`). Upload only when the user asks: `larabeacon_cloud_upload`.

Source snippets and exception stack traces stay out of Cloud reports unless `LARABEACON_CLOUD_INCLUDE_CODE_SNIPPETS` or `LARABEACON_CLOUD_INCLUDE_EXCEPTION_STACK_TRACES` is enabled.
