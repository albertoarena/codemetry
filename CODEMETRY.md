# CODEMETRY_CLAUDE.md — Claude-Optimized Implementation Plan (Codemetry)

> Purpose: This document is written for **Claude** (or similar coding agents) to implement Codemetry with minimal back-and-forth.
> Style: explicit steps, deterministic decisions, clear file tree, strict acceptance criteria.
> Guiding principle: **framework-agnostic core + thin Laravel adapter**.

---

## 0) One-paragraph product definition (use this exact wording in README)
Codemetry analyzes a Git repository and produces a **metrics-based “mood proxy”** (bad/medium/good) for each day or time window. It does **not** infer emotions. Instead it estimates *quality/strain/risk* using measurable signals such as churn, scatter, follow-up fixes, and optional static-analysis totals, and reports a confidence score plus human-readable reasons.

---

## 1) Deliverable overview (V1)
### Required (V1)
- **Core (framework-agnostic)** analysis pipeline:
  - Git reader
  - Windowing (daily)
  - Signal providers
  - Baseline + normalization
  - Heuristic scoring
  - Report output DTOs (JSON-ready)
- **Laravel adapter**:
  - ServiceProvider
  - Config publishing
  - Artisan command: `codemetry:analyze`
- **AI engine abstraction** (configurable), supporting structure for:
  - OpenAI, Anthropic(Claude), DeepSeek (+ optional Google)
  - In V1: engines can be implemented but should be **opt-in** and **metrics-only** input.
- Tests (unit + integration with a temporary git repo fixture)
- README (installation, usage, privacy, extension points)

### Not required (V1)
- Dashboard UI
- WordPress/WP-CLI, Symfony, CodeIgniter adapters (design for them, don’t build now)
- Line-level blame correlation
- CI integrations / GitHub API

---

## 2) Repository layout (monorepo suggested)
Create a single repo with two Composer packages:

```
codemetry/
  packages/
    core/
      src/
      tests/
      composer.json
      phpunit.xml
      README.md
    laravel/
      src/
      tests/
      config/
      composer.json
      README.md
  composer.json   (optional root, for dev)
  CODEMETRY_CLAUDE.md
```

If you prefer a single package, you may place the Laravel adapter under `src/Laravel/` but **keep core namespaces/framework dependencies separated**.

### Namespaces
- Core: `Codemetry\Core\...`
- Laravel adapter: `Codemetry\Laravel\...`

---

## 3) Deterministic tech choices (do not deviate)
- PHP: 8.2+
- Process execution: **symfony/process**
- Date handling: DateTimeImmutable (no Carbon in core)
- Testing: Pest (Orchestra Testbench only for Laravel adapter tests)
- Config format:
  - Laravel: config PHP array
  - Core: accept array config (Laravel passes config array to core)

---

## 4) Core public API (must implement)
### 4.1 Primary entry point
Create in core:

- `Codemetry\Core\Analyzer`

Method signature (final):

```php
public function analyze(string $repoPath, AnalysisRequest $request): AnalysisResult;
```

### 4.2 AnalysisRequest (DTO)
Must include:
- since: ?DateTimeImmutable
- until: ?DateTimeImmutable
- days: ?int
- author: ?string
- branch: ?string
- timezone: ?DateTimeZone
- baselineDays: int (default 56)
- followUpHorizonDays: int (default 3)
- aiEnabled: bool
- aiEngine: string (openai/anthropic/deepseek/google)
- outputFormat: string (json/table) (table used only by Laravel adapter)

Core must not care about CLI args; it only uses this DTO.

---

## 5) Data model (DTOs) — implement as immutable classes
Create under `packages/core/src/Domain`.

### 5.1 AnalysisWindow
- start, end, label
- helper: `durationSeconds()`

### 5.2 CommitInfo
- hash, authorName, authorEmail, authoredAt, subject
- insertions, deletions
- files: array<string> (touched)

### 5.3 RepoSnapshot
- window
- commits (CommitInfo[])
- filesTouched (unique list)
- totals: commitsCount, filesTouchedCount, added, deleted, churn

### 5.4 Signal / SignalSet
Signal:
- key (namespaced string)
- type: numeric|boolean|string
- value
- description

SignalSet:
- windowLabel
- signals: array<string, Signal>

### 5.5 NormalizedFeatureSet
- rawSignals: SignalSet
- normalized: array<string, float> (z + percentile)

### 5.6 MoodResult / AnalysisResult
MoodResult:
- windowLabel
- moodLabel: bad|medium|good
- moodScore: int 0..100
- confidence: float 0..1
- reasons: ReasonItem[]
- confounders: string[]
- rawSignals + normalized

AnalysisResult:
- repoId (hash)
- analyzedAt
- requestSummary (array)
- windows: MoodResult[]

ReasonItem:
- signalKey
- direction positive|negative
- magnitude float
- summary string

---

## 6) Git implementation (Core)
Create `Codemetry\Core\Git\GitRepoReader` using Symfony Process.

### 6.1 Repo detection
- `git rev-parse --is-inside-work-tree`
If not true, throw a clear exception.

### 6.2 Commit listing for window
Command template:
- `git log --since=<iso> --until=<iso> --pretty=format:%H%x09%an%x09%ae%x09%ad%x09%s --date=iso-strict`
Optional flags:
- if author provided: `--author=<author>`
- if branch provided: `-- <branch>` OR run `git log <branch> ...` (prefer `git log <branch> --since ...`)

### 6.3 Per-commit numstat
- `git show --numstat --pretty=format: <hash>`
Parse output:
- each line: `<added>\t<deleted>\t<path>`
- handle binary files indicated by `-`

### 6.4 Follow-up scanning (V1 approximation)
Given a window end and horizon days:
- for each file touched in window, count commits within horizon that touch the file
- count commits whose subject matches fix keywords
Avoid expensive blame operations.

Keywords (configurable):
- fix: `fix|bug|hotfix|patch|typo|oops`
- revert: `revert`
- wip: `wip|tmp|debug|hack`

---

## 7) Signal provider plugin system
### 7.1 Interface (must)
`Codemetry\Core\Signals\SignalProvider`

```php
public function id(): string;
public function provide(RepoSnapshot $snapshot, ProviderContext $ctx): SignalSet;
```

ProviderContext includes:
- config array
- git reader (for follow-up provider)
- logger (PSR-3 optional; implement NullLogger if absent)

### 7.2 Providers (V1 required)
Implement in `packages/core/src/Signals/Providers`:

1) `ChangeShapeProvider`
Signals:
- change.added
- change.deleted
- change.churn
- change.commits_count
- change.files_touched
- change.churn_per_commit
- change.scatter

2) `CommitMessageProvider`
Signals:
- msg.fix_keyword_count
- msg.revert_count
- msg.wip_count
- msg.fix_ratio (fix_count / commits_count)

3) `FollowUpFixProvider`
Signals:
- followup.horizon_days
- followup.touching_commits
- followup.fix_commits
- followup.fix_density (fix_commits / max(1, churn))

### 7.3 Optional providers (best-effort)
Implement stubs now (real parsing can be incremental):
- `PhpQualityProvider` (phpstan/psalm)
- `JsTsQualityProvider` (eslint/tsc)
- `CssQualityProvider` (stylelint)
Policy:
- if tool missing or command fails, do NOT fail analysis; add confounder `provider_skipped:<id>`

---

## 8) Baseline, normalization, and caching
### 8.1 Baseline builder
Compute baseline distributions using the same providers for the last `baselineDays` (default 56), daily windows.

Store distributions:
- per signal key: list of numeric values
Compute:
- mean, stddev
- percentile ranks (p50/p75/p90/p95)
Normalization outputs:
- `norm.<key>.z`
- `norm.<key>.pctl` (0..100)

### 8.2 Cache
Cache file location (core):
- `<repoPath>/.git/codemetry/cache-baseline.json` if writable
Fallback:
- system temp dir: `sys_get_temp_dir()/codemetry/<repoId>/cache-baseline.json`

Cache key includes:
- baselineDays, provider list + versions, config hash
If cache mismatch, recompute.

---

## 9) Scoring (Heuristic V1)
Implement `Codemetry\Core\Scoring\HeuristicScorer`.

### 9.1 Score recipe (deterministic)
Start `score = 70`.

Penalties:
- churn percentile:
  - p95+ => -20
  - p90–p95 => -12
- scatter percentile p90+ => -10
- follow-up fix density percentile:
  - p95+ => -25
  - p90–p95 => -15
- revert_count > 0 => -15
- wip_ratio high (>= 0.3) => -8
- tool-based errors (if present) => -10..-20 based on severity

Rewards:
- churn <= p25 AND followup_fix_density <= p25 => +5

Clamp score 0..100.

Label mapping:
- 0..44 bad
- 45..74 medium
- 75..100 good

Confidence:
- base 0.6
- +0.1 if commits_count >= 3
- +0.1 if follow-up provider ran
- -0.2 if commits_count <= 1
- -0.1 per skipped key provider (change_shape, follow_up, commit_message)
Clamp 0..1.

Reasons:
- include 3–6 top contributors by absolute impact.

Confounders:
- if churn p95+ but followup_fix_density <= p50 => `large_refactor_suspected`
- if churn very high and files_touched very high with low follow-up => `formatting_or_rename_suspected`

---

## 10) AI engine system (opt-in, metrics-only)
### 10.1 Goal
AI may:
- produce a nicer explanation
- optionally propose small adjustments (bounded)
AI must NEVER receive raw code or full diffs in V1.

### 10.2 Interface
Create `Codemetry\Core\Ai\AiEngine`:

```php
public function id(): string;
public function summarize(MoodAiInput $input): MoodAiSummary;
```

MoodAiInput contains:
- raw numeric signals
- normalized features
- commit message aggregates
- file extension histogram
- top paths (names only; max 20)

MoodAiSummary contains:
- explanation bullets (string[])
- optional adjustments:
  - score_delta in [-10, +10]
  - confidence_delta in [-0.1, +0.1]
  - label_override optional (rare; prefer no override)

### 10.3 Engines to implement (initial)
Implement classes (even if minimal) under `packages/core/src/Ai/Engines`:
- `OpenAiEngine`
- `AnthropicEngine`
- `DeepSeekEngine`
- `GoogleEngine` (optional)

Each engine should:
- read config: api_key, model, base_url(optional), timeout
- throw a specialized exception on failure; pipeline catches and continues (confounder: `ai_unavailable`).

### 10.4 Prompt template (use as default)
System:
“You are a software metrics assistant. You are not inferring emotions. You explain risk/quality signals.”

User:
Provide compact JSON with metrics + ask for:
- 5 bullets explaining mood proxy result
- any confounders
- optional score_delta within bounds

---

## 11) Laravel adapter (V1)
Create `Codemetry\Laravel\CodemetryServiceProvider` and `CodemetryAnalyzeCommand`.

### 11.1 Config publish
`config/codemetry.php` includes:
- baseline_days, follow_up_horizon_days
- keywords
- ai: enabled, engine, keys, models
- provider toggles
- optional tool commands

### 11.2 Command output
- `--format=table` prints Symfony Console table
- `--format=json` prints JSON only

The Laravel adapter must construct `AnalysisRequest` and call core `Analyzer`.

---

## 12) Dashboard readiness (V2 enablement now)
Introduce `ResultStore` interface in core now (but default is NullStore):
- `save(AnalysisResult $result): void`
Later, Laravel adapter can provide DB storage and a UI viewer.

Also: make the JSON schema stable and version it:
- include `schema_version: "1.0"` in AnalysisResult.

---

## 13) Tests (must)
### 13.1 Core unit tests
- Normalizer percentile correctness
- Heuristic scorer score/label mapping
- Keyword detection

### 13.2 Git integration tests (core)
Create a temporary git repo during test runtime:
- `git init`
- create commits with known timestamps and messages
- verify:
  - commits counted per day
  - churn totals correct
  - follow-up fix detection within horizon

### 13.3 Laravel adapter tests
Use Orchestra Testbench to verify:
- command registered
- `--format=json` outputs valid JSON

---

## 14) Acceptance criteria (Definition of Done)
A repo with commits must support:

1) Run in Laravel project:
- `php artisan codemetry:analyze --days=7`
Outputs a table with 7 rows (or fewer if no commits), each with mood label/score/confidence.

2) JSON mode:
- `php artisan codemetry:analyze --days=7 --format=json`
Outputs JSON matching schema_version 1.0 and includes reasons.

3) Works without AI keys:
- `--ai=1` without keys falls back gracefully and adds `ai_unavailable` confounder.

4) Extensible providers:
- Adding a new provider requires only registering it in a provider list (no pipeline rewrite).

---

## 15) Implementation order (follow exactly)
1) Core DTOs + Analyzer skeleton
2) GitRepoReader + RepoSnapshot building
3) ChangeShapeProvider
4) Baseline + Normalizer + Cache
5) FollowUpFixProvider
6) HeuristicScorer + reasons/confounders
7) CommitMessageProvider
8) Core tests
9) Laravel adapter (ServiceProvider + command)
10) AI abstractions + engine shells
11) README + polish

---

## 16) Notes for Claude (how to work)
- Prefer small commits.
- Implement core first; do not introduce Laravel dependencies in core.
- When unsure, pick the simplest approach that meets acceptance criteria.
- Do not over-engineer parsing for tool-based providers in V1; stubs are acceptable as long as they don’t break the pipeline.
- Keep prompts and AI payloads metrics-only.
