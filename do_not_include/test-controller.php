<?php
/**
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

if (!defined('_PS_VERSION_')) {
    exit;
}

class frsentryTestModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        xdebug_break();
        $type = (string) Tools::getValue('type');

        header('Content-Type: text/plain; charset=utf-8');

        if ($type === '') {
            $this->showHelp();
            exit;
        }

        echo "FrSentry test :: triggering '{$type}'\n";
        echo str_repeat('-', 60) . "\n";

        switch ($type) {
            // ── Throwables ────────────────────────────────────────────────────
            case 'exception':
                throw new \Exception('Test :: generic \\Exception');

            case 'prestashop':
                throw new \PrestaShopException('Test :: \\PrestaShopException');

            case 'runtime':
                throw new \RuntimeException('Test :: \\RuntimeException');

            case 'logic':
                throw new \LogicException('Test :: \\LogicException');

            case 'error':
                throw new \Error('Test :: \\Error');

            case 'type':
                $this->triggerTypeError();
                break;

            case 'division':
                throw new \DivisionByZeroError('Test :: \\DivisionByZeroError');

            // ── PHP errors (caught via set_error_handler) ─────────────────────
            case 'warning':
                // Real E_WARNING from a failed file read
                file_get_contents('/definitely/nonexistent/path/file.txt');
                echo "E_WARNING triggered (page continues)\n";
                exit;

            case 'notice':
                // E_NOTICE / E_WARNING depending on PHP version
                $undefined = $foo[42];
                echo "E_NOTICE triggered (page continues)\n";
                exit;

            case 'deprecated':
                // PHP-version-dependent; some core funcs raise this
                @\strftime('%Y');  // strftime deprecated in PHP 8.1
                echo "E_DEPRECATED triggered (page continues)\n";
                exit;

            // ── User-triggered errors ─────────────────────────────────────────
            case 'user_error':
                trigger_error('Test :: E_USER_ERROR', E_USER_ERROR);
                exit;

            case 'user_warning':
                trigger_error('Test :: E_USER_WARNING', E_USER_WARNING);
                echo "E_USER_WARNING triggered (page continues)\n";
                exit;

            case 'user_notice':
                trigger_error('Test :: E_USER_NOTICE', E_USER_NOTICE);
                echo "E_USER_NOTICE triggered (page continues)\n";
                exit;

            case 'user_deprecated':
                trigger_error('Test :: E_USER_DEPRECATED', E_USER_DEPRECATED);
                echo "E_USER_DEPRECATED triggered (page continues)\n";
                exit;

            // ── Fatal (caught via register_shutdown_function) ─────────────────
            case 'fatal':
                // Calls a function that doesn't exist -> E_ERROR
                nonexistent_function_xyz_123();
                exit;

            // ── Database (caught via override/classes/db/Db.php) ──────────────
            case 'mysql':
                \Db::getInstance()->query('SELECT * FROM definitely_no_such_table_xyz');
                exit;

            case 'mysql_syntax':
                \Db::getInstance()->query('SELECT INVALID SYNTAX FROM');
                exit;

            // ── Manual capture ────────────────────────────────────────────────
            case 'manual':
                \Module::getInstanceByName('frsentry')->captureException(
                    new \Exception('Test :: manual captureException()'),
                    ['type' => 'manual_test', 'extra_tag' => 'demo_value']
                );
                echo "Manual capture sent. Check Sentry.\n";
                exit;

            case 'manual_with_user':
                $context = \Context::getContext();
                \Module::getInstanceByName('frsentry')->captureException(
                    new \Exception('Test :: manual with implicit user context'),
                    ['source' => 'test-controller']
                );
                echo "Manual capture sent. Customer logged in: "
                    . (!empty($context->customer->id) ? 'yes (id=' . (int) $context->customer->id . ')' : 'no')
                    . "\n";
                exit;

            // ── Deduplication test ────────────────────────────────────────────
            case 'dedup':
                // Same exception thrown twice in one request — only first should reach Sentry
                for ($i = 1; $i <= 3; $i++) {
                    try {
                        throw new \Exception('Test :: deduplication (occurrence ' . $i . ')');
                    } catch (\Throwable $exception) {
                        \Module::getInstanceByName('frsentry')->captureException($exception);
                    }
                }
                echo "Threw the same exception 3 times. Sentry should show only 1 event.\n";
                exit;

            default:
                echo "Unknown type '{$type}'. Hit /module/frsentry/test with no type for the list.\n";
                exit;
        }
    }

    private function triggerTypeError(): void
    {
        $callable = function (int $x): int { return $x * 2; };
        $callable('not an int');  // \TypeError
    }

    private function showHelp(): void
    {
        echo <<<HELP
FrSentry backend test endpoint
==============================

Usage:  /module/frsentry/test?type=<name>

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
  manual_with_user same, with logged-in customer context if available

Deduplication:
  dedup            Throw the same exception 3 times — Sentry should show 1

Reminder: this controller only works if you copied it to controllers/front/test.php.
Delete it from there once you are done testing.
HELP;
    }
}
