# PLAN_08 — Core Analyzer Orchestration

## Status: COMPLETE

## Goal
Full end-to-end pipeline execution via `Analyzer::analyze()`.

## What changed

### Files created

| File | Purpose |
|---|---|
| `packages/core/src/Analyzer.php` | Main entry point orchestrating the full pipeline |
| `packages/core/tests/AnalyzerTest.php` | 7 end-to-end integration tests with real git repos |

### Pipeline flow

```
AnalysisRequest
  → resolveWindows (since/until/days → daily AnalysisWindow[])
  → resolveBaseline (cache check → BaselineBuilder → cache save)
  → for each window:
      → GitRepoReader::buildSnapshot()
      → ProviderRegistry::collect() (ChangeShape + CommitMessage + FollowUpFix)
      → Normalizer::normalize()
      → HeuristicScorer::score()
      → MoodResult
  → AnalysisResult (schema_version 1.0, JSON-ready)
```

### Public API

```php
$analyzer = new Analyzer();
$result = $analyzer->analyze($repoPath, new AnalysisRequest(days: 7));
echo $result->toJson(JSON_PRETTY_PRINT);
```

### Window resolution logic

| Input | Behavior |
|---|---|
| `since` + `until` | Generate daily windows between them |
| `until` + `days` | Count back `days` from `until` |
| `since` only | From `since` to now |
| `days` only | Count back `days` from now |
| Nothing | Default 7 days from now |

## Acceptance checklist

- [x] `Analyzer::analyze()` produces valid `AnalysisResult`
- [x] JSON output matches schema_version 1.0 with all expected fields
- [x] Window iteration produces correct date labels
- [x] `days` parameter works correctly
- [x] Author filtering works end-to-end
- [x] Empty windows handled gracefully (zero commits, valid scores)
- [x] Invalid repo path throws `InvalidRepoException`
- [x] Baseline cache created and reused on second run
- [x] All 15 signals present in output (7 change + 4 message + 4 followup)
- [x] All 104 tests pass (103 core + 1 laravel)

## Test results

```
$ ./vendor/bin/pest

  Tests:    104 passed (401 assertions)
```

## Notes

- Default provider registry includes ChangeShapeProvider, CommitMessageProvider, FollowUpFixProvider
- Custom registries can be injected via constructor for extensibility
- Baseline is computed against the earliest analysis window's start date
- AI engine integration deferred to PLAN_10
