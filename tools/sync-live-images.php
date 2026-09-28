#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Discover files referenced by an OpenCart database and copy them from the
 * public site's /image/ directory into the local upload/image directory.
 *
 * The default mode is deliberately a dry run. Add --download to write files.
 */

const DEFAULT_SOURCE_BASE = 'https://www.liber-media.hr/image/';
const DEFAULT_CONFIG = __DIR__ . '/../upload/config.php';
const DEFAULT_DESTINATION = __DIR__ . '/../upload/image';

/** Extensions accepted when a value contains a relative path without an
 * explicit image/ prefix. Explicit /image/ references may use any normal file
 * extension, so PDFs and other downloadable assets are included as well. */
const COMMON_ASSET_EXTENSIONS = 'avif|bmp|gif|heic|heif|ico|jfif|jpe?g|jxl|png|svg|tiff?|webp|eps|psd|pdf|docx?|xlsx?|pptx?|csv|txt|rtf|odt|ods|odp|zip|rar|7z|gz|tar|mp3|m4a|aac|flac|ogg|wav|mp4|m4v|mov|webm|avi|eot|otf|ttf|woff2?';

main($argv);

function main(array $argv): void
{
    $options = parseOptions($argv);

    if ($options['help']) {
        printHelp();
        return;
    }

    if ($options['download'] && $options['dry-run']) {
        fail('Opcije --download i --dry-run ne mogu se koristiti zajedno.');
    }

    $configPath = absolutePath($options['config'] ?? DEFAULT_CONFIG);
    if (!is_file($configPath) || !is_readable($configPath)) {
        fail("OpenCart config nije čitljiv: {$configPath}");
    }

    // OpenCart's config only defines constants and is the most reliable source
    // for the local database settings and prefix.
    require_once $configPath;

    $database = optionOrEnvironment($options, 'database', 'DB_DATABASE', defined('DB_DATABASE') ? (string) DB_DATABASE : '');
    $host = optionOrEnvironment($options, 'host', 'DB_HOSTNAME', defined('DB_HOSTNAME') ? (string) DB_HOSTNAME : '127.0.0.1');
    $port = (int) optionOrEnvironment($options, 'port', 'DB_PORT', defined('DB_PORT') ? (string) DB_PORT : '3306');
    $user = optionOrEnvironment($options, 'user', 'DB_USERNAME', defined('DB_USERNAME') ? (string) DB_USERNAME : 'root');
    $password = optionOrEnvironment($options, 'password', 'DB_PASSWORD', defined('DB_PASSWORD') ? (string) DB_PASSWORD : '');
    $prefix = optionOrEnvironment($options, 'prefix', 'DB_PREFIX', defined('DB_PREFIX') ? (string) DB_PREFIX : 'oc_');

    if ($database === '') {
        fail('Naziv baze nije zadan (DB_DATABASE ili --database).');
    }
    if ($port < 1 || $port > 65535) {
        fail('--port mora biti između 1 i 65535.');
    }

    $sourceBase = ensureTrailingSlash($options['source-base'] ?? DEFAULT_SOURCE_BASE);
    $sourceParts = parse_url($sourceBase);
    if (!is_array($sourceParts) || !isset($sourceParts['scheme'], $sourceParts['host']) || !in_array(strtolower((string) $sourceParts['scheme']), ['http', 'https'], true)) {
        fail('--source-base mora biti valjani HTTP(S) URL.');
    }

    $destination = absolutePath($options['destination'] ?? DEFAULT_DESTINATION);
    $concurrency = positiveInteger($options['concurrency'] ?? '12', '--concurrency', 1, 64);
    $timeout = positiveInteger($options['timeout'] ?? '90', '--timeout', 1, 3600);
    $retries = positiveInteger($options['retries'] ?? '2', '--retries', 0, 10);
    $sampleSize = positiveInteger($options['sample'] ?? '20', '--sample', 0, 500);
    $limit = $options['limit'] === null ? null : positiveInteger($options['limit'], '--limit', 1, PHP_INT_MAX);
    $download = $options['download'];

    $allowedHosts = array_values(array_unique(array_map(
        static fn (string $hostName): string => strtolower($hostName),
        array_merge(
            ['www.liber-media.hr', 'liber-media.hr', 'libermedia.dmb.hr', 'www.libermedia.dmb.hr', 'libermedia.test'],
            [(string) $sourceParts['host']],
            $options['allowed-host']
        )
    )));

    fwrite(STDERR, sprintf(
        "Skeniram bazu %s na %s:%d (tablice: %s*)...\n",
        $database,
        $host,
        $port,
        $prefix
    ));

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            ]
        );
    } catch (Throwable $error) {
        fail('Spajanje na bazu nije uspjelo: ' . $error->getMessage());
    }

    $tableFilter = normalizeTableFilter($options['table'], $prefix);
    $columnsByTable = discoverTextColumns($pdo, $database, $prefix, $tableFilter);

    if ($columnsByTable === []) {
        fail('Nije pronađena nijedna tekstualna kolona u odabranim OpenCart tablicama.');
    }

    [$assets, $scanStats] = scanDatabase($pdo, $columnsByTable, $allowedHosts, $limit);
    ksort($assets, SORT_NATURAL | SORT_FLAG_CASE);

    fwrite(STDERR, sprintf(
        "Pregledano: %d tablica, %d redaka i %d tekstualnih vrijednosti.\n",
        $scanStats['tables'],
        $scanStats['rows'],
        $scanStats['values']
    ));
    fwrite(STDERR, sprintf(
        "Pronađeno: %d jedinstvenih datoteka (%d cache putanja preskočeno).\n",
        count($assets),
        $scanStats['cache_skipped']
    ));

    if ($limit !== null && count($assets) >= $limit) {
        fwrite(STDERR, "Napomena: skeniranje je zaustavljeno na --limit={$limit}.\n");
    }

    if (!$download) {
        printDryRun($assets, $sourceBase, $destination, $sampleSize);
        return;
    }

    if (!extension_loaded('curl')) {
        fail('PHP curl ekstenzija je potrebna za --download.');
    }

    ensureDestinationRoot($destination);

    $queue = [];
    $existing = 0;
    foreach (array_keys($assets) as $path) {
        $localPath = destinationPath($destination, $path);
        if (!$options['overwrite'] && is_file($localPath) && filesize($localPath) > 0) {
            ++$existing;
            continue;
        }
        $queue[] = $path;
    }

    fwrite(STDERR, sprintf(
        "Preuzimanje: %d datoteka (%d već postoji), paralelnost %d.\n",
        count($queue),
        $existing,
        $concurrency
    ));

    $result = downloadAssets(
        $queue,
        $sourceBase,
        $destination,
        $concurrency,
        $timeout,
        $retries,
        $options['overwrite'],
        $options['verbose']
    );

    fwrite(STDERR, sprintf(
        "Gotovo: %d preuzeto, %d preskočeno tijekom rada, %d neuspjelo.\n",
        $result['downloaded'],
        $existing + $result['skipped'],
        count($result['failed'])
    ));

    if ($result['failed'] !== []) {
        fwrite(STDERR, "Neuspjele datoteke:\n");
        foreach ($result['failed'] as $path => $reason) {
            fwrite(STDERR, "  - {$path}: {$reason}\n");
        }
        exit(2);
    }
}

function parseOptions(array $argv): array
{
    $result = [
        'help' => false,
        'download' => false,
        'dry-run' => false,
        'overwrite' => false,
        'verbose' => false,
        'config' => null,
        'host' => null,
        'port' => null,
        'database' => null,
        'user' => null,
        'password' => null,
        'prefix' => null,
        'destination' => null,
        'source-base' => null,
        'concurrency' => null,
        'timeout' => null,
        'retries' => null,
        'limit' => null,
        'sample' => null,
        'table' => [],
        'allowed-host' => [],
    ];

    $flags = ['help', 'download', 'dry-run', 'overwrite', 'verbose'];
    $repeatable = ['table', 'allowed-host'];

    for ($index = 1, $count = count($argv); $index < $count; ++$index) {
        $argument = $argv[$index];
        if ($argument === '-h') {
            $result['help'] = true;
            continue;
        }
        if (!str_starts_with($argument, '--')) {
            fail("Nepoznat argument: {$argument}");
        }

        $pair = explode('=', substr($argument, 2), 2);
        $name = $pair[0];
        if (!array_key_exists($name, $result)) {
            fail("Nepoznata opcija: --{$name}");
        }

        if (in_array($name, $flags, true)) {
            if (isset($pair[1])) {
                fail("Opcija --{$name} ne prima vrijednost.");
            }
            $result[$name] = true;
            continue;
        }

        $value = $pair[1] ?? null;
        if ($value === null) {
            if (!isset($argv[$index + 1]) || str_starts_with($argv[$index + 1], '--')) {
                fail("Opcija --{$name} traži vrijednost.");
            }
            $value = $argv[++$index];
        }

        if (in_array($name, $repeatable, true)) {
            $result[$name][] = $value;
        } else {
            $result[$name] = $value;
        }
    }

    return $result;
}

function printHelp(): void
{
    $help = <<<'HELP'
Sinkronizira datoteke koje OpenCart baza referencira ispod /image/.

Uporaba:
  php tools/sync-live-images.php [opcije]

Bez --download radi se samo dry-run i ništa se ne zapisuje.

Najvažnije opcije:
  --download                 Stvarno preuzmi datoteke
  --dry-run                  Eksplicitni dry-run (zadano ponašanje)
  --overwrite                Zamijeni postojeće lokalne datoteke
  --concurrency=N            Broj paralelnih preuzimanja (zadano: 12)
  --timeout=SECONDS          Rok po datoteci (zadano: 90)
  --retries=N                Broj ponovnih pokušaja (zadano: 2)
  --limit=N                  Zaustavi skeniranje nakon N putanja
  --sample=N                 Broj putanja prikazanih u dry-runu (zadano: 20)
  --table=NAME               Skeniraj samo tablicu (može se ponoviti)
  --destination=PATH         Odredišni direktorij (zadano: upload/image)
  --source-base=URL          Izvor (zadano: https://www.liber-media.hr/image/)
  --allowed-host=HOST        Dodatna domena za apsolutne /image/ URL-ove
  --config=PATH              OpenCart config (zadano: upload/config.php)
  --host, --port, --database, --user, --password, --prefix
                             Prepiši postavke baze iz configa
  --verbose                  Ispiši svaku preuzetu datoteku
  -h, --help                 Prikaži ovu pomoć

DB_* varijable okruženja imaju prednost pred vrijednostima iz configa.
HELP;

    fwrite(STDOUT, $help . PHP_EOL);
}

function optionOrEnvironment(array $options, string $option, string $environment, string $default): string
{
    if ($options[$option] !== null) {
        return (string) $options[$option];
    }

    $value = getenv($environment);
    return $value === false ? $default : $value;
}

function positiveInteger(string|int $value, string $name, int $minimum, int $maximum): int
{
    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
        fail("{$name} mora biti cijeli broj.");
    }
    $integer = (int) $value;
    if ($integer < $minimum || $integer > $maximum) {
        fail("{$name} mora biti između {$minimum} i {$maximum}.");
    }
    return $integer;
}

function normalizeTableFilter(array $tables, string $prefix): array
{
    $result = [];
    foreach ($tables as $table) {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            fail("Neispravan naziv tablice: {$table}");
        }
        $result[] = str_starts_with($table, $prefix) ? $table : $prefix . $table;
    }
    return array_values(array_unique($result));
}

function discoverTextColumns(PDO $pdo, string $database, string $prefix, array $tableFilter): array
{
    $sql = <<<'SQL'
SELECT TABLE_NAME, COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = :database
  AND LEFT(TABLE_NAME, CHAR_LENGTH(:prefix)) = :prefix
  AND DATA_TYPE IN ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'tinyblob', 'blob', 'mediumblob', 'longblob', 'json')
ORDER BY TABLE_NAME, ORDINAL_POSITION
SQL;

    $statement = $pdo->prepare($sql);
    $statement->execute(['database' => $database, 'prefix' => $prefix]);
    $columnsByTable = [];
    while ($row = $statement->fetch()) {
        $table = (string) $row['TABLE_NAME'];
        if ($tableFilter !== [] && !in_array($table, $tableFilter, true)) {
            continue;
        }
        $columnsByTable[$table][] = (string) $row['COLUMN_NAME'];
    }
    $statement->closeCursor();
    return $columnsByTable;
}

function scanDatabase(PDO $pdo, array $columnsByTable, array $allowedHosts, ?int $limit): array
{
    $assets = [];
    $stats = ['tables' => 0, 'rows' => 0, 'values' => 0, 'cache_skipped' => 0];
    $stop = false;

    foreach ($columnsByTable as $table => $columns) {
        $select = implode(', ', array_map('quoteIdentifier', $columns));
        $statement = $pdo->query('SELECT ' . $select . ' FROM ' . quoteIdentifier($table));
        ++$stats['tables'];

        while ($row = $statement->fetch()) {
            ++$stats['rows'];
            foreach ($row as $column => $value) {
                if (!is_string($value) || $value === '') {
                    continue;
                }
                ++$stats['values'];

                foreach (extractAssetCandidates($value, (string) $column) as $candidate) {
                    $normalized = normalizeAssetPath($candidate, $allowedHosts);
                    if ($normalized === null) {
                        continue;
                    }
                    if (preg_match('~^(?:cache|cachewebp)/~i', $normalized)) {
                        ++$stats['cache_skipped'];
                        continue;
                    }
                    if (!isset($assets[$normalized])) {
                        $assets[$normalized] = $table . '.' . $column;
                        if ($limit !== null && count($assets) >= $limit) {
                            $stop = true;
                            break 4;
                        }
                    }
                }
            }
        }
        $statement->closeCursor();
    }

    // An unbuffered statement must be closed even when --limit exits early.
    if (isset($statement) && $stop) {
        $statement->closeCursor();
    }

    return [$assets, $stats];
}

function extractAssetCandidates(string $raw, string $column): array
{
    // JSON-escaped slashes and HTML-encoded markup both occur in OpenCart
    // settings and descriptions.
    $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $variants = [
        str_replace(['\\/', '\\u002F', '\\u002f'], '/', $raw),
        str_replace(['\\/', '\\u002F', '\\u002f'], '/', $decoded),
    ];

    // Module and theme settings are commonly stored as JSON. Decode them so
    // escaped Unicode and paths containing literal spaces stay intact.
    $json = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        collectJsonStrings($json, $variants);
    }

    $variants = array_values(array_unique($variants));

    $candidates = [];
    $imageColumn = preg_match('/(?:^|_)(?:image|images|logo|icon|thumb|thumbnail|banner)(?:_|$)/i', $column) === 1;
    foreach ($variants as $text) {
        $trimmed = trim($text);
        if (
            strlen($trimmed) <= 4096
            && !str_contains($trimmed, "\n")
            && !str_contains($trimmed, "\r")
            && (
                preg_match('~^(?:(?:https?:)?//[^/\s\"\'<>]+)?/?image/[^\"\'<>]+\.[A-Za-z0-9]{1,16}(?:[?#].*)?$~', $trimmed)
                || (
                    ($imageColumn || preg_match('~^(?:catalog|data|uploads)/~i', ltrim($trimmed, '/')))
                    && preg_match('~^[^\"\'<>={}]+\.(?:' . COMMON_ASSET_EXTENSIONS . ')(?:[?#].*)?$~i', $trimmed)
                )
            )
        ) {
            $candidates[] = $trimmed;
        }

        // Absolute URLs on known Liber Media hosts; host validation happens in
        // normalizeAssetPath(). Stop at HTML/JSON delimiters.
        preg_match_all('~(?:https?:)?//[^/\\s\"\'<>]+/image/[^\s\"\'<>]+~', $text, $matches);
        array_push($candidates, ...$matches[0]);

        // Root-relative and document-relative references.
        // Do not start this relative-path match in the middle of an absolute
        // URL; otherwise a third-party https://host/image/x.jpg could bypass
        // the allowed-host check as a second, relative candidate.
        preg_match_all('~(?<![A-Za-z0-9_:/.-])/?image/[^\s\"\'<>]+~', $text, $matches);
        array_push($candidates, ...$matches[0]);

        // HTML attributes preserve paths containing literal spaces.
        preg_match_all('~(?:src|href|poster|data-src|data-original|data-lazy-src)\s*=\s*([\"\'])(.*?)\1~is', $text, $matches);
        foreach ($matches[2] as $attributeValue) {
            if (isPlausibleMediaReference($attributeValue)) {
                $candidates[] = $attributeValue;
            }
        }

        preg_match_all('~srcset\s*=\s*([\"\'])(.*?)\1~is', $text, $srcsets);
        foreach ($srcsets[2] as $srcset) {
            foreach (preg_split('/\s*,\s*/', $srcset) ?: [] as $entry) {
                if (preg_match('/^(.+?)(?:\s+\d+(?:\.\d+)?[wx])?$/i', trim($entry), $match)) {
                    if (isPlausibleMediaReference($match[1])) {
                        $candidates[] = $match[1];
                    }
                }
            }
        }

        preg_match_all('~url\(\s*([\"\']?)(.*?)\1\s*\)~is', $text, $urls);
        foreach ($urls[2] as $urlValue) {
            if (isPlausibleMediaReference($urlValue)) {
                $candidates[] = $urlValue;
            }
        }

        // OpenCart image columns usually contain paths such as catalog/a.jpg,
        // with no leading image/. Restrict this fallback to common asset types.
        preg_match_all(
            '~(?<![A-Za-z0-9_.%/-])((?:(?:catalog(?!/(?:view|controller|model|language)/)|data|uploads)/)(?:[A-Za-z0-9_.%+@()\~-]+/)*[^\s\"\'<>]+\.(?:' . COMMON_ASSET_EXTENSIONS . '))(?:[?#][^\s\"\'<>]*)?~i',
            $text,
            $matches
        );
        array_push($candidates, ...$matches[1]);
    }

    return array_values(array_unique(array_filter($candidates, static fn (string $value): bool => $value !== '')));
}

function collectJsonStrings(array $value, array &$strings): void
{
    foreach ($value as $item) {
        if (is_array($item)) {
            collectJsonStrings($item, $strings);
        } elseif (is_string($item) && $item !== '') {
            $strings[] = $item;
        }
    }
}

function isPlausibleMediaReference(string $candidate): bool
{
    $candidate = html_entity_decode(trim($candidate), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $candidate = str_replace(['\\/', '\\u002F', '\\u002f'], '/', $candidate);

    if (preg_match('~^(?:https?:)?//~i', $candidate)) {
        return strpos((string) parse_url(str_starts_with($candidate, '//') ? 'https:' . $candidate : $candidate, PHP_URL_PATH), '/image/') !== false;
    }

    $candidate = ltrim($candidate, './');
    return preg_match('~^(?:image|catalog(?!/(?:view|controller|model|language)/)|data|uploads)/~', $candidate) === 1;
}

function normalizeAssetPath(string $candidate, array $allowedHosts): ?string
{
    $candidate = html_entity_decode(trim($candidate), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $candidate = str_replace(['\\/', '\\u002F', '\\u002f'], '/', $candidate);
    $candidate = trim($candidate, " \t\n\r\0\x0B\"'`<>[]{}(),;!");
    if ($candidate === '' || preg_match('~^(?:data|javascript|mailto):~i', $candidate)) {
        return null;
    }

    $isAbsolute = preg_match('~^(?:https?:)?//~i', $candidate) === 1;
    if (str_starts_with($candidate, '//')) {
        $candidate = 'https:' . $candidate;
    }

    if ($isAbsolute) {
        $parts = parse_url($candidate);
        if (!is_array($parts) || !isset($parts['host'], $parts['path'])) {
            return null;
        }
        if (!in_array(strtolower((string) $parts['host']), $allowedHosts, true)) {
            return null;
        }
        $candidate = (string) $parts['path'];
    } else {
        // Strip query strings and fragments before decoding, so encoded ?/# in
        // filenames remain intact.
        $candidate = preg_split('/[?#]/', $candidate, 2)[0];
    }

    $candidate = str_replace('\\', '/', rawurldecode($candidate));
    $candidate = preg_replace('~/+~', '/', $candidate) ?? $candidate;

    // The public OpenCart directory is the lowercase /image/ path. Keeping
    // this case-sensitive also avoids mistaking source paths such as
    // system/library/.../Image/Cache.php for public assets.
    $imagePosition = strpos('/' . ltrim($candidate, '/'), '/image/');
    if ($imagePosition !== false) {
        $candidate = substr('/' . ltrim($candidate, '/'), $imagePosition + strlen('/image/'));
    } elseif ($isAbsolute) {
        return null;
    }

    $candidate = preg_replace('~^(?:\./)+~', '', ltrim($candidate, '/')) ?? $candidate;
    $candidate = rtrim($candidate, " \t\n\r\0\x0B.,;:!?)\]}");

    if ($candidate === '' || strlen($candidate) > 4096) {
        return null;
    }

    $segments = explode('/', $candidate);
    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.' || $segment === '..') {
            return null;
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $segment)) {
            return null;
        }
    }

    // A referenced asset must look like a file. This intentionally accepts
    // more than images (for example PDFs) when it lives below /image/.
    $basename = end($segments);
    if (!is_string($basename) || !preg_match('/\.(?:' . COMMON_ASSET_EXTENSIONS . ')$/i', $basename)) {
        return null;
    }

    return implode('/', $segments);
}

function printDryRun(array $assets, string $sourceBase, string $destination, int $sampleSize): void
{
    fwrite(STDOUT, "DRY-RUN: ništa nije zapisano niti preuzeto.\n");
    $shown = 0;
    foreach ($assets as $path => $origin) {
        if ($shown >= $sampleSize) {
            break;
        }
        fwrite(STDOUT, sprintf(
            "  %s\n    -> %s\n    -> %s  [%s]\n",
            sourceUrl($sourceBase, $path),
            destinationPath($destination, $path),
            $path,
            $origin
        ));
        ++$shown;
    }
    if (count($assets) > $shown) {
        fwrite(STDOUT, sprintf("  ... još %d putanja\n", count($assets) - $shown));
    }
    fwrite(STDOUT, "Za stvarni prijenos dodaj opciju --download.\n");
}

function downloadAssets(
    array $paths,
    string $sourceBase,
    string $destination,
    int $concurrency,
    int $timeout,
    int $retries,
    bool $overwrite,
    bool $verbose
): array {
    $pending = array_fill_keys($paths, 0);
    $failed = [];
    $downloaded = 0;
    $skipped = 0;

    while ($pending !== []) {
        $roundAttempts = $pending;
        $roundPaths = array_keys($roundAttempts);
        $pending = [];

        foreach (array_chunk($roundPaths, $concurrency) as $chunk) {
            $results = downloadChunk($chunk, $sourceBase, $destination, $timeout, $overwrite);
            foreach ($results as $path => $result) {
                if ($result['status'] === 'downloaded') {
                    ++$downloaded;
                    if ($verbose) {
                        fwrite(STDERR, "Preuzeto: {$path}\n");
                    } elseif ($downloaded % 100 === 0) {
                        fwrite(STDERR, "Preuzeto {$downloaded} datoteka...\n");
                    }
                    continue;
                }
                if ($result['status'] === 'skipped') {
                    ++$skipped;
                    continue;
                }

                $attempt = $roundAttempts[$path] ?? 0;
                if ($result['retryable'] && $attempt < $retries) {
                    $pending[$path] = $attempt + 1;
                } else {
                    $failed[$path] = $result['reason'];
                }
            }
        }
    }

    return ['downloaded' => $downloaded, 'skipped' => $skipped, 'failed' => $failed];
}

function downloadChunk(array $paths, string $sourceBase, string $destination, int $timeout, bool $overwrite): array
{
    $multi = curl_multi_init();
    $jobs = [];
    $results = [];

    foreach ($paths as $path) {
        $localPath = destinationPath($destination, $path);
        if (!$overwrite && is_file($localPath) && filesize($localPath) > 0) {
            $results[$path] = ['status' => 'skipped'];
            continue;
        }

        try {
            ensureSafeParentDirectory($destination, $path);
            $temporary = $localPath . '.part-' . getmypid() . '-' . bin2hex(random_bytes(4));
            $stream = fopen($temporary, 'x+b');
            if ($stream === false) {
                throw new RuntimeException('ne mogu otvoriti privremenu datoteku');
            }
        } catch (Throwable $error) {
            $results[$path] = ['status' => 'failed', 'reason' => $error->getMessage(), 'retryable' => false];
            continue;
        }

        $handle = curl_init(sourceUrl($sourceBase, $path));
        if ($handle === false) {
            fclose($stream);
            @unlink($temporary);
            $results[$path] = ['status' => 'failed', 'reason' => 'curl inicijalizacija nije uspjela', 'retryable' => true];
            continue;
        }

        curl_setopt_array($handle, [
            CURLOPT_FILE => $stream,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => min(20, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'LiberMedia local asset sync/1.0',
            CURLOPT_ENCODING => '',
        ]);

        curl_multi_add_handle($multi, $handle);
        $jobs[spl_object_id($handle)] = compact('path', 'localPath', 'temporary', 'stream', 'handle');
    }

    do {
        $status = curl_multi_exec($multi, $running);
        if ($status !== CURLM_OK) {
            break;
        }
        if ($running > 0 && curl_multi_select($multi, 1.0) === -1) {
            usleep(10_000);
        }
    } while ($running > 0);

    foreach ($jobs as $job) {
        $handle = $job['handle'];
        $path = $job['path'];
        $httpStatus = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $contentType = strtolower((string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE));
        $curlNumber = curl_errno($handle);
        $curlError = curl_error($handle);

        curl_multi_remove_handle($multi, $handle);
        curl_close($handle);
        fflush($job['stream']);
        fclose($job['stream']);

        $reason = null;
        $retryable = false;
        if ($curlNumber !== CURLE_OK) {
            $reason = "curl {$curlNumber}: {$curlError}";
            $retryable = true;
        } elseif ($httpStatus < 200 || $httpStatus >= 300) {
            $reason = "HTTP {$httpStatus}";
            $retryable = $httpStatus === 0 || $httpStatus === 408 || $httpStatus === 425 || $httpStatus === 429 || $httpStatus >= 500;
        } elseif (!is_file($job['temporary']) || filesize($job['temporary']) === 0) {
            $reason = 'prazan odgovor';
            $retryable = true;
        } elseif (str_contains($contentType, 'text/html') || looksLikeHtml($job['temporary'])) {
            $reason = 'poslužitelj je vratio HTML umjesto datoteke';
            $retryable = false;
        }

        if ($reason !== null) {
            @unlink($job['temporary']);
            $results[$path] = ['status' => 'failed', 'reason' => $reason, 'retryable' => $retryable];
            continue;
        }

        if (!$overwrite && is_file($job['localPath']) && filesize($job['localPath']) > 0) {
            @unlink($job['temporary']);
            $results[$path] = ['status' => 'skipped'];
            continue;
        }

        if (!rename($job['temporary'], $job['localPath'])) {
            @unlink($job['temporary']);
            $results[$path] = ['status' => 'failed', 'reason' => 'ne mogu dovršiti lokalnu datoteku', 'retryable' => false];
            continue;
        }

        @chmod($job['localPath'], 0644);
        $results[$path] = ['status' => 'downloaded'];
    }

    curl_multi_close($multi);
    return $results;
}

function looksLikeHtml(string $path): bool
{
    $stream = fopen($path, 'rb');
    if ($stream === false) {
        return false;
    }
    $prefix = fread($stream, 512);
    fclose($stream);
    if (!is_string($prefix)) {
        return false;
    }
    $prefix = ltrim(strtolower($prefix));
    return str_starts_with($prefix, '<!doctype html') || str_starts_with($prefix, '<html');
}

function ensureDestinationRoot(string $destination): void
{
    if (file_exists($destination) && (!is_dir($destination) || is_link($destination))) {
        fail("Odredište mora biti stvarni direktorij, ne symlink/datoteka: {$destination}");
    }
    if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
        fail("Ne mogu napraviti odredišni direktorij: {$destination}");
    }
}

function ensureSafeParentDirectory(string $destination, string $assetPath): void
{
    $segments = explode('/', $assetPath);
    array_pop($segments);
    $directory = rtrim($destination, DIRECTORY_SEPARATOR);

    foreach ($segments as $segment) {
        $directory .= DIRECTORY_SEPARATOR . $segment;
        if (is_link($directory)) {
            throw new RuntimeException("symlink u odredišnoj putanji: {$directory}");
        }
        if (file_exists($directory) && !is_dir($directory)) {
            throw new RuntimeException("dio odredišne putanje nije direktorij: {$directory}");
        }
        if (!is_dir($directory) && !mkdir($directory, 0755) && !is_dir($directory)) {
            throw new RuntimeException("ne mogu napraviti direktorij: {$directory}");
        }
    }
}

function sourceUrl(string $base, string $path): string
{
    $segments = explode('/', $path);
    return ensureTrailingSlash($base) . implode('/', array_map('rawurlencode', $segments));
}

function destinationPath(string $destination, string $path): string
{
    return rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function ensureTrailingSlash(string $value): string
{
    return rtrim($value, '/') . '/';
}

function absolutePath(string $path): string
{
    if ($path === '') {
        fail('Putanja ne smije biti prazna.');
    }
    if ($path[0] === DIRECTORY_SEPARATOR) {
        return $path;
    }
    $cwd = getcwd();
    if ($cwd === false) {
        fail('Ne mogu odrediti trenutni direktorij.');
    }
    return $cwd . DIRECTORY_SEPARATOR . $path;
}

function fail(string $message): never
{
    fwrite(STDERR, "Greška: {$message}\n");
    exit(1);
}
