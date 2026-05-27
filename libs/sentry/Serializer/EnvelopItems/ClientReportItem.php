<?php

declare (strict_types=1);
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
