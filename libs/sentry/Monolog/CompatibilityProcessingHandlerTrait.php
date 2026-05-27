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
use FrSentry\Monolog\LogRecord;
use FrSentry\Sentry\Severity;

if (Logger::API >= 3) {
    /**
     * Logic which is used if monolog >= 3 is installed.
     *
     * @internal
     */
    trait CompatibilityProcessingHandlerTrait
    {
        /**
         * @param array<string, mixed>|LogRecord $record
         */
        abstract protected function doWrite($record): void;

        /**
         * {@inheritdoc}
         */
        protected function write(LogRecord $record): void
        {
            $this->doWrite($record);
        }

        /**
         * Translates the Monolog level into the Sentry severity.
         */
        private static function getSeverityFromLevel(int $level): Severity
        {
            $level = Level::from($level);
            switch ($level) {
                case Level::Debug:
                    return Severity::debug();
                case Level::Warning:
                    return Severity::warning();
                case Level::Error:
                    return Severity::error();
                case Level::Critical:
                case Level::Alert:
                case Level::Emergency:
                    return Severity::fatal();
                case Level::Info:
                case Level::Notice:
                default:
                    return Severity::info();
            }
        }
    }
} else {
    /**
     * Logic which is used if monolog < 3 is installed.
     *
     * @internal
     */
    trait CompatibilityProcessingHandlerTrait
    {
        /**
         * @param array<string, mixed>|LogRecord $record
         */
        abstract protected function doWrite($record): void;

        /**
         * {@inheritdoc}
         */
        protected function write(array $record): void
        {
            $this->doWrite($record);
        }

        /**
         * Translates the Monolog level into the Sentry severity.
         *
         * @param Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY $level The Monolog log level
         */
        private static function getSeverityFromLevel(int $level): Severity
        {
            switch ($level) {
                case Logger::DEBUG:
                    return Severity::debug();
                case Logger::WARNING:
                    return Severity::warning();
                case Logger::ERROR:
                    return Severity::error();
                case Logger::CRITICAL:
                case Logger::ALERT:
                case Logger::EMERGENCY:
                    return Severity::fatal();
                case Logger::INFO:
                case Logger::NOTICE:
                default:
                    return Severity::info();
            }
        }
    }
}
