# PLAN_01 — Repository & Package Scaffolding

## Status: COMPLETE

## Goal
Create monorepo structure with two Composer packages (core + laravel), configure PSR-4 autoloading, and set up Pest testing.

## What changed

### Files created

| File | Purpose |
|---|---|
| `composer.json` (root) | Dev workspace with path repos, PHP ^8.2, Pest ^3.0 |
| `phpunit.xml` (root) | Aggregates both test suites for running `./vendor/bin/pest` from root |
| `packages/core/composer.json` | Core package: PSR-4 `Codemetry\Core\`, requires `symfony/process ^7.0` |
| `packages/core/phpunit.xml` | PHPUnit config for core tests |
| `packages/core/src/.gitkeep` | Placeholder for source directory |
| `packages/core/tests/Pest.php` | Pest configuration (minimal) |
| `packages/core/tests/ExampleTest.php` | Smoke test |
| `packages/laravel/composer.json` | Laravel adapter: PSR-4 `Codemetry\Laravel\`, requires illuminate + testbench |
| `packages/laravel/phpunit.xml` | PHPUnit config for Laravel tests |
| `packages/laravel/src/.gitkeep` | Placeholder for source directory |
| `packages/laravel/config/.gitkeep` | Placeholder for config directory |
| `packages/laravel/tests/Pest.php` | Pest configuration with Orchestra Testbench base class |
| `packages/laravel/tests/ExampleTest.php` | Smoke test |

## Acceptance checklist

- [x] `composer install` succeeds at root
- [x] PSR-4 autoloading resolves `Codemetry\Core\` and `Codemetry\Laravel\`
- [x] `./vendor/bin/pest --testsuite=Core` passes (1 test, 1 assertion)
- [x] `./vendor/bin/pest --testsuite=Laravel` passes (1 test, 1 assertion)
- [x] `./vendor/bin/pest` from root runs all tests (2 passed)

## Test results

```
$ ./vendor/bin/pest

   PASS  Packages\core\tests\ExampleTest
  ✓ core package boots

   PASS  Packages\laravel\tests\ExampleTest
  ✓ laravel package boots

  Tests:    2 passed (2 assertions)
```

## Notes

- A root `phpunit.xml` was added (not in original plan) to allow running `./vendor/bin/pest` directly from root without `cd`-ing into packages. Pest resolves test directories relative to CWD, so per-package configs only work when running from within the package directory.
- Running per-package: `cd packages/core && ../../vendor/bin/pest` also works.
- Resolved illuminate versions: ^11.0|^12.0 (Composer resolved to v12.50.0).
- Orchestra Testbench: ^9.0|^10.0.
