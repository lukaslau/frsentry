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
use FrSentry\Sentry\Util\JSON;

/**
 * @internal
 */
class CheckInItem implements EnvelopeItemInterface
{
    public static function toEnvelopeItem(Event $event): string
    {
        $header = ['type' => (string) $event->getType(), 'content_type' => 'application/json'];
        $payload = [];
        $checkIn = $event->getCheckIn();
        if ($checkIn !== null) {
            $payload = ['check_in_id' => $checkIn->getId(), 'monitor_slug' => $checkIn->getMonitorSlug(), 'status' => (string) $checkIn->getStatus(), 'duration' => $checkIn->getDuration(), 'release' => $checkIn->getRelease(), 'environment' => $checkIn->getEnvironment()];
            if ($checkIn->getMonitorConfig() !== null) {
                $payload['monitor_config'] = $checkIn->getMonitorConfig()->toArray();
            }
            if (!empty($event->getContexts()['trace'])) {
                $payload['contexts']['trace'] = $event->getContexts()['trace'];
            }
        }

        return \sprintf("%s\n%s", JSON::encode($header), JSON::encode($payload));
    }
}
