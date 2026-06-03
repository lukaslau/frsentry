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

namespace Frento\FrSentry;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Reads and exposes the module settings, grouped by surface (back office vs.
 * storefront).
 *
 * Settings are stored as individual rows in ps_configuration, all sharing the
 * FRSENTRY_ prefix. Error-capture toggles follow an opt-in "track" model:
 * hard errors (fatals, parse/compile errors) are always captured, while softer
 * categories (warnings, notices, deprecations, user-triggered) are only sent
 * when the matching TRACK_* switch is enabled.
 */
class FrConfiguration
{
    public static $configPrefix = 'FRSENTRY_';

    /** @var array|null Settings snapshot — built once per request on first read. */
    private static $cache;

    /**
     * Free-text DSN suffixes. Full key = FRSENTRY_ + suffix.
     *
     * @var string[]
     */
    public static $dsnKeys = [
        'BACKEND_DSN',
        'FRONTEND_DSN',
    ];

    /**
     * Server-side boolean suffixes (stored as 0/1).
     *
     * @var string[]
     */
    public static $backendToggles = [
        'BACKEND_MONITOR_ADMIN',
        'BACKEND_TRACK_WARNING',
        'BACKEND_TRACK_NOTICE',
        'BACKEND_TRACK_DEPRECATION',
        'BACKEND_TRACK_USER',
        'BACKEND_TRACING',
        'BACKEND_PROFILING',
    ];

    /**
     * Storefront boolean suffixes (stored as 0/1).
     *
     * @var string[]
     */
    public static $frontendToggles = [
        'FRONTEND_INSIGHTS',
        'FRONTEND_PROFILING',
    ];

    /**
     * Server-side percentage suffixes (0–100 sampling rates).
     *
     * @var string[]
     */
    public static $backendRates = [
        'BACKEND_TRACING_RATE',
        'BACKEND_PROFILING_RATE',
    ];

    /**
     * Storefront percentage suffixes (0–100 sampling rates).
     *
     * @var string[]
     */
    public static $frontendRates = [
        'FRONTEND_TRACING_RATE',
        'FRONTEND_PROFILING_RATE',
    ];

    /**
     * Every boolean suffix across both surfaces — used by save/uninstall loops.
     *
     * @return string[]
     */
    public static function toggleKeys(): array
    {
        return array_merge(self::$backendToggles, self::$frontendToggles);
    }

    /**
     * Every percentage suffix across both surfaces.
     *
     * @return string[]
     */
    public static function rateKeys(): array
    {
        return array_merge(self::$backendRates, self::$frontendRates);
    }

    /**
     * Builds the settings snapshot grouped by surface.
     *
     * @return array{
     *   backend: array{
     *     dsn: string,
     *     monitorAdmin: bool,
     *     track: array{warning: bool, notice: bool, deprecation: bool, userErrors: bool},
     *     tracing: array{enabled: bool, sampleRate: int},
     *     profiling: array{enabled: bool, sampleRate: int}
     *   },
     *   frontend: array{
     *     dsn: string,
     *     insights: bool,
     *     tracingRate: int,
     *     profiling: bool,
     *     profilingRate: int
     *   }
     * }
     */
    public static function getConfiguration(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $p = self::$configPrefix;

        return self::$cache = [
            'backend' => [
                'dsn' => (string) (\Configuration::get($p . 'BACKEND_DSN') ?: ''),
                'monitorAdmin' => self::getBool($p . 'BACKEND_MONITOR_ADMIN', false),
                'track' => [
                    'warning' => self::getBool($p . 'BACKEND_TRACK_WARNING', false),
                    'notice' => self::getBool($p . 'BACKEND_TRACK_NOTICE', false),
                    'deprecation' => self::getBool($p . 'BACKEND_TRACK_DEPRECATION', false),
                    'userErrors' => self::getBool($p . 'BACKEND_TRACK_USER', false),
                ],
                'tracing' => [
                    'enabled' => self::getBool($p . 'BACKEND_TRACING', false),
                    'sampleRate' => self::getInt($p . 'BACKEND_TRACING_RATE', 100),
                ],
                'profiling' => [
                    'enabled' => self::getBool($p . 'BACKEND_PROFILING', false),
                    'sampleRate' => self::getInt($p . 'BACKEND_PROFILING_RATE', 100),
                ],
            ],
            'frontend' => [
                'dsn' => (string) (\Configuration::get($p . 'FRONTEND_DSN') ?: ''),
                'insights' => self::getBool($p . 'FRONTEND_INSIGHTS', false),
                'tracingRate' => self::getInt($p . 'FRONTEND_TRACING_RATE', 20),
                'profiling' => self::getBool($p . 'FRONTEND_PROFILING', false),
                'profilingRate' => self::getInt($p . 'FRONTEND_PROFILING_RATE', 20),
            ],
        ];
    }

    /**
     * Clears the in-memory snapshot so the next read reflects freshly saved values.
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * Reads a stored boolean, returning $default when the row does not exist.
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
     * Reads a stored integer (sampling rate), returning $default when unset.
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
