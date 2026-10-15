<?php

namespace BaerSoftware\LaraBeacon\Findings;

final class Guidance
{
    public static function forAnalyzer(array $result): array
    {
        $name = class_basename($result['class'] ?? '');
        $specific = [
            'EnvCallAnalyzer' => ['Configuration caching can change the value returned by env() outside configuration files.', 'Move the environment lookup into a configuration file and use config() at the reported call site.', "// config/services.php\n'provider_key' => env('PROVIDER_KEY'),\n// application code\nconfig('services.provider_key');", 'Exercise the affected path with cached configuration in a disposable staging environment.'],
            'AppDebugAnalyzer' => ['Production error pages may expose technical details.', 'Set APP_DEBUG=false in the production configuration and rebuild the configuration cache during deployment.', 'APP_DEBUG=false', 'Confirm config("app.debug") is false in the target runtime and check a controlled error response.'],
            'QueueTimeoutAnalyzer' => ['A job may be retried while its first worker is still processing it.', 'Set retry_after several seconds above the actual worker timeout; account for Horizon and job-specific timeouts.', 'worker timeout: 60 seconds; retry_after: 90 seconds (example only)', 'Check actual worker arguments, Horizon settings and longest-running jobs, then restart workers through the deployment process.'],
            'VulnerableDependencyAnalyzer' => ['An installed dependency version matches a known security advisory; exploitability depends on use.', 'Review advisory conditions and production usage, then update the affected dependency in a development branch.', 'composer audit --locked\ncomposer update vendor/package --with-all-dependencies', 'Review the lockfile diff, run application tests and repeat the dependency audit.'],
            'FrontendVulnerableDependencyAnalyzer' => ['A frontend dependency matches an advisory; build tooling and shipped browser code have different exposure.', 'Review the affected dependency path and advisory, then prepare a scoped package update.', 'npm audit\nnpm explain package-name', 'Rebuild assets, exercise affected browser flows and repeat the audit.'],
            'FrameworkSupportAnalyzer' => ['Once security support ends, newly disclosed Laravel vulnerabilities are no longer patched for this release.', 'Plan the next major Laravel upgrade in a dedicated branch and follow the official upgrade guide.', 'composer require laravel/framework:^13.0 --with-all-dependencies', 'Run the full test suite and LaraBeacon against the upgraded branch before deploying it.'],
            'PhpSupportAnalyzer' => ['Once security support ends, newly disclosed PHP vulnerabilities are no longer patched for this runtime.', 'Upgrade the production PHP runtime and align the platform constraint in composer.json.', '"config": { "platform": { "php": "8.4.0" } }', 'Run tests on the new runtime, deploy to staging first and rerun LaraBeacon in the production-like environment.'],
            'MassAssignmentAnalyzer' => ['Mass assignment may allow unintended field changes depending on validation and authorization.', 'Review each reported model against the actual request validation, ownership checks and allowed fields.', '$model->fill($request->validated()); // only with an appropriate field allowlist', 'Test requests attempting to change ownership and other protected fields.'],
        ];
        $steps = $specific[$name] ?? [
            $result['error'] ?? 'This result requires investigation in the environment where the application runs.',
            'Review the reported evidence and analyzer documentation; change only the affected behavior.',
            null,
            'Add or run a regression check for the affected behavior, then rerun this analyzer in the same environment.',
        ];
        $isError = ($result['status'] ?? '') === 'error';

        return [
            'kind' => $isError ? 'execution-error' : 'review-signal',
            'confidence' => in_array($name, ['AppDebugAnalyzer', 'EnvCallAnalyzer', 'QueueTimeoutAnalyzer'], true) && ! $isError ? 'high' : 'requires-review',
            'risk' => $isError ? 'The check could not complete. This is not evidence that the application passed or has a vulnerability.' : $steps[0],
            'fix' => $isError ? 'Resolve the reported execution error and rerun the check.' : $steps[1],
            'example' => $isError ? null : $steps[2],
            'verification' => $steps[3],
            'allowed_scope' => 'Only the reported configuration or call sites and their direct regression tests. Do not change unrelated behavior.',
        ];
    }
}
