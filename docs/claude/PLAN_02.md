# PLAN_02 — Core DTOs + Stable JSON Schema

## Status: COMPLETE

## Goal
Define immutable domain structures under `packages/core/src/Domain/` and verify stable JSON serialization with `schema_version = "1.0"`.

## What changed

### DTOs created (`packages/core/src/Domain/`)

| Class | Purpose |
|---|---|
| `AnalysisRequest` | Immutable request parameters (since, until, days, author, branch, etc.) |
| `AnalysisWindow` | Time window with start, end, label, durationSeconds() |
| `CommitInfo` | Single commit: hash, author, subject, insertions/deletions, files |
| `RepoSnapshot` | Window + commits + computed totals; factory `fromCommits()` |
| `Signal` | Single signal: key, type, value, description |
| `SignalSet` | Collection of signals for a window; get/has/merge |
| `NormalizedFeatureSet` | Raw signals + normalized z-scores and percentiles |
| `ReasonItem` | Scoring reason: signalKey, direction, magnitude, summary |
| `MoodResult` | Window result: label, score, confidence, reasons, confounders |
| `AnalysisResult` | Top-level output: schema_version 1.0, repoId, windows[], toJson() |

### Enums created

| Enum | Values |
|---|---|
| `SignalType` | numeric, boolean, string |
| `Direction` | positive, negative |
| `MoodLabel` | bad, medium, good (with `fromScore()` factory) |

### Tests created (`packages/core/tests/Domain/`)

| Test file | Tests |
|---|---|
| `AnalysisRequestTest.php` | defaults, all params, toSummary |
| `AnalysisWindowTest.php` | duration, JSON |
| `CommitInfoTest.php` | data storage, JSON, defaults |
| `RepoSnapshotTest.php` | fromCommits totals, empty, JSON |
| `SignalTest.php` | signal data, JSON, set get/has/merge |
| `NormalizedFeatureSetTest.php` | z-score/percentile accessors, JSON |
| `MoodResultTest.php` | label mapping, JSON |
| `AnalysisResultTest.php` | schema version, full serialization, toJson |

## Acceptance checklist

- [x] All DTOs are immutable (readonly classes)
- [x] AnalysisResult includes `schema_version = "1.0"`
- [x] Dummy AnalysisResult serializes correctly to JSON
- [x] Full AnalysisResult with nested windows/signals/reasons serializes correctly
- [x] JSON structure is stable and deterministic
- [x] All 24 tests pass (23 core + 1 laravel)

## Test results

```
$ ./vendor/bin/pest

  Tests:    24 passed (117 assertions)
```

## Notes

- All DTOs implement `JsonSerializable` for clean `json_encode()` output
- `RepoSnapshot::fromCommits()` factory deduplicates files and computes totals
- `MoodLabel::fromScore()` maps 0-44 → bad, 45-74 → medium, 75-100 → good
- `NormalizedFeatureSet` provides `zScore()` and `percentile()` convenience accessors
- `AnalysisResult::toJson()` wraps `json_encode` with `JSON_THROW_ON_ERROR`
