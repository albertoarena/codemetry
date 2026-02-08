<?php

declare(strict_types=1);

use Codemetry\WordPress\CodemetryCommand;
use Symfony\Component\Process\Process;

function createWpTestRepo(): string
{
    $dir = sys_get_temp_dir() . '/codemetry-wp-test-' . uniqid();
    mkdir($dir, 0755, true);

    runWpGitInDir($dir, ['git', 'init']);
    runWpGitInDir($dir, ['git', 'config', 'user.email', 'test@example.com']);
    runWpGitInDir($dir, ['git', 'config', 'user.name', 'Test User']);

    return $dir;
}

function runWpGitInDir(string $dir, array $cmd): void
{
    (new Process($cmd, $dir))->mustRun();
}

function wpGitCommitInDir(string $dir, string $file, string $content, string $message, string $date): void
{
    $fullPath = $dir . '/' . $file;
    $parentDir = dirname($fullPath);
    if (!is_dir($parentDir)) {
        mkdir($parentDir, 0755, true);
    }
    file_put_contents($fullPath, $content);
    runWpGitInDir($dir, ['git', 'add', $file]);

    $env = array_merge($_ENV, [
        'GIT_AUTHOR_DATE' => $date,
        'GIT_COMMITTER_DATE' => $date,
    ]);

    (new Process(['git', 'commit', '-m', $message], $dir, $env))->mustRun();
}

function cleanupWpTestRepo(string $dir): void
{
    (new Process(['rm', '-rf', $dir]))->run();
}

test('command can be instantiated', function () {
    $command = new CodemetryCommand();

    expect($command)->toBeInstanceOf(CodemetryCommand::class);
});

test('command analyze produces output for valid repo', function () {
    $dir = createWpTestRepo();

    wpGitCommitInDir($dir, 'init.txt', 'init', 'init', '2024-01-10T10:00:00+00:00');
    wpGitCommitInDir($dir, 'file.php', "<?php\necho 1;\n", 'feat: add file', '2024-01-15T10:00:00+00:00');

    $command = new CodemetryCommand();

    // Capture output
    ob_start();
    $command->analyze([], [
        'repo' => $dir,
        'since' => '2024-01-15',
        'until' => '2024-01-16',
        'format' => 'json',
        'baseline-days' => '3',
    ]);
    $output = ob_get_clean();

    $json = json_decode($output, true);

    expect($json)->toBeArray()
        ->and($json)->toHaveKey('schema_version')
        ->and($json['schema_version'])->toBe('1.0')
        ->and($json)->toHaveKey('windows')
        ->and($json['windows'])->toHaveCount(1);

    cleanupWpTestRepo($dir);
});

test('command analyze handles multiple days', function () {
    $dir = createWpTestRepo();

    wpGitCommitInDir($dir, 'init.txt', 'init', 'init', '2024-01-01T10:00:00+00:00');
    wpGitCommitInDir($dir, 'file1.php', "<?php\n", 'feat: day 1', '2024-01-10T10:00:00+00:00');
    wpGitCommitInDir($dir, 'file2.php', "<?php\n", 'feat: day 2', '2024-01-11T10:00:00+00:00');
    wpGitCommitInDir($dir, 'file3.php', "<?php\n", 'feat: day 3', '2024-01-12T10:00:00+00:00');

    $command = new CodemetryCommand();

    ob_start();
    $command->analyze([], [
        'repo' => $dir,
        'since' => '2024-01-10',
        'until' => '2024-01-13',
        'format' => 'json',
        'baseline-days' => '5',
    ]);
    $output = ob_get_clean();

    $json = json_decode($output, true);

    expect($json['windows'])->toHaveCount(3)
        ->and($json['windows'][0]['window_label'])->toBe('2024-01-10')
        ->and($json['windows'][1]['window_label'])->toBe('2024-01-11')
        ->and($json['windows'][2]['window_label'])->toBe('2024-01-12');

    cleanupWpTestRepo($dir);
});

test('command config shows configuration', function () {
    $command = new CodemetryCommand();

    ob_start();
    $command->config([], ['format' => 'json']);
    $output = ob_get_clean();

    $json = json_decode($output, true);

    expect($json)->toBeArray()
        ->and($json)->toHaveKeys(['baseline_days', 'ai', 'keywords'])
        ->and($json['baseline_days'])->toBe(56);
});

test('command config masks api key', function () {
    putenv('CODEMETRY_AI_API_KEY=sk-very-long-secret-key-12345');

    $command = new CodemetryCommand();

    ob_start();
    $command->config([], ['format' => 'json']);
    $output = ob_get_clean();

    $json = json_decode($output, true);

    // API key should be masked
    expect($json['ai']['api_key'])->toContain('...')
        ->and($json['ai']['api_key'])->not->toBe('sk-very-long-secret-key-12345');

    putenv('CODEMETRY_AI_API_KEY');
});

test('command respects days option', function () {
    $dir = createWpTestRepo();

    // Create commits over multiple days
    wpGitCommitInDir($dir, 'init.txt', 'init', 'init', '2024-01-01T10:00:00+00:00');
    for ($i = 1; $i <= 10; $i++) {
        $date = sprintf('2024-01-%02d', $i);
        wpGitCommitInDir($dir, "file{$i}.php", "<?php\n", "feat: day {$i}", "{$date}T10:00:00+00:00");
    }

    $command = new CodemetryCommand();

    ob_start();
    $command->analyze([], [
        'repo' => $dir,
        'days' => '5',
        'until' => '2024-01-10',
        'format' => 'json',
        'baseline-days' => '3',
    ]);
    $output = ob_get_clean();

    $json = json_decode($output, true);

    expect($json['windows'])->toHaveCount(5);

    cleanupWpTestRepo($dir);
});

test('json output includes all required fields', function () {
    $dir = createWpTestRepo();

    wpGitCommitInDir($dir, 'init.txt', 'init', 'init', '2024-01-10T10:00:00+00:00');
    wpGitCommitInDir($dir, 'file.php', "<?php\necho 1;\n", 'feat: add file', '2024-01-15T10:00:00+00:00');

    $command = new CodemetryCommand();

    ob_start();
    $command->analyze([], [
        'repo' => $dir,
        'since' => '2024-01-15',
        'until' => '2024-01-16',
        'format' => 'json',
        'baseline-days' => '3',
    ]);
    $output = ob_get_clean();

    $json = json_decode($output, true);

    // Check structure
    expect($json)->toHaveKeys(['schema_version', 'repo_id', 'analyzed_at', 'request_summary', 'windows']);

    $window = $json['windows'][0];
    expect($window)->toHaveKeys(['window_label', 'mood_label', 'mood_score', 'confidence', 'reasons', 'confounders']);

    cleanupWpTestRepo($dir);
});
