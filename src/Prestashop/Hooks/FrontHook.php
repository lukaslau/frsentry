<?php
/**
 * Sentry module for Prestashop
 * Version: 2.1.1
 * Copyright (c) 2023. Mateusz Szymański Teamwant
 * https://teamwant.pl
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Teamwant <kontakt@teamwant.pl>
 * @copyright Copyright 2016-2025 © Teamwant Mateusz Szymański All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @category  Teamwant
 */

namespace Teamwant\TeamwantSentry\src\Prestashop\Hooks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Teamwant\TeamwantSentry\src\Libs\TeamwantSentry;
use Teamwant\TeamwantSentry\src\Prestashop\TwConfiguration;

trait FrontHook
{
    public function registerFrontendHooks()
    {
        return $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('moduleRoutes')
            && $this->registerHook('actionDispatcherBefore')
        ;
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookActionFrontControllerSetMedia()
    {
        // set values
        if (empty(\Context::getContext()->tw_sentry)) {
            \Context::getContext()->tw_sentry = TwConfiguration::getConfiguration();
        }

        if (!empty(\Context::getContext()->tw_sentry) && \Context::getContext()->tw_sentry['frontend_key']) {
            $js = $this->context->link->getModuleLink(
                'teamwantsentry',
                'teamwantsentryjs',
                ['js' => 1, 'shop' => $this->context->shop->id],
                \Configuration::get('PS_SSL_ENABLED')
            );

            $this->context->controller->registerJavascript(
                'teamwantsentry-js',
                $js,
                [
                    'priority' => -9,
                    'position' => 'bottom',
                    'server' => 'remote',
                ]
            );
        }
    }

    public function hookModuleRoutes()
    {
        // set values
        if (empty(\Context::getContext()->tw_sentry)) {
            \Context::getContext()->tw_sentry = TwConfiguration::getConfiguration();
        }

        if (!empty(\Context::getContext()->tw_sentry) && \Context::getContext()->tw_sentry['backend_key']) {
            self::registerHandlers();
        }
    }

    public function hookActionDispatcherBefore($params)
    {
        if ($params['controller_type'] !== \Dispatcher::FC_FRONT)
            return;

        // set values
        if (empty(\Context::getContext()->tw_sentry)) {
            \Context::getContext()->tw_sentry = TwConfiguration::getConfiguration();
        }

        if (!empty(\Context::getContext()->tw_sentry) && \Context::getContext()->tw_sentry['backend']['profiling_frontend']) {
            if (!headers_sent())
                header('Document-Policy: js-profiling');
        }
    }

    private static function registerHandlers()
    {
        register_shutdown_function([TeamwantSentry::class, 'enableTeamwantErrorMonitorShut']);
        set_error_handler([TeamwantSentry::class, 'enableTeamwantErrorMonitorShutHandler']);

        // EXCEPTION HANDLERS
        $sentry_exception_handler = [TeamwantSentry::class, 'enableTeamwantException'];
        $previous_exception_handler = set_exception_handler(null);
        //if ($previous_exception_handler === null)
        //    $previous_exception_handler = [TeamwantSentry::class, 'prestashopFrontExceptionHandler'];

        if ($previous_exception_handler !== null)
            $handlers = [$sentry_exception_handler, $previous_exception_handler];
        else
            $handlers = [$sentry_exception_handler];

        set_exception_handler(function (\Throwable $e) use (&$handlers) {
            foreach ($handlers as $h) {
                $h($e);
            }
        });
    }
}
