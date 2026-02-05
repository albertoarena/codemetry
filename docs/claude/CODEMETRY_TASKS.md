# CODEMETRY_TASKS.md — Claude Implementation Task List

This document defines the step-by-step implementation plan for Codemetry.

> Each task must be fully implemented, tested, reviewed, and explicitly approved before proceeding to the next task.

---

## PLAN_01 — Repository & Package Scaffolding

**Goal:** Create monorepo structure and Composer setup.

Tasks:
- Create directories:
  - `packages/core/src`
  - `packages/core/tests`
  - `packages/laravel/src`
  - `packages/laravel/tests`
  - `packages/laravel/config`
- Add `composer.json` for both packages
- Configure PSR-4 autoloading:
  - `Codemetry\Core\`
  - `Codemetry\Laravel\`
- Setup Pest for core
- Setup Orchestra Testbench for Laravel adapter

Acceptance:
- `composer install` works
- Autoload works
- Tests boot successfully

---

## PLAN_02 — Core DTOs + Stable JSON Schema

**Goal:** Define immutable domain structures.

Implement:
- AnalysisRequest
- AnalysisWindow
- CommitInfo
- RepoSnapshot
- Signal, SignalSet
- NormalizedFeatureSet
- ReasonItem
- MoodResult
- AnalysisResult (include `schema_version = "1.0"`)

Acceptance:
- A dummy AnalysisResult serializes correctly to JSON
- JSON structure is stable

---

## PLAN_03 — GitRepoReader + Snapshot Builder

**Goal:** Build RepoSnapshot from real Git repo.

Implement:
- Repo validation
- Commit listing (since/until/author)
- Per-commit numstat parsing
- SnapshotBuilder

Tests:
- Create temporary git repo
- Add 2–3 commits
- Validate churn + file counts

Acceptance:
- Snapshot totals match expected values

---

## PLAN_04 — Signal System + Base Providers

**Goal:** Produce measurable signals.

Implement:
- SignalProvider interface
- Provider registry
- ChangeShapeProvider
- CommitMessageProvider

Acceptance:
- Known snapshot produces expected signals
- Providers unit tested

---

## PLAN_05 — FollowUpFixProvider

**Goal:** Detect rework via follow-up commits.

Implement:
- Horizon scanning
- Keyword detection (fix, revert, wip)
- Fix density calculation

Acceptance:
- Integration test confirms follow-up detection

---

## PLAN_06 — Baseline Builder + Normalizer + Cache

**Goal:** Normalize signals against historical baseline.

Implement:
- Baseline daily window generation
- Mean/stddev calculation
- Percentile ranks
- Cache file (under `.git/codemetry/` or temp fallback)

Acceptance:
- Percentiles deterministic
- Cache reused when unchanged

---

## PLAN_07 — Heuristic Scoring

**Goal:** Convert normalized metrics into mood proxy.

Implement:
- Deterministic penalty/reward system
- Score 0–100
- Label mapping (bad/medium/good)
- Confidence calculation
- Reasons extraction
- Confounder detection

Acceptance:
- Unit tests verify scoring logic

---

## PLAN_08 — Core Analyzer Orchestration

**Goal:** Full pipeline execution.

Implement:
- `Analyzer::analyze()`
- Window iteration
- Signal aggregation
- Baseline normalization
- Scoring
- JSON output

Acceptance:
- End-to-end test produces valid AnalysisResult JSON

---

## PLAN_09 — Laravel Adapter

**Goal:** Provide Laravel integration.

Implement:
- CodemetryServiceProvider
- Config publish (`config/codemetry.php`)
- `codemetry:analyze` Artisan command
- Table + JSON output

Acceptance:
- Command works in Testbench
- JSON output valid

---

## PLAN_10 — AI Engine Abstraction

**Goal:** Optional AI explanation layer.

Implement:
- AiEngine interface
- Engine factory
- Engines:
  - OpenAI
  - Anthropic (Claude)
  - DeepSeek
  - Google (optional)
- Metrics-only payload
- Graceful fallback if unavailable

Acceptance:
- AI disabled by default
- `--ai=1` does not crash without keys
- Engine switchable via config
