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
use FrSentry\Sentry\EventHint;
use FrSentry\Sentry\SentrySdk;
use FrSentry\Sentry\State\Scope;

/**
 * This integration sets the `transaction` attribute of the event to the value
 * found in the raw event payload or to the value of the `PATH_INFO` server var
 * if present.
 *
 * @author Stefano Arlandini <sarlandini@alice.it>
 */
final class TransactionIntegration implements IntegrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(static function (Event $event, EventHint $hint): Event {
            $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);
            // The client bound to the current hub, if any, could not have this
            // integration enabled. If this is the case, bail out
            if ($integration === null) {
                return $event;
            }
            if ($event->getTransaction() !== null) {
                return $event;
            }
            if (isset($hint->extra['transaction']) && \is_string($hint->extra['transaction'])) {
                $event->setTransaction($hint->extra['transaction']);
            } elseif (isset($_SERVER['PATH_INFO'])) {
                $event->setTransaction($_SERVER['PATH_INFO']);
            }

            return $event;
        });
    }
}
