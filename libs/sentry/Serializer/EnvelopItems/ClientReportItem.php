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

use FrSentry\Sentry\ClientReport\DiscardedEvent;
use FrSentry\Sentry\Event;
use FrSentry\Sentry\Util\JSON;

class ClientReportItem implements EnvelopeItemInterface
{
    public static function toEnvelopeItem(Event $event): ?string
    {
        $reports = $event->getClientReports();
        $headers = ['type' => 'client_report'];
        $body = ['timestamp' => $event->getTimestamp(), 'discarded_events' => array_map(static function (DiscardedEvent $report) {
            return ['category' => $report->getCategory(), 'reason' => $report->getReason(), 'quantity' => $report->getQuantity()];
        }, $reports)];

        return \sprintf("%s\n%s", JSON::encode($headers), JSON::encode($body));
    }
}
