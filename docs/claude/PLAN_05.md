# PLAN_05 — FollowUpFixProvider

## Status: COMPLETE

## Goal
Detect rework by scanning commits in a configurable horizon period after the analysis window.

## What changed

### Files created

| File | Purpose |
|---|---|
| `packages/core/src/Signals/Providers/FollowUpFixProvider.php` | Scans horizon commits for follow-up fixes to window files |
| `packages/core/tests/Signals/FollowUpFixProviderTest.php` | 6 integration tests using temporary git repos |

### Signal details (`follow_up_fix`)

| Signal | Description |
|---|---|
| `followup.horizon_days` | Number of days scanned after window (configurable, default 3) |
| `followup.touching_commits` | Horizon commits that touch at least one file from the window |
| `followup.fix_commits` | Touching commits whose subject matches fix keywords |
| `followup.fix_density` | `fix_commits / max(1, churn)` |

### How it works

1. Creates a horizon window: `[window.end, window.end + horizonDays]`
2. Uses `GitRepoReader::getCommits()` to fetch commits in the horizon
3. Filters to commits touching files from the analysis window
4. Counts fix-keyword matches among those commits
5. Computes fix density relative to window churn

### Graceful degradation

- No GitRepoReader → returns zero signals
- Empty snapshot (no files touched) → returns zero signals
- Provider can be safely registered in the registry without breaking the pipeline

## Acceptance checklist

- [x] Detects follow-up fix commits touching window files
- [x] Ignores horizon commits that touch unrelated files
- [x] Fix density correctly divides by churn
- [x] Configurable horizon days respected
- [x] Graceful when GitRepoReader is absent
- [x] Graceful when snapshot has no files
- [x] All 53 tests pass (52 core + 1 laravel)

## Test results

```
$ ./vendor/bin/pest

  Tests:    53 passed (206 assertions)
```

## Notes

- Fix keyword pattern is configurable via `config['keywords']['fix_pattern']`
- Horizon days configurable via `config['follow_up_horizon_days']`
- Uses the same fix pattern as CommitMessageProvider by default
