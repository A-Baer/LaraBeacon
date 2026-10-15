## LaraBeacon

LaraBeacon checks this Laravel application for performance, security, and reliability risks before release. It runs locally through Artisan and never uploads code unless `--report` is passed with configured Cloud credentials.

- Before finishing a change that touches configuration, queues, caching, sessions, authentication, middleware, dependencies, or deployment, run LaraBeacon and review the failed checks related to your change.
- Use the JSON report when you need structured results; each finding includes the analyzer, severity, location, and remediation guidance. The `larabeacon-release-check` skill describes the full review workflow.
- Fix only confirmed findings within the scope of the current task. Do not change production settings based only on a local run, and do not disable analyzers or edit the baseline to make a scan pass unless the user asks for it.
- Findings from runtime checks (headers, caches, queue workers, Redis, PHP version) are only meaningful in a production-like environment.

@verbatim
<code-snippet name="Scan the application and write a structured report" lang="bash">
php artisan larabeacon --profile=local --details
php artisan larabeacon --profile=local --format=json --output=storage/larabeacon.json
</code-snippet>

<code-snippet name="Gate only new findings in CI" lang="bash">
php artisan larabeacon --profile=ci --baseline=larabeacon-baseline.json --only-new --fail-on=major
</code-snippet>
@endverbatim
