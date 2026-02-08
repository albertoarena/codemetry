# PLAN_14: WordPress (WP-CLI) Package

## Goal

Create a WordPress adapter package (`codemetry/wordpress`) for WP-CLI integration, following the same thin adapter pattern as the Laravel package.

## Package Structure

```
packages/wordpress/
├── composer.json
├── README.md
├── config/
│   └── codemetry-defaults.php
├── src/
│   ├── CodemetryCommand.php
│   └── ConfigResolver.php
└── tests/
    ├── Pest.php
    ├── CommandTest.php
    └── ConfigResolverTest.php
```

## Key Components

### CodemetryCommand.php
- `wp codemetry analyze` - Main analysis command with all options
- `wp codemetry config` - Show current configuration
- `@when before_wp_load` - Runs without WordPress bootstrap for speed
- Git root auto-detection from current directory

### ConfigResolver.php
- Merges configuration from: CLI flags → wp-cli.yml → environment variables → defaults
- Supports `${VAR}` environment variable expansion
- Dot-notation key access

### Configuration Priority
1. CLI flags (`--days=7`, `--ai=1`)
2. wp-cli.yml (project or global)
3. Environment variables (`CODEMETRY_AI_API_KEY`)
4. Package defaults

## Command Options

Matching Laravel adapter:
- `--days=<n>` - Number of days (default: 7)
- `--since=<date>` - Start date (ISO 8601)
- `--until=<date>` - End date (ISO 8601)
- `--author=<name>` - Filter by author
- `--branch=<branch>` - Filter by branch
- `--format=<format>` - Output: table|json (default: table)
- `--ai=<0|1>` - Enable AI
- `--ai-engine=<engine>` - AI engine override
- `--baseline-days=<n>` - Override baseline
- `--follow-up-horizon=<n>` - Override horizon
- `--repo=<path>` - Repository path

## File Changes

### New Files
- `packages/wordpress/composer.json` - WP-CLI package with auto-discovery
- `packages/wordpress/src/CodemetryCommand.php` - Main command (~320 lines)
- `packages/wordpress/src/ConfigResolver.php` - Config merging (~180 lines)
- `packages/wordpress/config/codemetry-defaults.php` - Default values
- `packages/wordpress/README.md` - Documentation
- `packages/wordpress/tests/*` - Test files (15 tests)

### Updated Files
- `composer.json` - Add WordPress package to monorepo
- `phpunit.xml` - Add WordPress testsuite
- `.github/workflows/split.yml` - Add WordPress split, tags, releases
- `README.md` - Add WordPress installation and usage
- `website/src/content/docs/index.mdx` - Mention WordPress support
- `website/src/content/docs/getting-started/installation.mdx` - Add WordPress tabs
- `website/src/content/docs/getting-started/quickstart.mdx` - Add WordPress tabs

## Acceptance Checklist

- [x] Package structure created
- [x] CodemetryCommand with analyze and config subcommands
- [x] ConfigResolver with wp-cli.yml + env var support
- [x] All 15 WordPress tests pass (185 total)
- [x] Split workflow includes WordPress package
- [x] README updated with WordPress docs
- [x] Website updated with WordPress instructions
- [x] v1.4.0 released with WordPress package
- [x] codemetry-wordpress repo created and populated

## Verification

```bash
# Install
wp package install codemetry/wordpress

# Usage
wp codemetry analyze --days=7
wp codemetry analyze --format=json --ai=1
wp codemetry config
```

## Status

**COMPLETED** - Released in v1.4.0
