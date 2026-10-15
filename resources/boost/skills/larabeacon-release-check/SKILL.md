---
name: larabeacon-release-check
description: Run LaraBeacon to check a Laravel application for performance, security, and reliability risks, then fix confirmed findings within the scope of the current change. Use before finishing a feature, preparing a release, upgrading Laravel or PHP, or when the user asks for a production-readiness or security check.
---

# LaraBeacon Release Check

## When to use this skill

Use this skill when a change is ready for review, before a release, during a Laravel or PHP upgrade, or when the user asks whether the application is production ready.

## Workflow

1. Run the scan with a structured report:

   ```bash
   php artisan larabeacon --profile=local --format=json --output=storage/larabeacon.json
   ```

   Use `--profile=ci` inside CI. Use `--profile=production` only in a real production-like environment.

2. Read the `findings` array in `storage/larabeacon.json`. For each finding, note `analyzer`, `severity`, `message`, `location` (`path`, `lineNumber`), and the `guidance` fields (`risk`, `fix`, `verification`, `allowed_scope`). A finding with `status: error` means the check could not run; it is not evidence of a problem.

3. Separate findings:
   - **Related to the current change**: fix them, staying within `allowed_scope`.
   - **Pre-existing or environment-specific**: report them to the user instead of fixing them silently.
   - **Runtime checks run locally** (headers, caches, workers, PHP version): explain that they need a production-like environment.

4. Preview deterministic fixes where supported. Previews never change files:

   ```bash
   php artisan larabeacon --details --fix-preview
   ```

5. During a Laravel or PHP upgrade, also run the support checks. They are excluded from the `local` and `ci` profiles, and the PHP result is only meaningful for the production runtime:

   ```bash
   php artisan larabeacon 'BaerSoftware\LaraBeacon\Analyzers\Reliability\FrameworkSupportAnalyzer' 'BaerSoftware\LaraBeacon\Analyzers\Reliability\PhpSupportAnalyzer'
   ```

6. After fixing, rerun the affected analyzer and the application's tests:

   ```bash
   php artisan larabeacon 'BaerSoftware\LaraBeacon\Analyzers\Security\CSRFAnalyzer'
   ```

## Rules

- Never disable an analyzer, add `exclude_analyzers` entries, or extend the baseline to make a scan pass unless the user explicitly asks for it.
- Never commit real secrets, application keys, or credentials while fixing configuration findings.
- Do not pass `--report`; uploading to LaraBeacon Cloud is the user's decision.
- Summarize remaining findings by severity with the file and line, and say which ones you fixed.
