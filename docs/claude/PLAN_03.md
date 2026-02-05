# PLAN_03 — GitRepoReader + Snapshot Builder

## Status: COMPLETE

## Goal
Build `RepoSnapshot` from a real Git repository using Symfony Process.

## What changed

### Files created

| File | Purpose |
|---|---|
| `packages/core/src/Git/GitRepoReader.php` | Git interaction via Symfony Process: repo validation, commit listing, numstat parsing, snapshot building |
| `packages/core/src/Exception/InvalidRepoException.php` | Thrown when path doesn't exist or isn't a git repo |
| `packages/core/src/Exception/GitCommandException.php` | Thrown when a git command fails |
| `packages/core/tests/Git/GitRepoReaderTest.php` | Integration tests using temporary git repos |

### Key methods on `GitRepoReader`

- `validateRepo(string $repoPath): void` — checks `git rev-parse --is-inside-work-tree`
- `getCommits(repoPath, window, ?author, ?branch): array<CommitInfo>` — git log with numstat
- `getNumstat(repoPath, hash): array{insertions, deletions, files}` — parses `git show --numstat`
- `buildSnapshot(repoPath, window, ?author, ?branch): RepoSnapshot` — combines getCommits + fromCommits

## Acceptance checklist

- [x] Repo validation detects valid/invalid repos
- [x] Commit listing respects since/until window boundaries
- [x] Author filtering works correctly
- [x] Numstat parsing extracts insertions, deletions, and file paths
- [x] Binary files (with `-` stats) are handled
- [x] Snapshot totals (commitsCount, filesTouchedCount, added, deleted, churn) match expected values
- [x] Empty windows produce empty snapshots
- [x] All 34 tests pass (33 core + 1 laravel)

## Test results

```
$ ./vendor/bin/pest

  Tests:    34 passed (148 assertions)
```

### Git integration tests

| Test | Validates |
|---|---|
| validates a valid git repo | `validateRepo` succeeds on real repo |
| throws for non-existent path | `InvalidRepoException` with message |
| throws for non-git directory | `InvalidRepoException` with message |
| lists commits within a window | 2 of 3 commits fall in window |
| returns empty array when no commits in window | empty result |
| filters commits by author | only matching author returned |
| parses numstat for insertions and deletions | 3 insertions, 0 deletions |
| counts insertions and deletions on file modification | 2 insertions, 1 deletion |
| builds snapshot with correct totals | 3 commits, 2 files, churn=8 |
| builds empty snapshot when no commits in window | all zeroes |

## Notes

- Tests create real temporary git repos with known timestamps using `GIT_AUTHOR_DATE` / `GIT_COMMITTER_DATE` env vars
- Branch filtering is supported but not tested with a separate branch yet (will be validated in later plans)
- Follow-up scanning (section 6.4 of CODEMETRY.md) will be implemented in PLAN_05
