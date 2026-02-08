<?php

declare(strict_types=1);

namespace Codemetry\WordPress;

use Codemetry\Core\Analyzer;
use Codemetry\Core\Domain\AnalysisRequest;
use Codemetry\Core\Domain\AnalysisResult;
use Codemetry\Core\Domain\Confounder;
use Codemetry\Core\Exception\InvalidRepoException;

/**
 * Analyze Git repository and produce mood proxy metrics.
 *
 * ## EXAMPLES
 *
 *     # Analyze last 7 days
 *     wp codemetry analyze
 *
 *     # Analyze specific date range
 *     wp codemetry analyze --since=2024-01-01 --until=2024-01-15
 *
 *     # JSON output with AI enhancement
 *     wp codemetry analyze --format=json --ai=1
 *
 *     # Analyze external repository
 *     wp codemetry analyze --repo=/path/to/repo
 */
final class CodemetryCommand
{
    private Analyzer $analyzer;
    private ConfigResolver $config;

    public function __construct()
    {
        $this->analyzer = new Analyzer();
        $this->config = new ConfigResolver();
    }

    /**
     * Analyze repository and display mood metrics.
     *
     * ## OPTIONS
     *
     * [--days=<days>]
     * : Number of days to analyze.
     * ---
     * default: 7
     * ---
     *
     * [--since=<date>]
     * : Start date (ISO 8601 format, e.g., 2024-01-15).
     *
     * [--until=<date>]
     * : End date (ISO 8601 format).
     *
     * [--author=<name>]
     * : Filter commits by author name.
     *
     * [--branch=<branch>]
     * : Filter commits by branch.
     *
     * [--format=<format>]
     * : Output format.
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     *
     * [--ai=<enabled>]
     * : Enable AI explanation (0 or 1).
     *
     * [--ai-engine=<engine>]
     * : AI engine to use.
     * ---
     * options:
     *   - openai
     *   - anthropic
     *   - deepseek
     *   - google
     * ---
     *
     * [--baseline-days=<days>]
     * : Override baseline days for normalization.
     *
     * [--follow-up-horizon=<days>]
     * : Override follow-up horizon days.
     *
     * [--repo=<path>]
     * : Repository path. Defaults to detected git root or current directory.
     *
     * ## EXAMPLES
     *
     *     # Analyze last 7 days
     *     wp codemetry analyze
     *
     *     # Analyze with date range
     *     wp codemetry analyze --since=2024-01-01 --until=2024-01-31
     *
     *     # JSON output for CI/CD
     *     wp codemetry analyze --format=json --days=30
     *
     *     # With AI enhancement
     *     wp codemetry analyze --ai=1 --ai-engine=openai
     *
     * @when before_wp_load
     *
     * @param array<int, string> $args Positional arguments.
     * @param array<string, string> $assoc_args Named arguments.
     */
    public function analyze(array $args, array $assoc_args): void
    {
        $repoPath = $this->resolveRepoPath($assoc_args['repo'] ?? null);
        $format = $assoc_args['format'] ?? 'table';

        try {
            $request = $this->buildRequest($assoc_args);
            $externalConfig = $this->buildExternalConfig();
            $result = $this->analyzer->analyze($repoPath, $request, $externalConfig);
        } catch (InvalidRepoException $e) {
            $this->error('Invalid Git repository: ' . $e->getMessage());
            $this->log('  Hint: Ensure the path points to a valid Git repository with commit history.');
            $this->log('  Use --repo=/path/to/repo to specify a different repository.');
            exit(1);
        } catch (\InvalidArgumentException $e) {
            $this->error('Invalid argument: ' . $e->getMessage());
            $this->log('  Hint: Check date format (use ISO 8601, e.g., 2024-01-15).');
            exit(1);
        } catch (\Throwable $e) {
            $this->error('Analysis failed: ' . $e->getMessage());
            exit(1);
        }

        $this->warnIfAiUnavailable($request, $result);

        if ($format === 'json') {
            $this->log($result->toJson(JSON_PRETTY_PRINT));
            return;
        }

        $this->renderTable($result);
    }

    /**
     * Show current configuration.
     *
     * Displays the merged configuration from all sources (defaults, wp-cli.yml, environment).
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format.
     * ---
     * default: json
     * options:
     *   - json
     *   - dump
     * ---
     *
     * ## EXAMPLES
     *
     *     # Show config as JSON
     *     wp codemetry config
     *
     *     # Show config as PHP dump
     *     wp codemetry config --format=dump
     *
     * @when before_wp_load
     *
     * @param array<int, string> $args Positional arguments.
     * @param array<string, string> $assoc_args Named arguments.
     */
    public function config(array $args, array $assoc_args): void
    {
        $config = $this->config->all();
        $format = $assoc_args['format'] ?? 'json';

        // Mask sensitive values
        if (isset($config['ai']['api_key']) && $config['ai']['api_key'] !== null) {
            $key = $config['ai']['api_key'];
            $config['ai']['api_key'] = substr($key, 0, 8) . '...' . substr($key, -4);
        }

        if ($format === 'dump') {
            $this->log(print_r($config, true));
            return;
        }

        $this->log((string) json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Resolve the repository path to analyze.
     *
     * Priority:
     * 1. Explicit --repo flag
     * 2. Git root detected from current directory
     * 3. Current working directory
     */
    private function resolveRepoPath(?string $explicit): string
    {
        if ($explicit !== null) {
            return $explicit;
        }

        // Try to find git root from current directory
        $startDir = getcwd();
        if ($startDir === false) {
            $startDir = defined('ABSPATH') ? ABSPATH : '/';
        }

        $current = realpath($startDir);
        if ($current === false) {
            return $startDir;
        }

        while ($current !== '/' && $current !== '') {
            if (is_dir($current . '/.git')) {
                return $current;
            }
            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }
            $current = $parent;
        }

        // Fall back to start directory
        return $startDir;
    }

    /**
     * Build the analysis request from CLI arguments and config.
     *
     * @param array<string, string> $assoc_args
     */
    private function buildRequest(array $assoc_args): AnalysisRequest
    {
        $since = $this->parseDate($assoc_args['since'] ?? null, 'since');
        $until = $this->parseDate($assoc_args['until'] ?? null, 'until');

        $days = ($since !== null && $until !== null)
            ? null
            : (int) ($assoc_args['days'] ?? 7);

        $aiEnabled = isset($assoc_args['ai'])
            ? (bool) (int) $assoc_args['ai']
            : (bool) $this->config->get('ai.enabled', false);

        $aiEngine = $assoc_args['ai-engine']
            ?? $this->config->get('ai.engine', 'openai');

        return new AnalysisRequest(
            since: $since,
            until: $until,
            days: $days,
            author: $assoc_args['author'] ?? null,
            branch: $assoc_args['branch'] ?? null,
            baselineDays: (int) ($assoc_args['baseline-days'] ?? $this->config->get('baseline_days', 56)),
            followUpHorizonDays: (int) ($assoc_args['follow-up-horizon'] ?? $this->config->get('follow_up_horizon_days', 3)),
            aiEnabled: $aiEnabled,
            aiEngine: $aiEngine,
            outputFormat: $assoc_args['format'] ?? 'table',
        );
    }

    /**
     * Parse a date string to DateTimeImmutable.
     */
    private function parseDate(?string $value, string $optionName): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new \InvalidArgumentException(
                "Invalid --{$optionName} date: '{$value}'. Use ISO 8601 format (e.g., 2024-01-15)."
            );
        }
    }

    /**
     * Build external configuration for the analyzer.
     *
     * @return array<string, mixed>
     */
    private function buildExternalConfig(): array
    {
        $aiConfig = $this->config->get('ai', []);

        return [
            'keywords' => $this->config->get('keywords', []),
            'ai' => [
                'api_key' => $aiConfig['api_key'] ?? null,
                'model' => $aiConfig['model'] ?? null,
                'base_url' => $aiConfig['base_url'] ?? null,
                'timeout' => (int) ($aiConfig['timeout'] ?? 30),
                'batch_size' => (int) ($aiConfig['batch_size'] ?? 10),
            ],
        ];
    }

    /**
     * Warn if AI was requested but unavailable.
     */
    private function warnIfAiUnavailable(AnalysisRequest $request, AnalysisResult $result): void
    {
        if (!$request->aiEnabled || empty($result->windows)) {
            return;
        }

        foreach ($result->windows as $mood) {
            if (in_array(Confounder::AI_UNAVAILABLE, $mood->confounders, true)) {
                $this->warning('AI enhancement was requested but unavailable.');

                $lastError = $this->analyzer->getLastAiError();
                if ($lastError !== null) {
                    $this->log("  Error: {$lastError}");
                } else {
                    $this->log('  Hint: Set CODEMETRY_AI_API_KEY environment variable or configure in wp-cli.yml.');
                }

                return;
            }
        }
    }

    /**
     * Render results as a table.
     */
    private function renderTable(AnalysisResult $result): void
    {
        if (empty($result->windows)) {
            $this->warning('No data found for the specified time range.');
            return;
        }

        $rows = [];

        foreach ($result->windows as $mood) {
            $reasonCount = count($mood->reasons);
            $topReasons = array_slice($mood->reasons, 0, 3);
            $reasonsList = array_map(fn($r) => $r->summary, $topReasons);
            $reasonsText = implode('; ', $reasonsList) ?: '-';
            if ($reasonCount > 3) {
                $reasonsText .= ' (+' . ($reasonCount - 3) . ' more)';
            }

            $rows[] = [
                $mood->windowLabel,
                $mood->moodLabel->value,
                $mood->moodScore . '%',
                number_format($mood->confidence * 100, 0) . '%',
                $reasonsText,
            ];
        }

        // Render table using WP-CLI if available, otherwise simple output
        if (class_exists('WP_CLI') && method_exists('WP_CLI\Utils', 'format_items')) {
            $items = [];
            foreach ($result->windows as $i => $mood) {
                $items[] = [
                    'Date' => $rows[$i][0],
                    'Mood' => $rows[$i][1],
                    'Score' => $rows[$i][2],
                    'Confidence' => $rows[$i][3],
                    'Top Reasons' => $rows[$i][4],
                ];
            }
            \WP_CLI\Utils\format_items('table', $items, ['Date', 'Mood', 'Score', 'Confidence', 'Top Reasons']);
        } else {
            // Fallback: simple text output
            $this->log(str_pad('Date', 12) . str_pad('Mood', 8) . str_pad('Score', 8) . str_pad('Conf', 8) . 'Top Reasons');
            $this->log(str_repeat('-', 80));
            foreach ($rows as $row) {
                $this->log(str_pad($row[0], 12) . str_pad($row[1], 8) . str_pad($row[2], 8) . str_pad($row[3], 8) . $row[4]);
            }
        }

        $this->renderAiSummaries($result);
    }

    /**
     * Render AI summaries if available.
     */
    private function renderAiSummaries(AnalysisResult $result): void
    {
        foreach ($result->windows as $mood) {
            if ($mood->aiSummary !== null && !empty($mood->aiSummary->explanationBullets)) {
                $this->log('');
                $this->success("AI Insights for {$mood->windowLabel}:");
                foreach ($mood->aiSummary->explanationBullets as $bullet) {
                    $this->log("  - {$bullet}");
                }
            }
        }
    }

    /**
     * Output a standard log message.
     */
    private function log(string $message): void
    {
        if (class_exists('WP_CLI')) {
            \WP_CLI::log($message);
        } else {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Output a success message.
     */
    private function success(string $message): void
    {
        if (class_exists('WP_CLI')) {
            \WP_CLI::success($message);
        } else {
            echo "[SUCCESS] {$message}" . PHP_EOL;
        }
    }

    /**
     * Output a warning message.
     */
    private function warning(string $message): void
    {
        if (class_exists('WP_CLI')) {
            \WP_CLI::warning($message);
        } else {
            echo "[WARNING] {$message}" . PHP_EOL;
        }
    }

    /**
     * Output an error message.
     */
    private function error(string $message): void
    {
        if (class_exists('WP_CLI')) {
            \WP_CLI::error($message, false);
        } else {
            fwrite(STDERR, "[ERROR] {$message}" . PHP_EOL);
        }
    }
}
