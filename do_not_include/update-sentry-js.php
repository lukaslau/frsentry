#!/usr/bin/env php
<?php
/**
 * @author    Frento IT <info@frentoit.com>
 * @copyright Copyright 2016-2025 © Frento IT All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * Downloads the latest Sentry Browser SDK bundle files from jsDelivr CDN
 * and saves them as static assets in views/js/.
 *
 * Run this before releasing a new module version to update the bundled JS.
 *
 * Usage:
 *   php do_not_include/update-sentry-js.php
 */

define('ROOT', dirname(__DIR__));

// ── Resolve latest version ────────────────────────────────────────────────────

$resolveUrl = 'https://data.jsdelivr.com/v1/package/npm/%40sentry%2Fbrowser/resolved?specifier=latest';
$response   = @file_get_contents($resolveUrl);
$resolved   = $response ? json_decode($response, true) : null;
$version    = $resolved['version'] ?? null;

if (!$version) {
    echo "ERROR: could not resolve latest @sentry/browser version.\n";
    exit(1);
}

echo "@sentry/browser resolved to: {$version}\n\n";

// ── Download files ────────────────────────────────────────────────────────────

$baseUrl = "https://cdn.jsdelivr.net/npm/@sentry/browser@{$version}/build";

$files = [
    'views/js/sentry.min.js'           => "{$baseUrl}/bundle.min.js",
    'views/js/sentry-profiling.min.js' => "{$baseUrl}/bundle.profiling.min.js",
];

$success = true;

foreach ($files as $destination => $url) {
    echo "Downloading {$url} ... ";

    $content = @file_get_contents($url);

    if (!$content) {
        echo "FAILED\n";
        $success = false;
        continue;
    }

    file_put_contents(ROOT . '/' . $destination, $content);

    echo 'OK (' . round(strlen($content) / 1024) . " KB)\n";
}

echo "\n";

if (!$success) {
    echo "One or more files failed. Check the URLs above.\n";
    exit(1);
}

echo "Done. Commit views/js/sentry.min.js and views/js/sentry-profiling.min.js.\n";
