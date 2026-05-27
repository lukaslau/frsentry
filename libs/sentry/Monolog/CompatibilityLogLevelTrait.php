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

declare(strict_types=1);

namespace FrSentry\Sentry\Monolog;

use FrSentry\Monolog\Level;
use FrSentry\Monolog\Logger;
use FrSentry\Sentry\Logs\LogLevel;

if (Logger::API >= 3) {
    /**
     * Logic which is used if monolog >= 3 is installed.
     *
     * @internal
     */
    trait CompatibilityLogLevelTrait
    {
        /**
         * Translates the Monolog level into the Sentry LogLevel.
         */
        private static function getSentryLogLevelFromMonologLevel(int $level): LogLevel
        {
            $level = Level::from($level);
            switch ($level) {
                case Level::Debug:
                    return LogLevel::debug();
                case Level::Warning:
                    return LogLevel::warn();
                case Level::Error:
                    return LogLevel::error();
                case Level::Critical:
                case Level::Alert:
                case Level::Emergency:
                    return LogLevel::fatal();
                case Level::Info:
                case Level::Notice:
                default:
                    return LogLevel::info();
            }
        }
    }
} else {
    /**
     * Logic which is used if monolog < 3 is installed.
     *
     * @internal
     */
    trait CompatibilityLogLevelTrait
    {
        /**
         * Translates the Monolog level into the Sentry LogLevel.
         *
         * @param Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY $level The Monolog log level
         */
        private static function getSentryLogLevelFromMonologLevel(int $level): LogLevel
        {
            switch ($level) {
                case Logger::DEBUG:
                    return LogLevel::debug();
                case Logger::WARNING:
                    return LogLevel::warn();
                case Logger::ERROR:
                    return LogLevel::error();
                case Logger::CRITICAL:
                case Logger::ALERT:
                case Logger::EMERGENCY:
                    return LogLevel::fatal();
                case Logger::INFO:
                case Logger::NOTICE:
                default:
                    return LogLevel::info();
            }
        }
    }
}
