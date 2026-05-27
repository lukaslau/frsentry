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

namespace FrSentry\Sentry\Integration;

use FrSentry\Sentry\Event;
use FrSentry\Sentry\ExceptionMechanism;
use FrSentry\Sentry\State\HubInterface;
use FrSentry\Sentry\State\Scope;

abstract class AbstractErrorListenerIntegration implements IntegrationInterface
{
    /**
     * Captures the exception using the given hub instance.
     *
     * @param HubInterface $hub The hub instance
     * @param \Throwable $exception The exception instance
     */
    protected function captureException(HubInterface $hub, \Throwable $exception): void
    {
        $hub->withScope(function (Scope $scope) use ($hub, $exception): void {
            $scope->addEventProcessor(\Closure::fromCallable([$this, 'addExceptionMechanismToEvent']));
            $hub->captureException($exception);
        });
    }

    /**
     * Adds the exception mechanism to the event.
     *
     * @param Event $event The event object
     */
    protected function addExceptionMechanismToEvent(Event $event): Event
    {
        $exceptions = $event->getExceptions();
        foreach ($exceptions as $exception) {
            $data = [];
            $mechanism = $exception->getMechanism();
            if ($mechanism !== null) {
                $data = $mechanism->getData();
            }
            $exception->setMechanism(new ExceptionMechanism(ExceptionMechanism::TYPE_GENERIC, \false, $data));
        }

        return $event;
    }
}
