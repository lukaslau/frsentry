<?php
/**
 * Sentry module for Prestashop
 * Version: 2.1.1
 * Copyright (c) 2023. Mateusz Szymański Frento IT
 * https://frentoit.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Frento IT <info@frentoit.com>
 * @copyright Copyright 2016-2025 © Frento IT Mateusz Szymański All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @category  Frento IT
 */

namespace Frento\FrSentry\src\Libs;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;

class FrSentry
{
    public static $type = 1;

    /**
     * @return FrSentryClient|ClientInterface
     */
    public static function getClient()
    {
        if (self::$type === 2) {
            return (new FrSentryClient(\Context::getContext()->tw_sentry['backend_key']));
        }

        \Sentry\init([
            'dsn' => \Context::getContext()->tw_sentry['backend_key'],
            'traces_sample_rate' => 1.0,
            'profiles_sample_rate' => 1.0,
            // 'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
            //     return $event;
            // },
            'integrations' => function (array $integrations) {
                // Filter out the ModulesIntegration
                return array_filter($integrations, function ($integration) {
                    return !($integration instanceof \Sentry\Integration\ModulesIntegration);
                });
            },
        ]);

        $hub = SentrySdk::getCurrentHub();
        $client = $hub->getClient();

        return $client;
    }

    public static function enableFrSentryErrorMonitorShut()
    {
        $error = error_get_last();

        $typestr = self::checkEnabled($error['type'] ?? 0);

        if (!$typestr) {
            return;
        }

        if ($error && ($error['type'])) {
            self::enableFrSentryErrorMonitorShutHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }


    /**
     * @param \Exception $e
     * @param array $tags
     * @return void
     */
    public static function customCaptureException($e, $tags = [])
    {
        // checking if the same error was already sent for prevent duplication
        $error_hash = md5($e->getMessage().$e->getCode());
        if (isset(\Context::getContext()->tw_sentry['sent_errors'][$error_hash]))
            return;
        else
            \Context::getContext()->tw_sentry['sent_errors'][$error_hash] = 1;

        // assigning type if message has sqlstate substring
        if (stripos($e->getMessage(), 'SQLSTATE[') !== false)
            $tags['type'] = 'MYSQL';

        $client = self::getClient();

        if ($client instanceof FrSentryClient) {
            unset($tags['sql_query']);
            $client->captureException($e, $tags);
        } else {
            $tags['lang_id'] = \Context::getContext()->language->id;
            $tags['shop_id'] = \Context::getContext()->shop->id;
            $tags['php_ver'] = PHP_VERSION;
            $tags['controller'] = \Context::getContext()->controller->php_self;

            // \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($tags): void {
            //     foreach ($tags ?? [] as $key => $value) {
            //         $scope->setTag($key, $value);
            //     }
            // });


            $scope = new Scope();

            // adding sql query
            if (!empty($tags['sql_query']))
            {
                $scope->setContext('SQL Query', ['query' => $tags['sql_query']]);
                unset($tags['sql_query']);
            }

            $scope->setTags($tags);

            // setting user
            $user = ['ip_address' => \Tools::getRemoteAddr()];

            if (!empty(\Context::getContext()->customer->id)) {
                $user['id'] = \Context::getContext()->customer->id;
                $user['email'] = \Context::getContext()->customer->email;
            }

            $scope->setUser($user);
            $client->captureException($e, $scope);
        }
    }


    /**
     * @param \Exception $e
     * @return void
     */
    public static function enableFrSentryException($e)
    {
        $typestr = self::checkEnabled(0);

        if (!$typestr) {
            return;
        }

        self::customCaptureException($e, ['type' => 'PHP']);

        self::innerExceptionHandler($e);
    }

    public static function enableFrSentryErrorMonitorShutHandler($errno, $errstr, $errfile, $errline)
    {
        $typestr = self::checkEnabled($errno);

        if (!$typestr) {
            return;
        }

        try {
            self::customCaptureException(new \Exception(sprintf(
                '[file:%s:%s] error_type: %s -> %s',
                $errfile,
                $errline,
                $typestr,
                $errstr
            )), ['type' => 'PHP']);
        } catch (\Throwable $e) {
            // var_dump($e);
            // exit;
        }
    }

    public static function checkEnabled($errno)
    {
        if (defined('_PS_ADMIN_DIR_') && !\Context::getContext()->tw_sentry['backend']['use_backoffice']) {
            return false;
        }

        $typestr = true;
        $php_ignore_user = \Context::getContext()->tw_sentry['backend']['php_ignore_user'];
        $php_ignore_deprecated = \Context::getContext()->tw_sentry['backend']['php_ignore_deprecated'];
        $php_ignore_warning = \Context::getContext()->tw_sentry['backend']['php_ignore_warning'];
        $php_ignore_noticed = \Context::getContext()->tw_sentry['backend']['php_ignore_noticed'];

        switch ($errno) {
            case E_ERROR: // 1 //
                $typestr = 'E_ERROR';
                break;
            case E_WARNING: // 2 //
                if ($php_ignore_warning) {
                    return false;
                }
                $typestr = 'E_WARNING';
                break;
            case E_PARSE: // 4 //
                $typestr = 'E_PARSE';
                break;
            case E_NOTICE: // 8 //
                if ($php_ignore_noticed) {
                    return false;
                }
                $typestr = 'E_NOTICE';
                break;
            case E_CORE_ERROR: // 16 //
                $typestr = 'E_CORE_ERROR';
                break;
            case E_CORE_WARNING: // 32 //
                $typestr = 'E_CORE_WARNING';
                break;
            case E_COMPILE_ERROR: // 64 //
                $typestr = 'E_COMPILE_ERROR';
                break;
            case E_COMPILE_WARNING: // 128 //
                $typestr = 'E_COMPILE_WARNING';
                break;
            case E_USER_ERROR: // 256 //
                if ($php_ignore_user) {
                    return false;
                }
                $typestr = 'E_USER_ERROR';
                break;
            case E_USER_WARNING: // 512 //
                if ($php_ignore_user) {
                    return false;
                }
                $typestr = 'E_USER_WARNING';
                break;
            case E_USER_NOTICE: // 1024 //
                if ($php_ignore_noticed) {
                    return false;
                }

                if ($php_ignore_user) {
                    return false;
                }
                $typestr = 'E_USER_NOTICE';
                break;
            case E_STRICT: // 2048 //
                $typestr = 'E_STRICT';
                break;
            case E_RECOVERABLE_ERROR: // 4096 //
                $typestr = 'E_RECOVERABLE_ERROR';
                break;
            case E_DEPRECATED: // 8192 //
                if ($php_ignore_deprecated) {
                    return false;
                }
                $typestr = 'E_DEPRECATED';
                break;
            case E_USER_DEPRECATED: // 16384 //
                if ($php_ignore_deprecated) {
                    return false;
                }

                if ($php_ignore_user) {
                    return false;
                }
                $typestr = 'E_USER_DEPRECATED';
                break;
        }

        return $typestr;
    }

    public static function innerExceptionHandler($e)
    {
        if (!self::isFrontOffice()) {
            throw $e;
        }

        if (getenv('kernel.environment') === 'test') {
            throw $e;
        }

        if (class_exists('\Tools') && method_exists('\Tools', 'isPHPCLI') && \Tools::isPHPCLI())
        {
            echo get_class($e) . ' in ' . $e->getFile() . ' line ' . $e->getLine() . "\n";
            echo $e->getTraceAsString() . "\n";
        }
        else if ($e instanceof \PrestaShopException) {
            $e->displayMessage();
        }
        else if (defined('_PS_ROOT_DIR_')) {
            if (file_exists(_PS_ROOT_DIR_ . '/error500.html')) {
                header('HTTP/1.1 500 Internal Server Error');
                echo file_get_contents(_PS_ROOT_DIR_ . '/error500.html');
                exit();
            }
        }
    }

    public static function isFrontOffice()
    {
        if (!class_exists("\Context") || !class_exists("\FrontController"))
            return false;

        $controller = \Context::getContext()->controller ?? null;
        return $controller instanceof \FrontController;
    }
}
