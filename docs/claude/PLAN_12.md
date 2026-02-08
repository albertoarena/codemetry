# PLAN_12: Website Documentation Alignment & CLI Enhancement

## Goal

Align website documentation with the current codebase and add missing `--ai-engine` CLI option.

## Scope

1. Add `--ai-engine` CLI option to Laravel command
2. Add `base_url` and `timeout` to Laravel config file
3. Update website documentation to reflect current features
4. Add tests for new CLI option

## File Changes

### Code Changes

| File | Change |
|------|--------|
| `packages/laravel/src/CodemetryAnalyzeCommand.php` | Added `--ai-engine` option to signature and `buildRequest()` |
| `packages/laravel/config/codemetry.php` | Added `base_url` and `timeout` AI config options |
| `packages/laravel/tests/ExampleTest.php` | Added 2 tests for `--ai-engine` option |

### Documentation Changes

| File | Change |
|------|--------|
| `website/src/content/docs/getting-started/cli-options.mdx` | Added `--ai-engine` option, clarified `--days` behavior |
| `website/src/content/docs/configuration/index.mdx` | Added `base_url`, `timeout` config and env vars |
| `website/src/content/docs/getting-started/installation.mdx` | Updated config example with new options |

## Commands Run

```bash
./vendor/bin/pest                           # All 156 tests pass
./vendor/bin/pest packages/laravel/tests    # 11 Laravel tests pass
```

## Acceptance Checklist

- [x] `--ai-engine` CLI option added and working
- [x] `--ai-engine` overrides config value
- [x] `base_url` and `timeout` added to config file
- [x] Environment variables documented (`CODEMETRY_AI_BASE_URL`, `CODEMETRY_AI_TIMEOUT`)
- [x] CLI options table updated with `--ai-engine`
- [x] `--days` behavior clarified in docs (ignored when both `--since` and `--until` set)
- [x] Config examples updated in installation docs
- [x] Tests added for new CLI option
- [x] All 156 tests pass

## Review Notes

### What Changed

1. **CLI Enhancement**: Added `--ai-engine` option allowing users to specify AI engine directly from command line (e.g., `--ai-engine=anthropic`)

2. **Config Completeness**: Added `base_url` and `timeout` options to published config, matching what the command code already supported

3. **Documentation Alignment**: Fixed discrepancy where docs showed `--ai-engine` but it wasn't implemented

### Issues Fixed

- Documentation claimed `--ai-engine` existed but it wasn't implemented
- `base_url` and `timeout` were used in code but not exposed in config file
- `--days` behavior with date ranges wasn't clear in options table

### Risks/Assumptions

- None - backward compatible additions only

## Status

**COMPLETED** - All acceptance criteria satisfied, tests pass.
