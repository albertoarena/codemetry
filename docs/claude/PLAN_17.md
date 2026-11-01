# PLAN_17: Current AI Default Models and Docs Refresh

## Goal

Replace retired AI default models so `--ai=1` works again with every engine, update the OpenAI request parameters for current models, and bring the docs site up to date.

> PLAN_15 (optional quality-tool providers) is still a draft and remains parked.

## Background (checked 2026-11-01 against provider docs)

| Engine | Old default | Status | New default (official replacement) |
|---|---|---|---|
| Anthropic | `claude-sonnet-4-20250514` | Retired 2026-06-15 | `claude-sonnet-5-5` |
| Google | `gemini-1.5-flash` | Shut down 2025-09-29 | `gemini-3.8-flash` |
| DeepSeek | `deepseek-chat` | Retired 2026-07-24 (per third-party reports; no longer in DeepSeek's docs) | `deepseek-flash` |
| OpenAI | `gpt-4o-mini` | Shuts down 2027-01-14 | `gpt-6-luna` |

Three of the four engines fail with their default model today. The failure is not fatal (the analysis continues and adds `ai_unavailable`), but AI summaries never appear.

## Scope

- New default model in each engine
- OpenAI: send `max_completion_tokens` instead of the deprecated `max_tokens`, and stop sending `temperature` (newer OpenAI models may reject non-default values). DeepSeek is OpenAI-compatible but keeps its current parameters
- Tests pinning each engine's default model
- Docs site:
  - AI engine pages: current model lists, defaults and links to provider pricing (hardcoded cost estimates removed)
  - Replace the non-existent `--ai-model` CLI option with the `CODEMETRY_AI_MODEL` environment variable
  - Roadmap: add WordPress, current models
  - Installation: Packagist note (all three packages are now listed)
  - Adapters example: `symfony/console ^7.0|^8.0`
- `packages/wordpress/README.md` example model

Out of scope: adding an `--ai-model` CLI option (could be a follow-up).

## File Changes

- `packages/core/src/Ai/Engines/{OpenAi,Anthropic,Google,DeepSeek}Engine.php`
- `packages/core/tests/Ai/EnginesTest.php`
- `packages/wordpress/README.md`
- `website/src/content/docs/ai-engines/{index,openai,anthropic,google,deepseek}.mdx`
- `website/src/content/docs/roadmap/index.mdx`
- `website/src/content/docs/getting-started/installation.mdx`
- `website/src/content/docs/extending/adapters.mdx`

## Commands to Run

```bash
./vendor/bin/pest
php85 vendor/bin/pest
cd website && npm run build      # expect ~23 pages
# Owner, with an OpenAI key (live check of the new parameters):
OPENAI_API_KEY=... php artisan codemetry:analyze --days=3 --ai=1 --format=json
```

## Acceptance Checklist

- [x] All four engines default to a current model
- [x] OpenAI payload uses `max_completion_tokens` and no `temperature`
- [x] Tests cover each engine's default model
- [x] No retired model names or `--ai-model` left in docs
- [x] Tests pass; website builds
- [ ] Owner confirms a live OpenAI call works with the new defaults
- [ ] CI green on the PR
- [ ] Plan file updated with final Status / Review Notes

## Results

- 190 tests pass (185 existing + 5 new) on PHP 8.4 and 8.5
- Website builds locally: 23 pages
- Docs sweep: no retired model names, `--ai-model`, `dev-master` or hardcoded cost estimates left in `website/` or the READMEs

## Review Notes

**What changed**
- Defaults: `gpt-6-luna`, `claude-sonnet-5-5`, `gemini-3.8-flash`, `deepseek-flash`
- OpenAI requests send `max_completion_tokens` and no `temperature`
- Docs: current model tables, `CODEMETRY_AI_MODEL` replaces the documented-but-non-existent `--ai-model` flag, cost estimates replaced by links to provider pricing, roadmap lists WordPress, the broken "Installing from GitHub" section (it pointed Composer at the monorepo root package) replaced by `dev-main` instructions

**Risks / assumptions**
- The OpenAI parameter change and new defaults have not been tested against live APIs (no keys available to Claude); the owner's live check covers OpenAI only
- `max_completion_tokens` counts reasoning tokens too. If `gpt-6-luna` reasons before answering, 1000 (single) / 4000 (batch) may truncate output; the live check will show this
- DeepSeek's `deepseek-chat` retirement date comes from third-party reports; DeepSeek's own docs simply no longer list it
- Provider model IDs were taken from each provider's docs on 2026-11-01 and will go stale again; each engine page now says so

**Follow-ups**
- Release v1.5.1 after the live check (core changed)
- Optional: add a real `--ai-model` CLI option to the Laravel and WordPress commands

## Status: Implemented — awaiting owner's live OpenAI check
