# PLAN_06 — Baseline Builder + Normalizer + Cache

## Status: COMPLETE

## Goal
Normalize signals against historical baseline distributions using z-scores and percentile ranks, with caching.

## What changed

### Files created

| File | Purpose |
|---|---|
| `packages/core/src/Baseline/BaselineDistribution.php` | Per-signal stats: mean, stddev, sorted values, z-score, percentile rank |
| `packages/core/src/Baseline/Baseline.php` | Collection of distributions keyed by signal name |
| `packages/core/src/Baseline/BaselineBuilder.php` | Generates daily windows over baseline period, collects signals, builds distributions |
| `packages/core/src/Baseline/Normalizer.php` | Normalizes a SignalSet against a Baseline → NormalizedFeatureSet |
| `packages/core/src/Baseline/BaselineCache.php` | Saves/loads baseline as JSON with cache key validation |
| `packages/core/tests/Baseline/BaselineDistributionTest.php` | 8 tests: mean, stddev, z-score, percentile, serialization |
| `packages/core/tests/Baseline/NormalizerTest.php` | 5 tests: normalization, non-numeric skip, missing baseline, naming convention |
| `packages/core/tests/Baseline/BaselineCacheTest.php` | 6 tests: save/load, key mismatch, provider/days change, file location |

### Architecture

**BaselineBuilder** generates N daily windows before the analysis date, builds snapshots and collects signals from the same provider registry. Each numeric signal's values are aggregated into a `BaselineDistribution`.

**Normalizer** takes a current-window `SignalSet` and a `Baseline`, producing a `NormalizedFeatureSet` with `norm.<key>.z` and `norm.<key>.pctl` for each numeric signal.

**BaselineCache** stores baseline JSON under:
- Primary: `<repoPath>/.git/codemetry/cache-baseline.json`
- Fallback: `sys_get_temp_dir()/codemetry/<repoId>/cache-baseline.json`

Cache key = MD5 of (baselineDays + providerIds + config). Mismatched key triggers recompute.

### Stats implementation

- **Mean**: arithmetic mean
- **Stddev**: sample standard deviation (n-1 denominator)
- **Z-score**: `(value - mean) / stddev` (returns 0 when stddev=0)
- **Percentile rank**: count of baseline values ≤ current value, divided by total, ×100

## Acceptance checklist

- [x] BaselineDistribution correctly computes mean, stddev, z-score, percentile
- [x] Percentile computation is deterministic
- [x] Normalizer produces `norm.<key>.z` and `norm.<key>.pctl` keys
- [x] Non-numeric signals are skipped
- [x] Cache is saved and loaded correctly
- [x] Cache is invalidated on config/provider/days changes
- [x] Cache stored under `.git/codemetry/` when writable
- [x] Serialization round-trips (toArray → fromArray) preserve values
- [x] All 72 tests pass (71 core + 1 laravel)

## Test results

```
$ ./vendor/bin/pest

  Tests:    72 passed (251 assertions)
```

## Notes

- Sample stddev (n-1) used rather than population stddev for small sample sizes
- Empty distributions return z=0 and percentile=50 as safe defaults
- BaselineBuilder reuses the same ProviderRegistry as the main analysis pipeline
