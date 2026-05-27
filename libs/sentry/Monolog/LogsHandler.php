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

use FrSentry\Monolog\Formatter\FormatterInterface;
use FrSentry\Monolog\Formatter\LineFormatter;
use FrSentry\Monolog\Handler\HandlerInterface;
use FrSentry\Monolog\LogRecord;
use FrSentry\Sentry\Logs\LogLevel;
use FrSentry\Sentry\Logs\Logs;

class LogsHandler implements HandlerInterface
{
    use CompatibilityLogLevelTrait;
    /**
     * The minimum logging level at which this handler will be triggered.
     *
     * @var LogLevel|\Monolog\Level|int
     */
    private $logLevel;
    /**
     * Whether the messages that are handled can bubble up the stack or not.
     *
     * @var bool
     */
    private $bubble;

    /**
     * Creates a new Monolog handler that converts Monolog logs to Sentry logs.
     *
     * @param LogLevel|\Monolog\Level|int|null $logLevel the minimum logging level at which this handler will be triggered and collects the logs
     * @param bool $bubble whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($logLevel = null, bool $bubble = \true)
    {
        $this->logLevel = $logLevel ?? LogLevel::debug();
        $this->bubble = $bubble;
    }

    /**
     * @param array<string, mixed>|LogRecord $record
     */
    public function isHandling($record): bool
    {
        if ($this->logLevel instanceof LogLevel) {
            return self::getSentryLogLevelFromMonologLevel($record['level'])->getPriority() >= $this->logLevel->getPriority();
        } elseif ($this->logLevel instanceof \FrSentry\Monolog\Level) {
            return $record['level'] >= $this->logLevel->value;
        }

        return $record['level'] >= $this->logLevel;
    }

    /**
     * @param array<string, mixed>|LogRecord $record
     */
    public function handle($record): bool
    {
        if (!$this->isHandling($record)) {
            return \false;
        }
        // Do not collect logs for exceptions, they should be handled separately by `ExceptionToSentryIssueHandler` or `captureException`
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Throwable) {
            return \false;
        }
        Logs::getInstance()->aggregator()->add(self::getSentryLogLevelFromMonologLevel($record['level']), $record['message'], [], $this->compileAttributes($record));

        return $this->bubble === \false;
    }

    /**
     * @param array<array<string, mixed>|LogRecord> $records
     */
    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    public function close(): void
    {
        Logs::getInstance()->flush();
    }

    /**
     * @param callable $callback
     */
    public function pushProcessor($callback): void
    {
        // noop, this handler does not support processors
    }

    /**
     * @return callable
     */
    public function popProcessor()
    {
        // Since we do not support processors, we throw an exception if this method is called
        throw new \LogicException('You tried to pop from an empty processor stack.');
    }

    public function setFormatter(FormatterInterface $formatter): void
    {
        // noop, this handler does not support formatters
    }

    public function getFormatter(): FormatterInterface
    {
        // To adhere to the interface we need to return a formatter so we return a default one
        return new LineFormatter();
    }

    public function __destruct()
    {
        try {
            $this->close();
        } catch (\Throwable $e) {
            // Just in case so that the destructor can never fail.
        }
    }

    /**
     * @param array<string,mixed>|LogRecord $record
     *
     * @return array<string,mixed>
     */
    protected function compileAttributes($record): array
    {
        return array_merge($record['context'], $record['extra'], ['sentry.origin' => 'auto.log.monolog']);
    }
}
