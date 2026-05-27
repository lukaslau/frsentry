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

namespace FrSentry\Sentry\Serializer\EnvelopItems;

use FrSentry\Sentry\Event;
use FrSentry\Sentry\ExceptionDataBag;
use FrSentry\Sentry\Serializer\Traits\BreadcrumbSeralizerTrait;
use FrSentry\Sentry\Serializer\Traits\StacktraceFrameSeralizerTrait;
use FrSentry\Sentry\Util\JSON;
use FrSentry\Sentry\Util\Str;

/**
 * @internal
 */
class EventItem implements EnvelopeItemInterface
{
    use BreadcrumbSeralizerTrait;
    use StacktraceFrameSeralizerTrait;

    public static function toEnvelopeItem(Event $event): string
    {
        $header = ['type' => (string) $event->getType(), 'content_type' => 'application/json'];
        $payload = ['timestamp' => $event->getTimestamp(), 'platform' => 'php', 'sdk' => $event->getSdkPayload()];
        if ($event->getStartTimestamp() !== null) {
            $payload['start_timestamp'] = $event->getStartTimestamp();
        }
        if ($event->getLevel() !== null) {
            $payload['level'] = (string) $event->getLevel();
        }
        if ($event->getLogger() !== null) {
            $payload['logger'] = $event->getLogger();
        }
        if ($event->getTransaction() !== null) {
            $payload['transaction'] = $event->getTransaction();
        }
        if ($event->getServerName() !== null) {
            $payload['server_name'] = $event->getServerName();
        }
        if ($event->getRelease() !== null) {
            $payload['release'] = $event->getRelease();
        }
        if ($event->getEnvironment() !== null) {
            $payload['environment'] = $event->getEnvironment();
        }
        if (!empty($event->getFingerprint())) {
            $payload['fingerprint'] = $event->getFingerprint();
        }
        if (!empty($event->getModules())) {
            $payload['modules'] = $event->getModules();
        }
        if (!empty($event->getExtra())) {
            $payload['extra'] = $event->getExtra();
        }
        if (!empty($event->getTags())) {
            $payload['tags'] = $event->getTags();
        }
        $user = $event->getUser();
        if ($user !== null) {
            $payload['user'] = array_merge($user->getMetadata(), ['id' => $user->getId(), 'username' => $user->getUsername(), 'email' => $user->getEmail(), 'ip_address' => $user->getIpAddress(), 'segment' => $user->getSegment()]);
        }
        $osContext = $event->getOsContext();
        if ($osContext !== null) {
            $payload['contexts']['os'] = ['name' => $osContext->getName(), 'version' => $osContext->getVersion(), 'build' => $osContext->getBuild(), 'kernel_version' => $osContext->getKernelVersion()];
        }
        $runtimeContext = $event->getRuntimeContext();
        if ($runtimeContext !== null) {
            $payload['contexts']['runtime'] = ['name' => $runtimeContext->getName(), 'sapi' => $runtimeContext->getSAPI(), 'version' => $runtimeContext->getVersion()];
        }
        if (!empty($event->getContexts())) {
            $payload['contexts'] = array_merge($payload['contexts'] ?? [], $event->getContexts());
        }
        if (!empty($event->getBreadcrumbs())) {
            $payload['breadcrumbs']['values'] = array_map([self::class, 'serializeBreadcrumb'], $event->getBreadcrumbs());
        }
        if (!empty($event->getRequest())) {
            $payload['request'] = $event->getRequest();
        }
        if ($event->getMessage() !== null) {
            if (empty($event->getMessageParams())) {
                $payload['message'] = $event->getMessage();
            } else {
                $payload['message'] = ['message' => $event->getMessage(), 'params' => $event->getMessageParams(), 'formatted' => $event->getMessageFormatted() ?? Str::vsprintfOrNull($event->getMessage(), $event->getMessageParams()) ?? $event->getMessage()];
            }
        }
        $exceptions = $event->getExceptions();
        for ($i = \count($exceptions) - 1; $i >= 0; --$i) {
            $payload['exception']['values'][] = self::serializeException($exceptions[$i]);
        }
        $stacktrace = $event->getStacktrace();
        if ($stacktrace !== null) {
            $payload['stacktrace'] = ['frames' => array_map([self::class, 'serializeStacktraceFrame'], $stacktrace->getFrames())];
        }

        return \sprintf("%s\n%s", JSON::encode($header), JSON::encode($payload));
    }

    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array{
     *     type: string,
     *     value: string,
     *     stacktrace?: array{
     *         frames: array<array<string, mixed>>
     *     },
     *     mechanism?: array{
     *         type: string,
     *         handled: boolean,
     *         data?: array<string, mixed>
     *     }
     * }
     */
    protected static function serializeException(ExceptionDataBag $exception): array
    {
        $exceptionMechanism = $exception->getMechanism();
        $exceptionStacktrace = $exception->getStacktrace();
        $result = ['type' => $exception->getType(), 'value' => $exception->getValue()];
        if ($exceptionStacktrace !== null) {
            $result['stacktrace'] = ['frames' => array_map([self::class, 'serializeStacktraceFrame'], $exceptionStacktrace->getFrames())];
        }
        if ($exceptionMechanism !== null) {
            $result['mechanism'] = ['type' => $exceptionMechanism->getType(), 'handled' => $exceptionMechanism->isHandled()];
            if ($exceptionMechanism->getData() !== []) {
                $result['mechanism']['data'] = $exceptionMechanism->getData();
            }
        }

        return $result;
    }
}
