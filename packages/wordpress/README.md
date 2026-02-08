# Codemetry for WordPress (WP-CLI)

WP-CLI adapter for [Codemetry](https://github.com/albertoarena/codemetry) - Git repository mood-proxy analysis.

## Requirements

- PHP 8.2+
- WP-CLI 2.8+
- Git

## Installation

### Via WP-CLI Package Manager

```bash
wp package install codemetry/wordpress
```

### Via Composer

```bash
composer require codemetry/wordpress --dev
```

## Usage

### Basic Analysis

```bash
# Analyze last 7 days
wp codemetry analyze

# Analyze last 30 days
wp codemetry analyze --days=30

# Analyze specific date range
wp codemetry analyze --since=2024-01-01 --until=2024-01-31
```

### Output Formats

```bash
# Table output (default)
wp codemetry analyze --days=7

# JSON output (for CI/CD)
wp codemetry analyze --days=7 --format=json
```

### Filtering

```bash
# Filter by author
wp codemetry analyze --days=7 --author="Jane Doe"

# Filter by branch
wp codemetry analyze --days=7 --branch=main
```

### AI Enhancement

```bash
# Enable AI explanations
wp codemetry analyze --days=7 --ai=1

# Specify AI engine
wp codemetry analyze --days=7 --ai=1 --ai-engine=anthropic
```

### Repository Path

By default, Codemetry detects the Git root from the current directory. You can specify a different repository:

```bash
# Analyze a different repository
wp codemetry analyze --repo=/path/to/repo
```

### View Configuration

```bash
# Show current configuration
wp codemetry config

# Show as JSON
wp codemetry config --format=json
```

## Configuration

### Via wp-cli.yml

Create or edit `wp-cli.yml` in your project root or `~/.wp-cli/config.yml`:

```yaml
codemetry:
  baseline_days: 56
  follow_up_horizon_days: 3
  ai:
    enabled: false
    engine: openai
    api_key: ${CODEMETRY_AI_API_KEY}
    batch_size: 10
```

### Via Environment Variables

```bash
export CODEMETRY_AI_ENGINE=openai
export CODEMETRY_AI_API_KEY=sk-...
export CODEMETRY_AI_MODEL=gpt-4o-mini
export CODEMETRY_AI_TIMEOUT=30
export CODEMETRY_AI_BATCH_SIZE=10
```

### Configuration Priority

1. CLI flags (highest priority)
2. wp-cli.yml
3. Environment variables
4. Package defaults (lowest priority)

## Command Reference

### wp codemetry analyze

Analyze repository and display mood metrics.

**Options:**

| Option | Description | Default |
|--------|-------------|---------|
| `--days=<n>` | Number of days to analyze | 7 |
| `--since=<date>` | Start date (ISO 8601) | - |
| `--until=<date>` | End date (ISO 8601) | - |
| `--author=<name>` | Filter by author | - |
| `--branch=<branch>` | Filter by branch | - |
| `--format=<format>` | Output: table, json | table |
| `--ai=<0\|1>` | Enable AI explanations | 0 |
| `--ai-engine=<engine>` | AI engine to use | openai |
| `--baseline-days=<n>` | Override baseline days | 56 |
| `--follow-up-horizon=<n>` | Override follow-up horizon | 3 |
| `--repo=<path>` | Repository path | auto-detect |

### wp codemetry config

Show current configuration (merged from all sources).

**Options:**

| Option | Description | Default |
|--------|-------------|---------|
| `--format=<format>` | Output: json, dump | json |

## Example Output

### Table Format

```
+------------+--------+-------+------------+--------------------------------+
| Date       | Mood   | Score | Confidence | Top Reasons                    |
+------------+--------+-------+------------+--------------------------------+
| 2024-01-15 | good   | 78%   | 80%        | Low churn; stable patterns     |
| 2024-01-14 | medium | 55%   | 70%        | Elevated scatter               |
| 2024-01-13 | bad    | 32%   | 85%        | High churn; multiple fixes     |
+------------+--------+-------+------------+--------------------------------+
```

### JSON Format

```json
{
  "schema_version": "1.0",
  "repo_id": "abc123...",
  "analyzed_at": "2024-01-15T12:00:00Z",
  "windows": [
    {
      "window_label": "2024-01-15",
      "mood_label": "good",
      "mood_score": 78,
      "confidence": 0.80,
      "reasons": [...],
      "confounders": []
    }
  ]
}
```

## Privacy

- AI engines receive **aggregated metrics only** — never source code or diffs
- All analysis runs locally via Git commands
- No data sent to external services unless AI is explicitly enabled

## License

MIT
