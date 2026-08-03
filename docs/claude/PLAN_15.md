# PLAN_15: Optional Quality-Tool Signal Providers (stubs)

## Goal

Implement the optional, best-effort quality-tool signal providers described in
`CODEMETRY.md §7.3` that were never turned into a plan and are therefore missing
from the codebase:

- `PhpQualityProvider` (phpstan/psalm)
- `JsTsQualityProvider` (eslint/tsc)
- `CssQualityProvider` (stylelint)

Per the spec these are **optional / best-effort**: implement stubs now, with real
parsing kept incremental. When the underlying tool is missing, disabled, or its
command fails, analysis MUST NOT fail — the provider adds a
`provider_skipped:<id>` confounder and contributes no signals.

## Scope

- Add three new providers under `packages/core/src/Signals/Providers/`.
- Register them in `Analyzer::defaultRegistry()` (registration only — no pipeline
  changes, honoring the "new providers require registration only" rule).
- Providers are **off by default**: they only run when explicitly enabled via
  config, otherwise they self-skip (raise → `provider_skipped:<id>`). This keeps
  default analysis output and existing tests unchanged.
- Emit static-analysis total signals so the baseline/normalizer can treat them
  like any other numeric signal (the spec already references "optional
  static-analysis totals").
- Unit tests for each provider (enabled, disabled/skipped, tool-missing).

**Out of scope:** real invocation/parsing of phpstan/psalm/eslint/tsc/stylelint
output. Stubs return zeroed/`null` totals unless a caller injects results via
config. Wiring these into the Laravel/WordPress CLI as flags is a follow-up.

## Design

### Signal keys (numeric)

| Provider | id | Signals |
|----------|-----|---------|
| `PhpQualityProvider` | `php_quality` | `quality.php.errors`, `quality.php.warnings` |
| `JsTsQualityProvider` | `js_ts_quality` | `quality.jsts.errors`, `quality.jsts.warnings` |
| `CssQualityProvider` | `css_quality` | `quality.css.errors`, `quality.css.warnings` |

### Enable / skip policy

Each provider reads its config slice from `ProviderContext::$config['quality']`:

```php
'quality' => [
    'php'   => ['enabled' => false, 'errors' => null, 'warnings' => null],
    'js_ts' => ['enabled' => false, ...],
    'css'   => ['enabled' => false, ...],
]
```

- `enabled !== true` → provider `throw`s a skip signal so `ProviderRegistry`
  records `provider_skipped:<id>` (matches the existing catch in
  `ProviderRegistry::collect()`).
- `enabled === true` with injected totals → emit those totals as signals.
- `enabled === true` but no tool/totals available → skip (confounder), never fail.

This reuses the existing `try/catch` in `ProviderRegistry` — no registry change.

## File Changes

### New

| File | Purpose |
|------|---------|
| `packages/core/src/Signals/Providers/AbstractQualityProvider.php` | Shared enable/skip + signal-building logic |
| `packages/core/src/Signals/Providers/PhpQualityProvider.php` | phpstan/psalm stub |
| `packages/core/src/Signals/Providers/JsTsQualityProvider.php` | eslint/tsc stub |
| `packages/core/src/Signals/Providers/CssQualityProvider.php` | stylelint stub |
| `packages/core/tests/Signals/PhpQualityProviderTest.php` | Unit tests |
| `packages/core/tests/Signals/JsTsQualityProviderTest.php` | Unit tests |
| `packages/core/tests/Signals/CssQualityProviderTest.php` | Unit tests |

### Modified

| File | Change |
|------|--------|
| `packages/core/src/Analyzer.php` | Register the 3 providers in `defaultRegistry()` |

## Commands to run

```bash
./vendor/bin/pest packages/core/tests/Signals        # New + existing provider tests
./vendor/bin/pest packages/core/tests                 # Full core suite (no regressions)
./vendor/bin/pest                                     # All packages
```

## Acceptance checklist

- [ ] Three providers implemented against `SignalProvider` interface
- [ ] Disabled by default: default `Analyzer` run produces identical output to before,
      except a `provider_skipped:<id>` confounder for each of the 3 (best-effort, expected)
- [ ] Enabled-with-totals path emits `quality.*` signals
- [ ] Tool-missing / no-totals path skips gracefully (no exception escapes the pipeline)
- [ ] Registration-only change to `Analyzer`; no other pipeline files touched
- [ ] All existing tests still pass; new tests pass
- [ ] Plan file updated with final Status / Review Notes

## Review Notes

_To be completed after implementation._

- **What changed:** _pending_
- **Risks/assumptions:** Adding 3 skip confounders by default slightly changes the
  confounders array on every result. Decide whether "off by default" means
  _not registered_ (zero output change) or _registered but self-skipping_
  (confounder appears). Current draft favors registered-but-skipping to satisfy
  §7.3's "add confounder `provider_skipped:<id>`"; revisit if it noisily affects
  confidence scoring.
- **Follow-ups:** Real tool invocation/parsing; CLI flags in Laravel & WordPress
  adapters to toggle/enable quality providers and pass tool output.

## Status

**Current:** Draft — awaiting approval before implementation
**Approved:** No
