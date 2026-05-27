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

namespace Frento\FrSentry\src\Prestashop;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FrConfiguration
{
    public static $configPrefix = 'FRSENTRY_';

    /** @var array|null Config cache — populated once per request on first call. */
    private static $cache;

    /**
     * DB key suffixes for boolean settings (stored as 0/1 in ps_configuration).
     * Used by install/uninstall routines and the admin form.
     * Full DB key = FRSENTRY_ + suffix, e.g. FRSENTRY_PHP_IGNORE_USER.
     */
    public static $booleanKeys = [
        'PHP_IGNORE_USER',
        'PHP_IGNORE_DEPRECATED',
        'PHP_IGNORE_WARNING',
        'PHP_IGNORE_NOTICED',
        'USE_BACKOFFICE',
        'INSIGHTS_FRONTEND',
        'PROFILING_FRONTEND',
        'BACKEND_EXCIMER_ENABLED',
        'BACKEND_TRACING',
        'BACKEND_PROFILING',
    ];

    /**
     * DB key suffixes for integer settings (stored as 0–100 in ps_configuration).
     * These are sampling rates expressed as a percentage.
     * Full DB key = FRSENTRY_ + suffix, e.g. FRSENTRY_BACKEND_TRACING_RATE.
     */
    public static $rateKeys = [
        'BACKEND_TRACING_RATE',
        'BACKEND_PROFILING_RATE',
        'FRONTEND_TRACING_RATE',
        'FRONTEND_PROFILING_RATE',
    ];

    /**
     * Reads all module settings from the PrestaShop configuration table.
     *
     * @return array{
     *   backendKey: string,
     *   frontendKey: string,
     *   backend: array{
     *     phpIgnoreUser: bool,
     *     phpIgnoreDeprecated: bool,
     *     phpIgnoreWarning: bool,
     *     phpIgnoreNoticed: bool,
     *     useBackoffice: bool,
     *     insightsFrontend: bool,
     *     profilingFrontend: bool,
     *     tracingEnabled: bool,
     *     tracingRate: int,
     *     profilingEnabled: bool,
     *     profilingRate: int,
     *   }
     * }
     */
    public static function getConfiguration(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $prefix = self::$configPrefix;

        return self::$cache = [
            'backendKey' => (string) (\Configuration::get($prefix . 'BACKEND_KEY') ?: ''),
            'frontendKey' => (string) (\Configuration::get($prefix . 'FRONTEND_KEY') ?: ''),
            'backend' => [
                'phpIgnoreUser' => self::getBool($prefix . 'PHP_IGNORE_USER', true),
                'phpIgnoreDeprecated' => self::getBool($prefix . 'PHP_IGNORE_DEPRECATED', true),
                'phpIgnoreWarning' => self::getBool($prefix . 'PHP_IGNORE_WARNING', true),
                'phpIgnoreNoticed' => self::getBool($prefix . 'PHP_IGNORE_NOTICED', true),
                'useBackoffice' => self::getBool($prefix . 'USE_BACKOFFICE', false),
                'insightsFrontend' => self::getBool($prefix . 'INSIGHTS_FRONTEND', false),
                'profilingFrontend' => self::getBool($prefix . 'PROFILING_FRONTEND', false),
                'frontendTracingRate' => self::getInt($prefix . 'FRONTEND_TRACING_RATE', 20),
                'frontendProfilingRate' => self::getInt($prefix . 'FRONTEND_PROFILING_RATE', 20),
                'tracingEnabled' => self::getBool($prefix . 'BACKEND_TRACING', false),
                'tracingRate' => self::getInt($prefix . 'BACKEND_TRACING_RATE', 100),
                'profilingEnabled' => self::getBool($prefix . 'BACKEND_PROFILING', false),
                'profilingRate' => self::getInt($prefix . 'BACKEND_PROFILING_RATE', 100),
            ],
        ];
    }

    /**
     * Clears the in-memory cache.
     * Called after saving settings so the next read picks up the new values.
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * Reads a stored boolean config value, returning $default when unset.
     */
    private static function getBool(string $key, bool $default): bool
    {
        $value = \Configuration::get($key);

        if ($value === false) {
            return $default;
        }

        return (bool) (int) $value;
    }

    /**
     * Reads a stored integer config value, returning $default when unset.
     * Used for sampling rates (0–100).
     */
    private static function getInt(string $key, int $default): int
    {
        $value = \Configuration::get($key);

        if ($value === false) {
            return $default;
        }

        return (int) $value;
    }
}
