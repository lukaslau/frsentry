<?php
/*
 * Copyright (c) 2026 Frento IT <info@frentoit.com>
 *
 * NOTICE OF LICENSE
 *
 * This file is licensed under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the license agreement.
 *
 * You must not modify, adapt or create derivative works of this source code.
 *
 * @author    Frento IT <info@frentoit.com>
 * @copyright Since 2024 Frento IT
 * @license   Commercial license
 */

// ── Locate PrestaShop ────────────────────────────────────────────────────────
// This script assumes the module is installed at <PS_ROOT>/modules/frsentry/,
// so PS_ROOT is three levels up from do_not_include/. Adjust if not.

$psRoot = realpath(__DIR__ . '/../../../');
$psRoot = 'D:\WebServer\ps\ps817_basic';

if (!$psRoot || !is_file($psRoot . '/config/config.inc.php')) {
    fwrite(STDERR, "ERROR: Cannot find PrestaShop config at {$psRoot}/config/config.inc.php\n");
    fwrite(STDERR, "Set \$psRoot manually at the top of this script.\n");
    exit(1);
}

// ── Bootstrap PrestaShop ─────────────────────────────────────────────────────
// config.inc.php sets up autoload, defines (_PS_VERSION_, _PS_ROOT_DIR_, ...),
// the Context, DB connection, and everything else a request would have.

require_once $psRoot . '/config/config.inc.php';

// ── Boot the FrSentry module ─────────────────────────────────────────────────
// Calling hookModuleRoutes() fires bootSentry(), which:
//   1. Reads module settings via FrConfiguration::getConfiguration()
//   2. Registers set_error_handler / set_exception_handler / shutdown handler
// This is exactly what would happen on a real front-office request.

$module = Module::getInstanceByName('frsentry');

if (!$module) {
    fwrite(STDERR, "ERROR: Module 'frsentry' is not installed.\n");
    exit(1);
}

echo "[diag] Module loaded: " . get_class($module) . "\n";

$module->hookModuleRoutes();

echo "[diag] hookModuleRoutes() called\n";

$config = Frento\FrSentry\src\Prestashop\FrConfiguration::getConfiguration();

echo "[diag] backendKey: " . (!empty($config['backendKey']) ? 'SET (' . substr($config['backendKey'], 0, 30) . '...)' : 'EMPTY') . "\n";
echo "[diag] PHP_SAPI: " . PHP_SAPI . "\n";
echo "[diag] isMonitoringEnabled (via capture test): ";

if (empty($config['backendKey'])) {
    fwrite(STDERR, "\nWARNING: Backend DSN key is not configured. Events will not reach Sentry.\n\n");
} else {
    // Re-init SDK with a transport logger so we can see exactly what Sentry returns.

    $eventId = \FrSentry\Sentry\captureException(new \Exception('[diag] connectivity check'));
    echo "[diag] eventId: " . ($eventId ? (string) $eventId : 'null (not sent)') . "\n";
}

echo "\n";

// ── Pick the test type ──────────────────────────────────────────────────────

$type = $argv[1] ?? '';

if ($type === '') {
    showHelp();
    exit(0);
}

echo "Triggering '{$type}'...\n";
echo str_repeat('-', 60) . "\n";

switch ($type) {
    // ── Throwables ────────────────────────────────────────────────────────────
    case 'exception':
        throw new Exception('CLI test :: generic \\Exception');

    case 'prestashop':
        throw new PrestaShopException('CLI test :: \\PrestaShopException');

    case 'runtime':
        throw new RuntimeException('CLI test :: \\RuntimeException');

    case 'logic':
        throw new LogicException('CLI test :: \\LogicException');

    case 'error':
        throw new Error('CLI test :: \\Error');

    case 'type':
        $callable = function (int $x): int { return $x * 2; };
        $callable('not an int');  // \TypeError
        break;

    case 'division':
        throw new DivisionByZeroError('CLI test :: \\DivisionByZeroError');

    // ── PHP errors (caught via set_error_handler) ────────────────────────────
    case 'warning':
        file_get_contents('/definitely/nonexistent/path/file.txt');
        echo "E_WARNING triggered (script continues)\n";
        break;

    case 'notice':
        $foo = $undefined[42];
        echo "E_NOTICE / E_WARNING triggered (script continues)\n";
        break;

    case 'deprecated':
        @strftime('%Y');  // deprecated in PHP 8.1
        echo "E_DEPRECATED triggered (script continues)\n";
        break;

    case 'user_error':
        trigger_error('CLI test :: E_USER_ERROR', E_USER_ERROR);
        break;

    case 'user_warning':
        trigger_error('CLI test :: E_USER_WARNING', E_USER_WARNING);
        echo "E_USER_WARNING triggered (script continues)\n";
        break;

    case 'user_notice':
        trigger_error('CLI test :: E_USER_NOTICE', E_USER_NOTICE);
        echo "E_USER_NOTICE triggered (script continues)\n";
        break;

    case 'user_deprecated':
        trigger_error('CLI test :: E_USER_DEPRECATED', E_USER_DEPRECATED);
        echo "E_USER_DEPRECATED triggered (script continues)\n";
        break;

    // ── Fatal (caught via register_shutdown_function) ────────────────────────
    case 'fatal':
        nonexistent_function_xyz_123();
        break;

    // ── Database (caught via override/classes/db/Db.php) ─────────────────────
    case 'mysql':
        Db::getInstance()->query('SELECT * FROM definitely_no_such_table_xyz');
        echo "MySQL error triggered (script continues)\n";
        break;

    case 'mysql_syntax':
        Db::getInstance()->query('SELECT INVALID SYNTAX FROM');
        echo "MySQL syntax error triggered (script continues)\n";
        break;

    // ── Manual capture ───────────────────────────────────────────────────────
    case 'manual':
        $module->captureException(
            new Exception('CLI test :: manual captureException()'),
            ['type' => 'manual_cli_test', 'extra_tag' => 'demo']
        );
        echo "Manual capture sent.\n";
        break;

    // ── Deduplication ────────────────────────────────────────────────────────
    case 'dedup':
        for ($i = 1; $i <= 3; $i++) {
            try {
                throw new Exception('CLI test :: deduplication');
            } catch (Throwable $exception) {
                $module->captureException($exception);
            }
        }
        echo "Threw the same exception 3 times. Sentry should show only 1 event.\n";
        break;

    default:
        fwrite(STDERR, "Unknown type '{$type}'. Run with no argument to see the list.\n");
        exit(1);
}

echo "Done.\n";

// ── Help text ────────────────────────────────────────────────────────────────
function showHelp(): void
{
    echo <<<HELP
FrSentry standalone backend test script
=======================================

Usage:  php do_not_include/test-backend.php <type>

Throwables (caught via set_exception_handler):
  exception        \\Exception
  prestashop       \\PrestaShopException
  runtime          \\RuntimeException
  logic            \\LogicException
  error            \\Error
  type             \\TypeError
  division         \\DivisionByZeroError

PHP errors (caught via set_error_handler):
  warning          E_WARNING (file_get_contents on missing file)
  notice           E_NOTICE / E_WARNING (undefined index)
  deprecated       E_DEPRECATED (deprecated core function)
  user_error       E_USER_ERROR  (trigger_error)
  user_warning     E_USER_WARNING (trigger_error)
  user_notice      E_USER_NOTICE  (trigger_error)
  user_deprecated  E_USER_DEPRECATED (trigger_error)

Fatal (caught via register_shutdown_function):
  fatal            E_ERROR — call to undefined function

Database (caught via override/classes/db/Db.php):
  mysql            SELECT from nonexistent table
  mysql_syntax     SQL syntax error

Manual capture:
  manual           Module::captureException() with extra tags

Deduplication:
  dedup            Throw the same exception 3 times — Sentry should show 1

HELP;
}
