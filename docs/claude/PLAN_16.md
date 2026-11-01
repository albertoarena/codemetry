# PLAN_16: PHP 8.3 Minimum, PHP 8.5 and Laravel 13 Support

## Goal

Raise the minimum PHP version to 8.3 (PHP 8.2 security support ends 2026-12-31), verify and test PHP 8.5, and add Laravel 13 support to the Laravel adapter.

> PLAN_15 (optional quality-tool providers) is still a draft and is intentionally left parked; this plan was requested ahead of it.

## Scope

- PHP constraint `^8.2` → `^8.3` in all four `composer.json` files
- CI matrix: PHP `8.3, 8.4, 8.5` × Laravel `12, 13`
- Laravel adapter: `illuminate/*` `^11.0|^12.0` → `^12.0|^13.0`, Testbench `^9.0|^10.0` → `^10.0|^11.0`
  - Laravel 11 is dropped: it is out of support and would no longer be tested. Laravel 11 users stay on v1.4.x.
- Core: `symfony/process` `^7.0` → `^7.0|^8.0`, so Laravel 13 apps on Symfony 8 are not forced down to 7.x (Symfony 8 needs PHP 8.4, so PHP 8.3 users still resolve 7.x)
- Remove the `curl_close()` call deprecated in PHP 8.5 (no-op since PHP 8.0)
- Update every "PHP 8.2+" mention in docs

Out of scope: Pest 3 → 4/5 upgrade.

## File Changes

### Updated Files
- `composer.json` - PHP `^8.3`, Testbench `^10.0|^11.0`
- `packages/core/composer.json` - PHP `^8.3`, `symfony/process` `^7.0|^8.0`
- `packages/laravel/composer.json` - PHP `^8.3`, `illuminate/*` `^12.0|^13.0`, Testbench `^10.0|^11.0`
- `packages/wordpress/composer.json` - PHP `^8.3`
- `packages/core/src/Ai/Engines/AbstractAiEngine.php` - drop `curl_close($ch)`
- `.github/workflows/tests.yml` - PHP × Laravel matrix, pins the Laravel major per job
- `README.md`, `packages/{core,laravel,wordpress}/README.md` - PHP 8.3+, Laravel 12/13
- `website/src/content/docs/getting-started/installation.mdx` - PHP 8.3+, Laravel 12/13
- `CLAUDE.md` - tech stack PHP 8.3+
- `docs/plans/branch-protection-ruleset.md` - new required check names

## Branch Ruleset (manual step, owner only)

Required checks change from `test (8.2)`, `test (8.3)`, `test (8.4)` to the six new matrix job names. The ruleset must be updated when this merges, otherwise every PR waits on `test (8.2)` forever.

## Commands to Run

```bash
composer update
php -d error_reporting=-1 vendor/bin/pest --display-deprecations --display-warnings --display-notices
# Other combinations, each in a scratch copy of the repo:
composer require --dev --no-update "laravel/framework:^12.0"   # or ^13.0
php83 composer update && php83 vendor/bin/pest --display-deprecations
```

## Acceptance Checklist

- [x] PHP `^8.3` in all four `composer.json` files
- [x] Laravel adapter allows `^12.0|^13.0`
- [x] `curl_close()` removed
- [x] CI matrix covers PHP 8.3/8.4/8.5 × Laravel 12/13
- [x] Tests pass locally on PHP 8.3, 8.4, 8.5 and on Laravel 12 and 13, with deprecations displayed
- [x] No "PHP 8.2" references left in docs (except historical plan files)
- [x] CI green on the PR (6 test jobs + website-build; each job confirmed to install its pinned Laravel major)
- [x] Ruleset updated (owner, 2026-11-01): required checks are the 6 `test (<php>, <laravel>)` jobs + `website-build`
- [x] Plan file updated with final Status / Review Notes

## Test Results (local)

185 tests / 617 assertions passed in every combination, with no deprecations, warnings or notices:

| PHP | Laravel | Testbench | symfony/process | Result |
|---|---|---|---|---|
| 8.3.33 | 12.70.2 | 10.12.0 | 7.4.22 | pass |
| 8.3.33 | 13.39.0 | 11.3.0 | 7.4.22 | pass |
| 8.4.27 | 13.39.0 | 11.3.0 | 8.1.10 | pass |
| 8.5.10 | 12.70.2 | 10.12.0 | 7.4.22 | pass |
| 8.5.10 | 13.39.0 | 11.3.0 | 8.1.10 | pass |

PHP 8.4 + Laravel 12 is covered by CI only.

## Review Notes

**What changed**
- Minimum PHP is 8.3 across all packages; CI tests PHP 8.3/8.4/8.5 against Laravel 12 and 13 (6 jobs)
- Laravel adapter supports Laravel 12 and 13; Laravel 11 dropped
- Core accepts symfony/process 7 or 8
- Removed `curl_close()`, deprecated in PHP 8.5 and untested because AI HTTP calls are not exercised by the suite

**Risks / assumptions**
- Raising the PHP minimum and dropping Laravel 11 means users on PHP 8.2 or Laravel 11 stay on v1.4.x; proposed release is v1.5.0
- The AI HTTP path (`httpPost`) still has no test coverage, so the `curl_close()` removal was verified by reading the code, not by a test
- CI now runs `composer update` after pinning the Laravel major (the lockfile was never committed, so `install` already resolved fresh)

**Follow-ups**
- Release v1.5.0 after merge (touches all three packages)
- Optional: Pest 3 → 4 (now possible on PHP 8.3)

## Status: COMPLETE

Merged in PR #45 (`42798e1`) on 2026-11-01; CI green on master.
