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

namespace Frento\FrSentry\Hooks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Frento\FrSentry\Core\SentryReporter;
use Frento\FrSentry\FrConfiguration;

class FrontHook
{
    /**
     * Registers the hooks this module listens to.
     * Called once during module installation.
     *
     * @param \Module $module
     *
     * @return bool
     */
    public static function registerHooks(\Module $module): bool
    {
        return $module->registerHook('actionFrontControllerSetMedia')
            && $module->registerHook('moduleRoutes');
    }

    /**
     * Fires before any route is dispatched — the earliest available hook.
     * Used to populate the Sentry context and register PHP error handlers.
     *
     * @return array empty — this module adds no custom routes
     */
    public static function handleModuleRoutes(): array
    {
        self::bootSentry();

        return [];
    }

    /**
     * Injects Sentry JavaScript based on the active settings.
     *
     * Load order (lower priority = earlier):
     *   -11  sentry.min.js             SDK — loaded when a frontend DSN is set
     *   -10  sentry-profiling.min.js   profiling integration — only when both
     *                                  frontend insights AND profiling are on
     *    -9  /frsentry/js              dynamic init config (DSN, integrations, user)
     */
    public static function handleSetMedia(): void
    {
        $context = \Context::getContext();
        $config = FrConfiguration::getConfiguration();
        $frontend = $config['frontend'];

        if (empty($frontend['dsn'])) {
            return;
        }

        // JS Self-Profiling API requires this header to be present on the page
        // that loads the profiler. Must be sent before output starts.
        if (!empty($frontend['profiling']) && !headers_sent()) {
            header('Document-Policy: js-profiling');
        }

        $context->controller->registerJavascript(
            'frsentry-sdk',
            'modules/frsentry/views/js/sentry.min.js',
            ['priority' => -11, 'position' => 'bottom']
        );

        // Profiling requires the tracing integration — only load the profiling
        // bundle when both features are explicitly enabled.
        if (!empty($frontend['insights'])
            && !empty($frontend['profiling'])
        ) {
            $context->controller->registerJavascript(
                'frsentry-profiling',
                'modules/frsentry/views/js/sentry-profiling.min.js',
                ['priority' => -10, 'position' => 'bottom']
            );
        }

        $initUrl = $context->link->getModuleLink(
            'frsentry',
            'js',
            ['shop' => $context->shop->id],
            (bool) \Configuration::get('PS_SSL_ENABLED')
        );

        $context->controller->registerJavascript(
            'frsentry-init',
            $initUrl,
            ['priority' => -9, 'position' => 'bottom', 'server' => 'remote']
        );
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Boots the Sentry backend — reads config and registers PHP error handlers.
     * FrConfiguration::getConfiguration() is cached internally so the DB is
     * read at most once per request regardless of how many times this runs.
     */
    private static function bootSentry(): void
    {
        $config = FrConfiguration::getConfiguration();

        if (!empty($config['backend']['dsn'])) {
            SentryReporter::registerHandlers();
        }
    }
}
