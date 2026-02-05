# PLAN_04 — Signal System + Base Providers

## Status: COMPLETE

## Goal
Implement the signal provider plugin system with ChangeShapeProvider and CommitMessageProvider.

## What changed

### Files created

| File | Purpose |
|---|---|
| `packages/core/src/Signals/SignalProvider.php` | Interface: `id()` + `provide(RepoSnapshot, ProviderContext): SignalSet` |
| `packages/core/src/Signals/ProviderContext.php` | Immutable context: repoPath, config array, optional GitRepoReader |
| `packages/core/src/Signals/ProviderRegistry.php` | Registers providers, collects signals, catches failures as confounders |
| `packages/core/src/Signals/Providers/ChangeShapeProvider.php` | 7 signals: added, deleted, churn, commits_count, files_touched, churn_per_commit, scatter |
| `packages/core/src/Signals/Providers/CommitMessageProvider.php` | 4 signals: fix_keyword_count, revert_count, wip_count, fix_ratio |
| `packages/core/tests/Signals/ChangeShapeProviderTest.php` | 4 tests for change shape signals |
| `packages/core/tests/Signals/CommitMessageProviderTest.php` | 7 tests for keyword detection |
| `packages/core/tests/Signals/ProviderRegistryTest.php` | 3 tests for registry collection and failure handling |

### Signal details

**ChangeShapeProvider** (`change_shape`):
- `change.added` — total lines added
- `change.deleted` — total lines deleted
- `change.churn` — added + deleted
- `change.commits_count` — number of commits
- `change.files_touched` — unique files
- `change.churn_per_commit` — average churn per commit
- `change.scatter` — unique directories touched

**CommitMessageProvider** (`commit_message`):
- `msg.fix_keyword_count` — commits matching `fix|bug|hotfix|patch|typo|oops`
- `msg.revert_count` — commits matching `revert`
- `msg.wip_count` — commits matching `wip|tmp|debug|hack`
- `msg.fix_ratio` — fix_count / commits_count

## Acceptance checklist

- [x] SignalProvider interface defined with `id()` and `provide()`
- [x] ProviderRegistry collects from multiple providers
- [x] Failed providers add `provider_skipped:<id>` confounder without breaking pipeline
- [x] ChangeShapeProvider produces all 7 signals with correct values
- [x] CommitMessageProvider detects keywords case-insensitively with word boundaries
- [x] Empty snapshots handled gracefully (zero values)
- [x] All 47 tests pass (46 core + 1 laravel)

## Test results

```
$ ./vendor/bin/pest

  Tests:    47 passed (188 assertions)
```

## Notes

- Keyword patterns are configurable via `config['keywords']` in ProviderContext
- Word boundary matching (`\b`) ensures partial words like "prefix" or "debugging" don't match
- Scatter is defined as count of unique parent directories touched
- FollowUpFixProvider deferred to PLAN_05 per task plan
