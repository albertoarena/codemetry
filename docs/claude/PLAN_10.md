# PLAN_10 — AI Engine Abstraction

## Status: COMPLETE

## Goal
Optional AI explanation layer that enhances mood results with human-readable explanations. Engines receive metrics only (never raw code or diffs) and produce bounded adjustments.

## What changed

### Files created

| File | Purpose |
|---|---|
| `packages/core/src/Ai/AiEngine.php` | Interface for AI engines |
| `packages/core/src/Ai/AiEngineException.php` | AI-specific exception with factory methods |
| `packages/core/src/Ai/AiEngineFactory.php` | Factory to create engines by identifier |
| `packages/core/src/Ai/MoodAiInput.php` | Metrics-only input DTO for AI engines |
| `packages/core/src/Ai/MoodAiSummary.php` | AI output with bounded score/confidence adjustments |
| `packages/core/src/Ai/Engines/AbstractAiEngine.php` | Base class with HTTP and prompt handling |
| `packages/core/src/Ai/Engines/OpenAiEngine.php` | OpenAI API implementation (GPT-4, GPT-3.5) |
| `packages/core/src/Ai/Engines/AnthropicEngine.php` | Anthropic API implementation (Claude) |
| `packages/core/src/Ai/Engines/DeepSeekEngine.php` | DeepSeek API implementation |
| `packages/core/src/Ai/Engines/GoogleEngine.php` | Google Generative AI implementation (Gemini) |
| `packages/core/tests/Ai/AiEngineExceptionTest.php` | Exception factory tests |
| `packages/core/tests/Ai/AiEngineFactoryTest.php` | Factory tests |
| `packages/core/tests/Ai/MoodAiInputTest.php` | Input DTO tests |
| `packages/core/tests/Ai/MoodAiSummaryTest.php` | Summary DTO tests |
| `packages/core/tests/Ai/EnginesTest.php` | Engine implementation tests |
| `packages/core/tests/Ai/AnalyzerAiIntegrationTest.php` | Integration tests |

### Files modified

| File | Change |
|---|---|
| `packages/core/src/Analyzer.php` | Added AI engine resolution and enhancement |
| `packages/core/src/Domain/MoodResult.php` | Added `aiSummary` field and `withAiSummary()` method |
| `packages/laravel/src/CodemetryAnalyzeCommand.php` | Passes AI config to analyzer |

### API

```php
// AiEngine interface
interface AiEngine {
    public function id(): string;
    public function summarize(MoodAiInput $input): MoodAiSummary;
}

// Factory usage
$engine = AiEngineFactory::create('openai', [
    'api_key' => 'sk-...',
    'model' => 'gpt-4o-mini',
]);

// Analyzer with AI
$result = $analyzer->analyze($repoPath, $request, [
    'ai' => [
        'api_key' => 'sk-...',
        'model' => 'gpt-4o-mini',
    ],
]);
```

### MoodAiInput (metrics-only)

Contains:
- `windowLabel`, `moodLabel`, `moodScore`, `confidence`
- `rawSignals` — key-value signal data
- `normalized` — z-scores and percentiles
- `reasons` — scoring reasons
- `confounders` — detected confounders
- `commitsCount`
- `extensionHistogram` — file type distribution (top 10)
- `topPaths` — touched file paths (top 20, names only)

Never contains raw code, diffs, or commit content.

### MoodAiSummary (bounded adjustments)

Contains:
- `explanationBullets` — human-readable explanation points
- `scoreDelta` — bounded to [-10, +10]
- `confidenceDelta` — bounded to [-0.1, +0.1]
- `labelOverride` — optional, rarely used

### Supported engines

| Engine | Model default | API format |
|---|---|---|
| `openai` | `gpt-4o-mini` | OpenAI Chat Completions |
| `anthropic` | `claude-sonnet-4-20250514` | Anthropic Messages |
| `deepseek` | `deepseek-chat` | OpenAI-compatible |
| `google` | `gemini-1.5-flash` | Google Generative AI |

### Graceful fallback

When AI is enabled but unavailable (missing API key, request failure):
- Analysis continues without crashing
- `ai_unavailable` confounder added to results
- No AI summary in output

## Acceptance checklist

- [x] AI disabled by default
- [x] `--ai=1` does not crash without keys
- [x] Engine switchable via config (`aiEngine` in request)
- [x] MoodAiInput contains metrics only, never code
- [x] MoodAiSummary bounds enforced (score ±10, confidence ±0.1)
- [x] All engines throw on missing API key
- [x] Factory throws for unknown engine
- [x] Analyzer gracefully handles AI failures
- [x] All 145 tests pass (136 core + 9 Laravel)

## Test results

```
$ ./vendor/bin/pest

  Tests:    145 passed (497 assertions)
  Duration: 4.18s
```

## Notes

- Engines use cURL for HTTP requests (native PHP, no external HTTP client dependency)
- System prompt instructs AI to explain risk/quality signals, not infer emotions
- JSON response format requested where supported (OpenAI, DeepSeek, Google)
- Anthropic uses text response with JSON parsing
- All API calls have configurable timeout (default 30s)
- Base URL configurable per engine for custom/proxy endpoints
