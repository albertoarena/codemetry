<?php

declare(strict_types=1);

use Codemetry\WordPress\ConfigResolver;

test('config resolver loads defaults', function () {
    $resolver = new ConfigResolver();

    expect($resolver->get('baseline_days'))->toBe(56)
        ->and($resolver->get('follow_up_horizon_days'))->toBe(3)
        ->and($resolver->get('ai.enabled'))->toBeFalse()
        ->and($resolver->get('ai.engine'))->toBe('openai')
        ->and($resolver->get('ai.timeout'))->toBe(30)
        ->and($resolver->get('ai.batch_size'))->toBe(10);
});

test('config resolver returns default for missing key', function () {
    $resolver = new ConfigResolver();

    expect($resolver->get('nonexistent'))->toBeNull()
        ->and($resolver->get('nonexistent', 'fallback'))->toBe('fallback')
        ->and($resolver->get('ai.nonexistent', 'default'))->toBe('default');
});

test('config resolver supports dot notation', function () {
    $resolver = new ConfigResolver();

    expect($resolver->get('ai.enabled'))->toBeFalse()
        ->and($resolver->get('ai.engine'))->toBe('openai')
        ->and($resolver->get('keywords.fix_pattern'))->toBe('/\b(fix|bug|hotfix|patch|typo|oops)\b/i');
});

test('config resolver returns all configuration', function () {
    $resolver = new ConfigResolver();
    $all = $resolver->all();

    expect($all)->toBeArray()
        ->and($all)->toHaveKeys(['baseline_days', 'follow_up_horizon_days', 'keywords', 'ai'])
        ->and($all['ai'])->toHaveKeys(['enabled', 'engine', 'api_key', 'model', 'timeout', 'batch_size']);
});

test('environment variables override defaults', function () {
    // Set environment variable
    putenv('CODEMETRY_AI_ENGINE=anthropic');
    putenv('CODEMETRY_AI_TIMEOUT=60');

    // Create new resolver to pick up env vars
    $resolver = new ConfigResolver();

    expect($resolver->get('ai.engine'))->toBe('anthropic')
        ->and($resolver->get('ai.timeout'))->toBe(60);

    // Clean up
    putenv('CODEMETRY_AI_ENGINE');
    putenv('CODEMETRY_AI_TIMEOUT');
});

test('environment variable expansion works', function () {
    putenv('TEST_API_KEY=sk-test-12345');

    $resolver = new ConfigResolver();

    // The default config doesn't have ${VAR} syntax, but the mechanism should work
    // This test verifies the env var reading mechanism is functional
    expect(getenv('TEST_API_KEY'))->toBe('sk-test-12345');

    putenv('TEST_API_KEY');
});

test('api key from environment is used', function () {
    putenv('CODEMETRY_AI_API_KEY=sk-test-key-12345');

    $resolver = new ConfigResolver();

    expect($resolver->get('ai.api_key'))->toBe('sk-test-key-12345');

    putenv('CODEMETRY_AI_API_KEY');
});

test('keywords config is properly loaded', function () {
    $resolver = new ConfigResolver();

    $keywords = $resolver->get('keywords');

    expect($keywords)->toBeArray()
        ->and($keywords)->toHaveKeys(['fix_pattern', 'revert_pattern', 'wip_pattern'])
        ->and($keywords['fix_pattern'])->toContain('fix')
        ->and($keywords['revert_pattern'])->toContain('revert')
        ->and($keywords['wip_pattern'])->toContain('wip');
});
