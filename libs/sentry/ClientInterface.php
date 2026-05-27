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

namespace FrSentry\Sentry;

use FrSentry\Sentry\Integration\IntegrationInterface;
use FrSentry\Sentry\State\Scope;
use FrSentry\Sentry\Transport\Result;

interface ClientInterface
{
    /**
     * Returns the options of the client.
     */
    public function getOptions(): Options;

    /**
     * Returns an URL for security policy reporting that's generated from the configured DSN.
     */
    public function getCspReportUrl(): ?string;

    /**
     * Logs a message.
     *
     * @param string $message The message (primary description) for the event
     * @param Severity|null $level The level of the message to be sent
     * @param Scope|null $scope An optional scope keeping the state
     * @param EventHint|null $hint Object that can contain additional information about the event
     */
    public function captureMessage(string $message, ?Severity $level = null, ?Scope $scope = null, ?EventHint $hint = null): ?EventId;

    /**
     * Logs an exception.
     *
     * @param \Throwable $exception The exception object
     * @param Scope|null $scope An optional scope keeping the state
     * @param EventHint|null $hint Object that can contain additional information about the event
     */
    public function captureException(\Throwable $exception, ?Scope $scope = null, ?EventHint $hint = null): ?EventId;

    /**
     * Logs the most recent error (obtained with {@link error_get_last}).
     *
     * @param Scope|null $scope An optional scope keeping the state
     * @param EventHint|null $hint Object that can contain additional information about the event
     */
    public function captureLastError(?Scope $scope = null, ?EventHint $hint = null): ?EventId;

    /**
     * Captures a new event using the provided data.
     *
     * @param Event $event The event being captured
     * @param EventHint|null $hint May contain additional information about the event
     * @param Scope|null $scope An optional scope keeping the state
     */
    public function captureEvent(Event $event, ?EventHint $hint = null, ?Scope $scope = null): ?EventId;

    /**
     * Returns the integration instance if it is installed on the client.
     *
     * @param string $className The FQCN of the integration
     *
     * @phpstan-template T of IntegrationInterface
     *
     * @phpstan-param class-string<T> $className
     *
     * @phpstan-return T|null
     */
    public function getIntegration(string $className): ?IntegrationInterface;

    /**
     * Flushes the queue of events pending to be sent. If a timeout is provided
     * and the queue takes longer to drain, the promise resolves with `false`.
     *
     * @param int|null $timeout Maximum time in seconds the client should wait
     */
    public function flush(?int $timeout = null): Result;

    /**
     * Returns the stacktrace builder of the client.
     */
    public function getStacktraceBuilder(): StacktraceBuilder;
}
