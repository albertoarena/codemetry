<?php

declare(strict_types=1);

namespace Codemetry\WordPress;

/**
 * Resolves configuration from multiple sources with priority:
 * 1. CLI flags (handled by command)
 * 2. wp-cli.yml configuration
 * 3. Environment variables
 * 4. Package defaults
 */
final class ConfigResolver
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $this->config = $this->resolve();
    }

    /**
     * Get all configuration values.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get a configuration value by key.
     *
     * @param string $key Dot-notation key (e.g., 'ai.enabled')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Resolve configuration from all sources.
     *
     * @return array<string, mixed>
     */
    private function resolve(): array
    {
        // 1. Start with package defaults
        $config = require __DIR__ . '/../config/codemetry-defaults.php';

        // 2. Merge wp-cli.yml config (if WP-CLI is available)
        $config = $this->mergeWpCliConfig($config);

        // 3. Apply environment variable overrides
        $config = $this->applyEnvOverrides($config);

        return $config;
    }

    /**
     * Merge configuration from wp-cli.yml.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function mergeWpCliConfig(array $config): array
    {
        // Check if WP_CLI class exists and has config
        if (!class_exists('WP_CLI')) {
            return $config;
        }

        try {
            $wpCliConfig = \WP_CLI::get_config('codemetry');
            if (is_array($wpCliConfig)) {
                $config = $this->arrayMergeRecursive($config, $wpCliConfig);
            }
        } catch (\Throwable) {
            // WP_CLI::get_config may not be available in all contexts
        }

        return $config;
    }

    /**
     * Apply environment variable overrides.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function applyEnvOverrides(array $config): array
    {
        // AI configuration from environment
        $envMappings = [
            'CODEMETRY_AI_ENGINE' => ['ai', 'engine'],
            'CODEMETRY_AI_API_KEY' => ['ai', 'api_key'],
            'CODEMETRY_AI_MODEL' => ['ai', 'model'],
            'CODEMETRY_AI_BASE_URL' => ['ai', 'base_url'],
            'CODEMETRY_AI_TIMEOUT' => ['ai', 'timeout'],
            'CODEMETRY_AI_BATCH_SIZE' => ['ai', 'batch_size'],
        ];

        foreach ($envMappings as $envKey => $configPath) {
            $envValue = getenv($envKey);
            if ($envValue !== false && $envValue !== '') {
                $config = $this->setNestedValue($config, $configPath, $envValue);
            }
        }

        // Also expand ${VAR} syntax in existing config values
        $config = $this->expandEnvVars($config);

        // Convert numeric strings to integers where appropriate
        if (isset($config['ai']['timeout'])) {
            $config['ai']['timeout'] = (int) $config['ai']['timeout'];
        }
        if (isset($config['ai']['batch_size'])) {
            $config['ai']['batch_size'] = (int) $config['ai']['batch_size'];
        }

        return $config;
    }

    /**
     * Set a value at a nested path.
     *
     * @param array<string, mixed> $array
     * @param array<string> $path
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function setNestedValue(array $array, array $path, mixed $value): array
    {
        $current = &$array;
        foreach ($path as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;

        return $array;
    }

    /**
     * Expand ${VAR} syntax in configuration values.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function expandEnvVars(array $config): array
    {
        array_walk_recursive($config, function (&$value): void {
            if (is_string($value) && preg_match('/\$\{([A-Z_][A-Z0-9_]*)\}/', $value, $matches)) {
                $envValue = getenv($matches[1]);
                if ($envValue !== false) {
                    // Full replacement if entire value is ${VAR}
                    if ($value === '${' . $matches[1] . '}') {
                        $value = $envValue;
                    } else {
                        // Partial replacement
                        $value = str_replace('${' . $matches[1] . '}', $envValue, $value);
                    }
                } else {
                    // Env var not set, replace with null for full matches
                    if ($value === '${' . $matches[1] . '}') {
                        $value = null;
                    }
                }
            }
        });

        return $config;
    }

    /**
     * Recursively merge arrays, with later values overwriting earlier ones.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function arrayMergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->arrayMergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
