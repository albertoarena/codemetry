# PLAN_09 — Laravel Adapter: ServiceProvider + Artisan Command

## Status: COMPLETE

## Goal
Thin Laravel adapter that wraps the core `Analyzer` pipeline behind a publishable config, a service provider, and an Artisan command with table/JSON output.

## What changed

### Files created

| File | Purpose |
|---|---|
| `packages/laravel/config/codemetry.php` | Publishable config: baseline_days, follow_up_horizon_days, keywords, AI settings |
| `packages/laravel/src/CodemetryServiceProvider.php` | Registers Analyzer singleton, merges config, publishes config, registers command |
| `packages/laravel/src/CodemetryAnalyzeCommand.php` | `codemetry:analyze` Artisan command with table/JSON output |
| `packages/laravel/tests/ExampleTest.php` | 9 integration tests using Orchestra Testbench |
| `tests/Pest.php` | Root-level Pest config binding Testbench TestCase for Laravel tests |

### Files modified

| File | Change |
|---|---|
| `composer.json` | Added `orchestra/testbench` to root `require-dev` |
| `packages/laravel/tests/Pest.php` | Simplified (Testbench binding moved to root) |

### Files removed

| File | Reason |
|---|---|
| `packages/laravel/src/.gitkeep` | Replaced by real source files |
| `packages/laravel/config/.gitkeep` | Replaced by real config file |

### Command signature

```
php artisan codemetry:analyze
    {--days=7}
    {--since=}
    {--until=}
    {--author=}
    {--branch=}
    {--format=table}
    {--ai=}
    {--baseline-days=}
    {--follow-up-horizon=}
    {--repo=}
```

### Config structure

```php
return [
    'baseline_days' => 56,
    'follow_up_horizon_days' => 3,
    'keywords' => [
        'fix_pattern' => '/\b(fix|bug|hotfix|patch|typo|oops)\b/i',
        'revert_pattern' => '/\b(revert)\b/i',
        'wip_pattern' => '/\b(wip|tmp|debug|hack)\b/i',
    ],
    'ai' => [
        'enabled' => false,
        'engine' => env('CODEMETRY_AI_ENGINE', 'openai'),
        'api_key' => env('CODEMETRY_AI_API_KEY'),
        'model' => env('CODEMETRY_AI_MODEL'),
    ],
];
```

## Acceptance checklist

- [x] ServiceProvider registers `Analyzer` as singleton
- [x] Config merged with defaults and publishable via `codemetry-config` tag
- [x] Command registered and discoverable via Artisan
- [x] `--format=json` outputs valid schema 1.0 JSON
- [x] `--format=table` outputs table with date, mood, score, confidence, reasons
- [x] Invalid repo path returns FAILURE exit code
- [x] CLI options (author, baseline-days, etc.) passed through to AnalysisRequest
- [x] Config values used as defaults when CLI options not provided
- [x] All 113 tests pass (104 core + 9 Laravel)

## Test results

```
$ ./vendor/bin/pest

  Tests:    113 passed (418 assertions)
  Duration: 5.82s
```

## Notes

- `orchestra/testbench` added to root `require-dev` because Composer path repos don't install sub-package dev dependencies
- Root `tests/Pest.php` created to bind Testbench TestCase for Laravel test directory (Pest discovers Pest.php relative to project root, not per-package)
- AI engine integration deferred to PLAN_10 — command accepts `--ai` flag but engine implementation not yet present
- Core package remains framework-agnostic; Laravel adapter only constructs DTOs and delegates
