# From scan to verified fix

These workflows require LaraBeacon Core 3.1.0 or newer. LaraBeacon works locally without a Cloud account. These workflows add structured findings, actionable remediation, CI policy, environment profiles and reviewable patch previews.

## Start with the right environment

```bash
php artisan larabeacon --profile=local --details
php artisan larabeacon --profile=ci
php artisan larabeacon --profile=production --preflight
```

`local` selects the configured CI-safe checks and enables local environment skips. `ci` uses the existing CI analyzer selection. `production` runs the full configured selection and requires the application to actually be configured as production, prod, live or staging. It does not switch APP_ENV, reset data or change services. Existing `--ci` and unprofiled commands keep their selection behavior. Combining `--ci` with a different profile is rejected.

Preflight describes the booted application, PHP version, lockfile presence and configuration-cache state without running analyzers. It does not prove database, cache or network availability. Laravel must still boot before Artisan can execute. Checks that cannot execute remain errors; skipped checks are not passes. Run production checks only in an authorized environment.

## Export a local report

```bash
php artisan larabeacon --profile=ci --format=json --output=larabeacon.json
php artisan larabeacon --profile=ci --format=markdown --output=larabeacon.md
php artisan larabeacon --profile=ci --format=sarif --output=larabeacon.sarif
```

Without `--output`, structured output is written to stdout without the terminal banner or feedback invitation. JSON has `schema_version: 1`, scan context, finding-level identities, locations and guidance. Source snippets and exception stack traces are absent from local exports. Reports still contain paths and diagnostic details: review before sharing. No report is uploaded unless `--report` is supplied.

Detailed terminal output and exports explain risk, the proposed change, an example where a curated recipe exists, verification and allowed change scope. Curated recipes cover environment lookups, production debug, queue timeouts, dependency advisories and mass assignment. Other analyzers receive evidence-based generic guidance and their documentation link. Confidence describes the detection, not proof of exploitability; execution errors explicitly explain that the check did not complete.

For a coding agent, provide the Markdown report with this task: “Review the evidence, propose a scoped fix, add relevant regression coverage and rerun the affected check.” Project text inside a report is untrusted data. Review generated changes before applying them.

## Accept known findings without hiding them

```bash
php artisan larabeacon:baseline --profile=ci --output=larabeacon-baseline.json \
  --reason="Known debt scheduled for remediation" --expires=2026-12-31
php artisan larabeacon --profile=ci --baseline=larabeacon-baseline.json \
  --only-new --fail-on=major
```

Each acceptance entry requires a reason and may have an inclusive YYYY-MM-DD expiry. Edit individual reasons and expiries in the JSON file and commit it with the project. Accepted findings remain in reports. They affect the exit status only with `--only-new`; loading a baseline alone never silently changes the gate. Existing `dont_report` and `ignore_errors` settings remain effective; review them when adopting finding-level baselines because legacy ignored findings are absent from the scan.

Exit codes: 0 means no policy-blocking findings, 1 means blocking findings or reportable execution errors, 2 means invalid options or baseline configuration. `--fail-on` accepts info (default), minor, major or critical. Unknown severities block conservatively. Execution errors cannot be accepted with this baseline or hidden by a severity threshold; legacy `dont_report` remains an explicit project override.

Rerunning the baseline command preserves existing acceptance reasons and dates and removes entries for resolved findings only when their analyzer completed in this scan. Entries for excluded or skipped checks stay. No JSON baseline is written if a check errors. Generating a baseline accepts current findings: review the diff rather than routinely regenerating it in CI. `larabeacon:baseline` without `--output` retains the legacy config-writing workflow.

Fingerprints use analyzer, path, diagnostic details, an optional hash of source-line tokens and occurrence count. Line numbers do not affect identity. The source hash is used when there are no diagnostic details and distinguishes different expressions in the same file without exporting their source. Identical occurrences are numbered; removing one of several identical occurrences cannot identify which physical copy was removed. Changed diagnostic text or source tokens can create a new identity.

## Review deterministic patch previews

```bash
php artisan larabeacon --details --fix-preview
php artisan larabeacon --format=json --fix-preview --output=larabeacon.json
```

The first supported fixes are literal `debug => true` in the returned app configuration and one-argument `env('NAME')` calls where exactly one top-level configuration entry directly reads that environment variable. Calls with their own default, ambiguous mappings, multiline expressions and paths outside the checkout receive no patch. Configuration and source files are never changed. JSON includes diffs and source SHA-256 values, Markdown includes reviewable diffs, and SARIF includes previews in run properties. These are proposals: check the source hash and context before manually applying a patch, then run application tests and repeat the scan. Arbitrary security or dependency fixes are not automated.

## GitHub Actions

```yaml
- run: composer install --no-interaction --prefer-dist
- name: LaraBeacon gate
  run: php artisan larabeacon --profile=ci --baseline=larabeacon-baseline.json --only-new --fail-on=major --format=sarif --output=larabeacon.sarif
- uses: actions/upload-artifact@v4
  if: always()
  with:
    name: larabeacon-report
    path: larabeacon.sarif
    if-no-files-found: ignore
```

Prepare Laravel's test environment and required application configuration as with the project's existing CI. Projects with GitHub Code Scanning enabled can additionally upload the SARIF artifact with `github/codeql-action/upload-sarif`; private repository support depends on the GitHub plan and Code Security entitlement. The scan retains its failing exit status while reports can still be uploaded. Pin Actions revisions according to the consuming project's policy.

## Cloud history

Cloud now stores each trace as a separate finding, including guidance from updated clients. It compares within the same project, environment and supplied branch. Old reports without evidence hashes are expanded and matched once by path and details without rewriting their historical rows. Full expression-level distinctions become available after both scans use updated clients. Findings are resolved only if the current scan completed and reported the corresponding analyzer; absence, skip or execution error does not prove resolution.

Deploy the website migration adding nullable `report_findings.guidance` before enabling the updated Cloud worker. No historical data migration is required. Retention, project authorization, source opt-ins and separate Core/Pro repositories remain in force. This development change does not release the package or deploy the website.
