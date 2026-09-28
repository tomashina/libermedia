#!/usr/bin/env php
<?php

declare(strict_types=1);

function fail(string $message, int $code = 1): void {
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

/**
 * @return array{0:string,1:string,2:int}
 */
function runProcess(array $command, ?string $stdin = null): array {
    $temporaryDirectory = sys_get_temp_dir();
    $stdoutPath = tempnam($temporaryDirectory, 'libermedia-out-');
    $stderrPath = tempnam($temporaryDirectory, 'libermedia-err-');
    $stdinPath = null;

    if ($stdoutPath === false || $stderrPath === false) {
        fail('Unable to create temporary files for a child process.');
    }

    if ($stdin !== null) {
        $stdinPath = tempnam($temporaryDirectory, 'libermedia-in-');

        if ($stdinPath === false || file_put_contents($stdinPath, $stdin) === false) {
            @unlink($stdoutPath);
            @unlink($stderrPath);
            fail('Unable to prepare input for a child process.');
        }
    }

    $descriptorSpec = [
        0 => ['file', $stdinPath ?? '/dev/null', 'r'],
        1 => ['file', $stdoutPath, 'w'],
        2 => ['file', $stderrPath, 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes);

    if (!is_resource($process)) {
        @unlink($stdoutPath);
        @unlink($stderrPath);

        if ($stdinPath !== null) {
            @unlink($stdinPath);
        }

        fail('Unable to start: ' . implode(' ', $command));
    }

    $status = proc_close($process);
    $stdout = file_get_contents($stdoutPath);
    $stderr = file_get_contents($stderrPath);

    @unlink($stdoutPath);
    @unlink($stderrPath);

    if ($stdinPath !== null) {
        @unlink($stdinPath);
    }

    if ($stdout === false || $stderr === false) {
        fail('Unable to read child-process output.');
    }

    return [$stdout, $stderr, $status];
}

function startsWith(string $value, string $prefix): bool {
    return strncmp($value, $prefix, strlen($prefix)) === 0;
}

function loadDeployConfig(string $path): array {
    if (!is_file($path)) {
        fail('Missing .deploy.env. Copy .deploy.env.example and set the private-key path.');
    }

    $config = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || startsWith($line, '#')) {
            continue;
        }

        $position = strpos($line, '=');

        if ($position === false) {
            fail('Invalid line in .deploy.env: ' . $line);
        }

        $key = trim(substr($line, 0, $position));
        $value = trim(substr($line, $position + 1));
        $config[$key] = $value;
    }

    return $config;
}

function requireConfig(array $config, string $key): string {
    $environmentValue = getenv($key);
    $value = $environmentValue !== false && $environmentValue !== ''
        ? $environmentValue
        : ($config[$key] ?? '');

    if ($value === '') {
        fail('Missing deployment setting: ' . $key);
    }

    return $value;
}

function resolveCommit(string $reference): string {
    [$stdout, $stderr, $status] = runProcess(['git', 'rev-parse', '--verify', $reference . '^{commit}']);

    if ($status !== 0) {
        fail('Unknown Git reference "' . $reference . '": ' . trim($stderr));
    }

    return trim($stdout);
}

function isDeployablePath(string $path): bool {
    if (preg_match('/[\r\n]/', $path) || strpos($path, '..') !== false) {
        fail('Unsafe path in Git diff: ' . $path);
    }

    if (startsWith($path, 'upload/')) {
        $protected = [
            'upload/config.php',
            'upload/admin/config.php',
            'upload/LocalValetDriver.php',
            'upload/.ftpquota',
        ];

        if (in_array($path, $protected, true) || substr($path, -12) === '.example.php') {
            return false;
        }

        foreach ([
            'upload/image/',
            'upload/sitemaps/',
            'upload/system/storage/cache/',
            'upload/system/storage/download/',
            'upload/system/storage/logs/',
            'upload/system/storage/modification/',
            'upload/system/storage/session/',
            'upload/system/storage/upload/',
        ] as $prefix) {
            if (startsWith($path, $prefix)) {
                return false;
            }
        }

        return true;
    }

    if (startsWith($path, 'storageunbtbl/vendor/')) {
        return true;
    }

    return (bool)preg_match('#^storageunbtbl/(cache|download|logs|modification|session|upload)/index\.html$#', $path);
}

function parseGitChanges(string $fromCommit, string $toCommit): array {
    [$stdout, $stderr, $status] = runProcess([
        'git',
        'diff',
        '--name-status',
        '-z',
        '--find-renames',
        $fromCommit . '..' . $toCommit,
    ]);

    if ($status !== 0) {
        fail('Unable to calculate deployment diff: ' . trim($stderr));
    }

    $parts = explode("\0", $stdout);
    $index = 0;
    $uploads = [];
    $deletions = [];
    $requiredBackups = [];
    $newPaths = [];

    while ($index < count($parts) && $parts[$index] !== '') {
        $statusCode = $parts[$index++];
        $kind = $statusCode[0] ?? '';

        if ($kind === 'R' || $kind === 'C') {
            $oldPath = $parts[$index++] ?? '';
            $newPath = $parts[$index++] ?? '';

            if ($kind === 'R' && isDeployablePath($oldPath)) {
                $deletions[$oldPath] = true;
                $requiredBackups[$oldPath] = true;
            }

            if (isDeployablePath($newPath)) {
                $uploads[$newPath] = true;
                $newPaths[$newPath] = true;
            }

            continue;
        }

        $path = $parts[$index++] ?? '';

        if ($path === '' || !isDeployablePath($path)) {
            continue;
        }

        if ($kind === 'D') {
            $deletions[$path] = true;
            $requiredBackups[$path] = true;
        } elseif ($kind === 'A') {
            $uploads[$path] = true;
            $newPaths[$path] = true;
        } elseif (in_array($kind, ['M', 'T'], true)) {
            $uploads[$path] = true;
            $requiredBackups[$path] = true;
        }
    }

    ksort($uploads);
    ksort($deletions);
    ksort($requiredBackups);
    ksort($newPaths);

    return [
        array_keys($uploads),
        array_keys($deletions),
        array_keys($requiredBackups),
        array_keys($newPaths),
    ];
}

function batchQuote(string $value): string {
    return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
}

function isRemoteMissingMessage(string $output): bool {
    return (bool)preg_match('/(?:No such file|not found)/i', $output);
}

function remotePath(string $remoteRoot, string $relativePath): string {
    return rtrim($remoteRoot, '/') . '/' . ltrim($relativePath, '/');
}

function remoteDirectories(string $remoteRoot, array $paths): array {
    $directories = [];

    foreach ($paths as $path) {
        $directory = dirname($path);

        while ($directory !== '.' && $directory !== '') {
            $directories[remotePath($remoteRoot, $directory)] = true;
            $directory = dirname($directory);
        }
    }

    $directories = array_keys($directories);
    usort($directories, static function (string $left, string $right): int {
        return substr_count($left, '/') <=> substr_count($right, '/');
    });

    return $directories;
}

/**
 * @return array{0:bool,1:string}
 */
function runSftp(array $baseCommand, array $commands): array {
    [$stdout, $stderr, $status] = runProcess($baseCommand, implode("\n", $commands) . "\nbye\n");

    return [$status === 0, trim($stdout . "\n" . $stderr)];
}

function healthCheck(array $urls): bool {
    foreach ($urls as $url) {
        [$stdout, , $status] = runProcess([
            'curl',
            '--silent',
            '--show-error',
            '--location',
            '--max-time',
            '20',
            '--output',
            '/dev/null',
            '--write-out',
            '%{http_code}',
            $url,
        ]);

        $httpCode = (int)trim($stdout);

        if ($status !== 0 || $httpCode < 200 || $httpCode >= 400) {
            fwrite(STDERR, sprintf("Health check failed: %s returned %d\n", $url, $httpCode));
            return false;
        }

        printf("Health check OK: %s (%d)\n", $url, $httpCode);
    }

    return true;
}

$options = getopt('', ['from:', 'to:', 'apply', 'allow-delete', 'help']);

if (isset($options['help'])) {
    echo "Usage: php tools/deploy-sftp.php --from=<ref> [--to=<ref>] [--apply] [--allow-delete]\n";
    exit(0);
}

if (!isset($options['from']) || !is_string($options['from'])) {
    fail('The --from Git reference is required. Use --help for usage.');
}

$toReference = isset($options['to']) && is_string($options['to']) ? $options['to'] : 'HEAD';
[$rootOutput, $rootError, $rootStatus] = runProcess(['git', 'rev-parse', '--show-toplevel']);

if ($rootStatus !== 0) {
    fail('This command must run inside the Liber Media Git repository: ' . trim($rootError));
}

$repositoryRoot = trim($rootOutput);
chdir($repositoryRoot);

$fromCommit = resolveCommit($options['from']);
$toCommit = resolveCommit($toReference);
[$uploads, $deletions, $requiredBackups, $newPaths] = parseGitChanges($fromCommit, $toCommit);

printf("Deploy range: %s..%s\n", substr($fromCommit, 0, 12), substr($toCommit, 0, 12));

foreach ($uploads as $path) {
    echo "UPLOAD  $path\n";
}

foreach ($deletions as $path) {
    echo "DELETE  $path\n";
}

if (!$uploads && !$deletions) {
    echo "No deployable application changes in this range.\n";
    exit(0);
}

if (!isset($options['apply'])) {
    echo "Dry run only. Add --apply after reviewing this list.\n";
    exit(0);
}

if ($deletions && !isset($options['allow-delete'])) {
    fail('This range contains deletions. Review them and rerun with --allow-delete if intentional.');
}

$headCommit = resolveCommit('HEAD');

if ($toCommit !== $headCommit) {
    fail('For safety, --apply can deploy only the current HEAD commit.');
}

[$workingTreeStatus, $workingTreeError, $workingTreeExit] = runProcess([
    'git',
    'status',
    '--porcelain',
    '--untracked-files=no',
]);

if ($workingTreeExit !== 0) {
    fail('Unable to verify the working tree: ' . trim($workingTreeError));
}

if (trim($workingTreeStatus) !== '') {
    fail('For safety, commit or revert tracked working-tree changes before deploying.');
}

$config = loadDeployConfig($repositoryRoot . '/.deploy.env');
$deployHost = requireConfig($config, 'LIBERMEDIA_DEPLOY_HOST');
$deployUser = requireConfig($config, 'LIBERMEDIA_DEPLOY_USER');
$deployPort = requireConfig($config, 'LIBERMEDIA_DEPLOY_PORT');
$deployKey = requireConfig($config, 'LIBERMEDIA_DEPLOY_KEY');
$remoteRoot = requireConfig($config, 'LIBERMEDIA_REMOTE_ROOT');
$healthUrls = array_values(array_filter(array_map('trim', explode(',', requireConfig($config, 'LIBERMEDIA_HEALTH_URLS')))));

if (!is_file($deployKey)) {
    fail('Deployment private key not found: ' . $deployKey);
}

$sftp = [
    'sftp',
    '-q',
    '-b',
    '-',
    '-i',
    $deployKey,
    '-P',
    $deployPort,
    '-o',
    'BatchMode=yes',
    '-o',
    'IdentitiesOnly=yes',
    '-o',
    'StrictHostKeyChecking=yes',
    $deployUser . '@' . $deployHost,
];

$releaseId = gmdate('Ymd-His') . '-' . substr($toCommit, 0, 12);
$backupRoot = $repositoryRoot . '/.deploy-backups/' . $releaseId;

if (!mkdir($backupRoot, 0700, true) && !is_dir($backupRoot)) {
    fail('Unable to create backup directory: ' . $backupRoot);
}

$allAffectedPaths = array_values(array_unique(array_merge($uploads, $deletions)));

foreach ($allAffectedPaths as $path) {
    $localBackup = $backupRoot . '/' . $path;
    $partialBackup = $localBackup . '.part';
    $localDirectory = dirname($localBackup);

    if (!is_dir($localDirectory) && !mkdir($localDirectory, 0700, true) && !is_dir($localDirectory)) {
        fail('Unable to create backup directory: ' . $localDirectory);
    }

    [$backupSucceeded, $backupOutput] = runSftp($sftp, [
        'get ' . batchQuote(remotePath($remoteRoot, $path)) . ' ' . batchQuote($partialBackup),
    ]);

    if ($backupSucceeded) {
        if (!is_file($partialBackup) || !rename($partialBackup, $localBackup)) {
            fail('A remote backup could not be finalized locally. Nothing was deployed: ' . $path);
        }

        continue;
    }

    if (in_array($path, $requiredBackups, true)) {
        fail("Required remote backup failed. Nothing was deployed: $path\n" . $backupOutput);
    }

    if (!isRemoteMissingMessage($backupOutput)) {
        fail("Unable to verify whether a new remote path already exists. Nothing was deployed: $path\n" . $backupOutput);
    }
}

$applyCommands = [];

foreach (remoteDirectories($remoteRoot, $uploads) as $directory) {
    $applyCommands[] = '-mkdir ' . batchQuote($directory);
}

foreach ($uploads as $path) {
    $localPath = $repositoryRoot . '/' . $path;

    if (!is_file($localPath)) {
        fail('Local deploy file is missing: ' . $path);
    }

    $target = remotePath($remoteRoot, $path);
    $temporary = $target . '.deploy-' . substr($toCommit, 0, 12) . '.tmp';
    $applyCommands[] = 'put ' . batchQuote($localPath) . ' ' . batchQuote($temporary);
    $applyCommands[] = 'rename ' . batchQuote($temporary) . ' ' . batchQuote($target);
}

foreach ($deletions as $path) {
    $applyCommands[] = 'rm ' . batchQuote(remotePath($remoteRoot, $path));
}

$restoreCommands = [];

foreach (remoteDirectories($remoteRoot, $allAffectedPaths) as $directory) {
    $restoreCommands[] = '-mkdir ' . batchQuote($directory);
}

foreach ($allAffectedPaths as $path) {
    $localBackup = $backupRoot . '/' . $path;
    $target = remotePath($remoteRoot, $path);

    if (is_file($localBackup)) {
        $temporary = $target . '.rollback-' . substr($fromCommit, 0, 12) . '.tmp';
        $restoreCommands[] = 'put ' . batchQuote($localBackup) . ' ' . batchQuote($temporary);
        $restoreCommands[] = 'rename ' . batchQuote($temporary) . ' ' . batchQuote($target);
    }
}

[$applySucceeded, $applyOutput] = runSftp($sftp, $applyCommands);

if ($applySucceeded && healthCheck($healthUrls)) {
    echo 'Deployment completed successfully.' . PHP_EOL;
    echo 'Remote backups are stored locally in: ' . $backupRoot . PHP_EOL;
    exit(0);
}

if (!$applySucceeded) {
    fwrite(STDERR, "Deployment transfer failed; restoring the previous remote files.\n" . $applyOutput . "\n");
} else {
    fwrite(STDERR, "Health check failed; restoring the previous remote files.\n");
}

$rollbackSucceeded = true;
$rollbackOutput = '';

if ($restoreCommands) {
    [$rollbackSucceeded, $rollbackOutput] = runSftp($sftp, $restoreCommands);
}

foreach ($newPaths as $path) {
    if (is_file($backupRoot . '/' . $path)) {
        continue;
    }

    [$deleteSucceeded, $deleteOutput] = runSftp($sftp, [
        'rm ' . batchQuote(remotePath($remoteRoot, $path)),
    ]);

    if (!$deleteSucceeded && !isRemoteMissingMessage($deleteOutput)) {
        $rollbackSucceeded = false;
        $rollbackOutput .= "\n" . $deleteOutput;
    }
}

$temporaryCleanup = [];

foreach ($uploads as $path) {
    $temporaryCleanup[] = '-rm ' . batchQuote(
        remotePath($remoteRoot, $path) . '.deploy-' . substr($toCommit, 0, 12) . '.tmp'
    );
}

if ($temporaryCleanup) {
    runSftp($sftp, $temporaryCleanup);
}

if (!$rollbackSucceeded) {
    fail("Deployment failed and the automatic rollback also failed. Manual recovery is required from: $backupRoot\n" . $rollbackOutput, 3);
}

fail('Deployment was rolled back successfully.', 2);
