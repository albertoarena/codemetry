# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Codemetry is a PHP library that analyzes Git repositories and produces a metrics-based "mood proxy" (bad/medium/good) for each day or time window. It estimates quality/strain/risk using measurable signals (churn, scatter, follow-up fixes, optional static-analysis totals) and reports a confidence score plus human-readable reasons. It does **not** infer emotions.

## Architecture

**Monorepo with two Composer packages:**

- `packages/core/` — Framework-agnostic analysis pipeline (`Codemetry\Core\*`)
- `packages/laravel/` — Thin Laravel adapter (`Codemetry\Laravel\*`)

**Core must never depend on Laravel.** The Laravel adapter constructs an `AnalysisRequest` DTO and delegates to `Codemetry\Core\Analyzer::analyze()`.

### Data Flow

```
Git Repo → GitRepoReader → RepoSnapshot
         → Signal Providers → SignalSet
         → Normalizer (with Baseline Cache) → NormalizedFeatureSet
         → HeuristicScorer → MoodResult (score, label, confidence, reasons, confounders)
         → Optional AI Engine → Enhanced explanation
         → AnalysisResult (JSON-ready, schema_version 1.0)
```

### Key Components

- **`Codemetry\Core\Analyzer`** — Main entry point: `analyze(string $repoPath, AnalysisRequest $request): AnalysisResult`
- **`Codemetry\Core\Git\GitRepoReader`** — Executes git commands via Symfony Process
- **`Codemetry\Core\Signals\SignalProvider`** — Plugin interface; providers: `ChangeShapeProvider`, `CommitMessageProvider`, `FollowUpFixProvider`, plus optional quality tool providers
- **`Codemetry\Core\Baseline\Normalizer`** — Computes z-scores and percentile ranks against a baseline period (default 56 days)
- **`Codemetry\Core\Scoring\HeuristicScorer`** — Deterministic scoring: starts at 70, applies penalties/rewards, clamps 0-100, maps to bad/medium/good
- **`Codemetry\Core\Ai\AiEngine`** — Optional, opt-in, metrics-only AI summarization (OpenAI, Anthropic, DeepSeek, Google)
- **`Codemetry\Core\Domain\*`** — Immutable DTOs: `AnalysisRequest`, `AnalysisResult`, `MoodResult`, `CommitInfo`, `RepoSnapshot`, `Signal`, `SignalSet`, etc.

### Baseline Cache Location

- Primary: `<repoPath>/.git/codemetry/cache-baseline.json`
- Fallback: `sys_get_temp_dir()/codemetry/<repoId>/cache-baseline.json`

## Tech Stack

- PHP 8.2+
- Symfony Process (git execution)
- DateTimeImmutable (no Carbon in core)
- Pest (testing)
- Orchestra Testbench (Laravel adapter tests only)

## Build & Test Commands

```bash
composer install                                          # Install dependencies

# Run tests
./vendor/bin/pest                                         # All tests
./vendor/bin/pest packages/core/tests                     # Core tests only
./vendor/bin/pest packages/laravel/tests                  # Laravel adapter tests only
./vendor/bin/pest --filter=TestClassName                   # Single test class
./vendor/bin/pest --filter=testMethodName                  # Single test method

# Laravel usage
php artisan codemetry:analyze --days=7                    # Table output
php artisan codemetry:analyze --days=7 --format=json      # JSON output
php artisan codemetry:analyze --days=7 --ai=1             # With AI (requires API keys)
```

## Implementation Guidelines

- Implement core first, following the order in CODEMETRY.md section 15
- Signal providers that fail (missing tools, command errors) must not break the pipeline — add `provider_skipped:<id>` confounder instead
- AI engines receive metrics only, never raw code or diffs
- When `--ai=1` is used without API keys, fall back gracefully and add `ai_unavailable` confounder
- New signal providers should only require registration in the provider list, no pipeline changes
- Keep DTOs immutable
- Stubs are acceptable for tool-based providers (phpstan/eslint/stylelint) in V1

## Git Commit Conventions

### Format
- type: short subject line (max 50 chars)
- Detailed body paragraph explaining what and why (not how).

### Rules
- No Claude attribution - NEVER include "Generated with Claude Code" or "Co-Authored-By: Claude"
- Keep first line under 50 characters
- Use heredoc for multi-line commit messages