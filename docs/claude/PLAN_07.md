# PLAN_07 — Heuristic Scoring + Reasons/Confounders

## Status: COMPLETE

## Goal
Convert normalized metrics into a deterministic mood proxy with score, label, confidence, reasons, and confounders.

## What changed

### Files created

| File | Purpose |
|---|---|
| `packages/core/src/Scoring/HeuristicScorer.php` | Deterministic scoring: base 70, penalties/rewards, label mapping, confidence, reasons, confounders |
| `packages/core/tests/Scoring/HeuristicScorerTest.php` | 25 unit tests covering all scoring rules |

### Scoring recipe

**Base score:** 70

**Penalties:**
| Condition | Impact |
|---|---|
| Churn percentile ≥ 95 | -20 |
| Churn percentile 90–95 | -12 |
| Scatter percentile ≥ 90 | -10 |
| Follow-up fix density ≥ 95 | -25 |
| Follow-up fix density 90–95 | -15 |
| Revert count > 0 | -15 |
| WIP ratio ≥ 0.3 | -8 |

**Rewards:**
| Condition | Impact |
|---|---|
| Churn ≤ p25 AND fix density ≤ p25 | +5 |

**Label mapping:** 0–44 bad, 45–74 medium, 75–100 good

**Confidence:** base 0.6, +0.1 (commits≥3), +0.1 (follow-up ran), -0.2 (commits≤1), -0.1 per skipped key provider

**Confounders:**
- `large_refactor_suspected` — churn p95+ but fix density ≤ p50
- `formatting_or_rename_suspected` — churn p95+, files_touched p90+, fix density ≤ p25

## Acceptance checklist

- [x] Base score 70 with no penalties or rewards
- [x] Each penalty applied correctly at threshold boundaries
- [x] Reward applied for low churn + low fix density
- [x] Multiple penalties accumulate correctly
- [x] Score clamped to 0–100
- [x] Labels map correctly at boundaries (44→bad, 45→medium, 75→good)
- [x] Confidence calculation includes all factors
- [x] Confidence clamped to 0–1
- [x] Reasons sorted by magnitude, capped at 6
- [x] Confounders detected and deduplicated
- [x] Existing confounders preserved
- [x] Result includes raw signals and normalized data
- [x] All 97 tests pass (96 core + 1 laravel)

## Test results

```
$ ./vendor/bin/pest

  Tests:    97 passed (292 assertions)
```

## Notes

- Scorer is stateless and deterministic — same inputs always produce same outputs
- WIP ratio computed from raw signal values, not percentiles
- Revert penalty uses raw count (> 0), not percentile threshold
