# Former Enlightn projects: LaraBeacon 3.0.4 scan

Date: 2026-09-25

This report records an isolated scan of five public Laravel projects that previously used `enlightn/enlightn` and later removed it. No issue, pull request, email, or other maintainer message has been sent.

## Method

- Each repository was cloned from its current default branch and its earlier Enlightn use/removal was verified in Git history.
- LaraBeacon 3.0.4 was added only inside a temporary clone with Composer scripts disabled.
- Scans used a synthetic local environment with SQLite plus `array` cache/session and a `sync` queue. Findings caused by those temporary values, missing migrations, the local PHP configuration, or the synthetic application key are excluded below.
- Dependency findings come from the checked-in lockfiles and current advisory databases. They are time-sensitive.
- The foreign-key fillable analyzer is treated as a review signal, not proof of an exploitable mass-assignment vulnerability.
- `apiary` could not be booted because its private Laravel Nova package returned HTTP 402. Its Composer and npm lockfiles were audited separately without replacing or stubbing Nova.

## Release and compatibility verification

- Published LaraBeacon 3.0.4 from commit `39fc3a3c96edc8372d66d1479e3bd330f7c6d139`.
- Fresh Laravel 12.69.2 install: LaraBeacon 3.0.4, Guzzle 7.15.5, command discovery successful.
- Fresh Laravel 13.33.0 install: LaraBeacon 3.0.4, Guzzle 8.2.0 retained, command discovery successful.
- The GitHub Actions matrix passed on PHP 8.2-8.5 and Laravel 12/13.

## Results

| Project | Current revision scanned | Verified Enlightn removal | Dependency result | Other high-confidence signals |
| --- | --- | --- | --- | --- |
| [kantorge/yaffa](https://github.com/kantorge/yaffa) | `dbed841a` | [`801e2db`](https://github.com/kantorge/yaffa/commit/801e2dbf368937ab86be4da3b4e44c65f36408fb), Laravel 12 upgrade | Composer and npm production audits clean | No custom error pages or CSP; 18 fillable foreign-key review signals; deprecated Laravel request access and League CSV call |
| [Mythos/books](https://github.com/Mythos/books) | `0bb09a6` | [`ddb568f`](https://github.com/Mythos/books/commit/ddb568f05ffac092a69568ec87ed2e6221b04b60), Laravel/Livewire upgrade | 17 backend advisories across Guzzle 7.12.3, league/commonmark 2.8.2, and Livewire 4.3.3; pnpm production audit clean | No custom error pages or CSP; three fillable foreign-key review signals |
| [Fabio1202/faboi-local-sso](https://github.com/Fabio1202/faboi-local-sso) | `16d8634` | [`89c5c76`](https://github.com/Fabio1202/faboi-local-sso/commit/89c5c76531ba871854fa6e54810f1f852fe02d9a), explicitly because Enlightn did not support Laravel 12 | 20 backend advisories across 11 packages; npm reports 8 affected production packages (1 moderate, 6 high, 1 critical) | No custom error pages or CSP; both mass-assignment checks passed |
| [matiusnugroho/ppdb](https://github.com/matiusnugroho/ppdb) | `58237ec` | [`65b56bb`](https://github.com/matiusnugroho/ppdb/commit/65b56bb42ff39978a4c1c521bf0b10116a332d98), Laravel 12 upgrade | 15 backend advisories across five packages; npm production audit clean | Runtime-relevant alerts include Laravel Excel 3.1.67 and PhpSpreadsheet 1.30.1; no custom error pages or CSP; three fillable foreign-key review signals |
| [RoboJackets/apiary](https://github.com/RoboJackets/apiary) | `98739d2` | [`6fdb49f`](https://github.com/RoboJackets/apiary/commit/6fdb49f0f0a56ddb5d618b518397e765a62412ab), dependency cleanup | Composer audit clean but `marcusschwarz/lesserphp` is abandoned; npm reports Vue 2 / Vue compiler findings affecting four packages (3 low, 1 moderate) | Full LaraBeacon result unavailable because the repository's Nova license rejected the package download |

### Dependency detail worth prioritising

`books` pins versions that are now covered by advisories: Guzzle 7.12.3 has six advisories, league/commonmark 2.8.2 has ten, and Livewire 4.3.3 has one DOM-XSS advisory. The project currently pins PHP 8.5.7 exactly; the scan used that installed PHP line.

`faboi-local-sso` includes high-impact backend findings in Laravel Passport 13.1.0, Livewire 3.6.4, phpseclib 3.0.46, Symfony components, and WebAuthn packages. Its production npm graph also contains affected `postcss`, `vite`, `rollup`, `tar`, `immutable`, `nanoid`, `picomatch`, and `yaml` versions. Reachability still needs project-specific review, particularly for build-only packages.

`ppdb` has two particularly relevant runtime dependency paths: Laravel Excel 3.1.67 is affected by a caller-controlled export-path advisory, and its PhpSpreadsheet 1.30.1 dependency has multiple file-processing advisories including critical SSRF/RCE and XSS reports. The remaining alerts are in PHPUnit, PsySH, and Symfony YAML and should be classified by deployment scope before prioritisation.

## Maintainer outreach drafts

These are drafts only. They should be rechecked against the repository immediately before sending because advisory data and default branches can change.

### kantorge/yaffa

**Subject:** Optional Laravel 12 readiness scan results for Yaffa

Hi! I noticed Yaffa previously used Enlightn and removed it during the Laravel 12 upgrade. I maintain LaraBeacon, an open-source Laravel readiness scanner that supports Laravel 12 and 13, so I ran version 3.0.4 against a temporary clone of the current default branch.

The good news: both the Composer and production npm dependency audits were clean, and LaraBeacon's mass-assignment check passed. The most actionable project-level items were missing custom error pages / Content-Security-Policy headers, several deprecated API calls such as `Request::get()`, and 18 fillable foreign-key fields that may be worth checking against the authorization and validation in their write paths. That last category is deliberately a review signal, not a claim of a vulnerability.

I have not opened a PR because some findings need application context, but I can share the exact scan output or help turn the confirmed items into a small PR if useful.

### Mythos/books

**Subject:** Current dependency findings from a Laravel 13 LaraBeacon scan

Hi! I saw that Books previously used Enlightn and removed it while upgrading the Laravel/Livewire stack. I maintain LaraBeacon, which supports Laravel 12 and 13, and ran 3.0.4 against a temporary clone of the current branch using PHP 8.5.

The scan found 17 current backend advisories concentrated in the pinned Guzzle 7.12.3, league/commonmark 2.8.2, and Livewire 4.3.3 versions. The pnpm production audit was clean. LaraBeacon also flagged missing custom error pages / CSP headers and three fillable relationship IDs as review signals (`Article.category_id`, `Series.category_id`, and `Volume.series_id`).

I have not changed the repository. If helpful, I can provide the full output or prepare a focused dependency-update PR after confirming the desired version constraints.

### Fabio1202/faboi-local-sso

**Subject:** LaraBeacon replacement scan after the Laravel 12 upgrade

Hi! I noticed the project removed Enlightn because Laravel 12 was not supported. I maintain LaraBeacon, an open-source alternative with Laravel 12/13 support, and tested version 3.0.4 against a temporary clone.

The scan completed and both mass-assignment checks passed. The main actionable area is dependency maintenance: Composer reported 20 advisories across 11 packages, including Laravel Passport 13.1.0, Livewire 3.6.4, phpseclib 3.0.46, Symfony components, and WebAuthn packages. The production npm graph reported eight affected packages, including `postcss`, `vite`, `rollup`, and `tar`; some may be build-only, so reachability should be reviewed before prioritising. LaraBeacon also reported missing custom error pages and a CSP.

I have not opened an automated upgrade PR because this set deserves a scoped update and regression test. I can share the exact report or help prepare that update if useful.

### matiusnugroho/ppdb

**Subject:** Laravel 12 security scan findings for PPDB

Hi! I noticed PPDB previously used Enlightn and removed it during the Laravel 12 upgrade. I maintain LaraBeacon, which supports Laravel 12 and 13, and ran version 3.0.4 against a temporary clone of the current branch.

The production npm audit was clean. Composer reported 15 advisories across five packages. The most relevant runtime paths appear to be Laravel Excel 3.1.67 and PhpSpreadsheet 1.30.1; the latter is covered by several file-processing advisories, including critical reports, so it is worth prioritising if uploaded or otherwise untrusted spreadsheets are processed. The scan also identified missing custom error pages / CSP headers and three fillable relationship IDs as review signals.

I have not changed the repository. I can provide the complete output or help with a focused dependency update and verification if useful.

### RoboJackets/apiary

**Subject:** Partial dependency scan after Apiary's Enlightn removal

Hi! I noticed Apiary previously used Enlightn and later removed it during dependency cleanup. I maintain LaraBeacon and attempted a 3.0.4 scan against a temporary clone of the current branch.

I could not complete the application scan because Composer correctly required the project's private Laravel Nova package and the available Nova license rejected the download. I did not replace or stub that dependency. The independent lockfile checks were still useful: Composer reported no known advisories, but `marcusschwarz/lesserphp` is abandoned; the production npm audit reported Vue 2 / Vue compiler findings affecting four packages (three low, one moderate).

If a maintainer can run `composer require --dev baer-software/larabeacon` in an authorized development environment, I would be happy to help interpret the full report. I have not opened an issue because the result is intentionally partial.
