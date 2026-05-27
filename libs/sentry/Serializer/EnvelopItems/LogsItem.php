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

use FrSentry\Sentry\Attributes\Attribute;
use FrSentry\Sentry\Event;
use FrSentry\Sentry\EventType;
use FrSentry\Sentry\Logs\Log;
use FrSentry\Sentry\Util\JSON;

/**
 * @internal
 */
class LogsItem implements EnvelopeItemInterface
{
    public static function toEnvelopeItem(Event $event): string
    {
        $logs = $event->getLogs();
        $header = ['type' => (string) EventType::logs(), 'item_count' => \count($logs), 'content_type' => 'application/vnd.sentry.items.log+json'];

        return \sprintf("%s\n%s", JSON::encode($header), JSON::encode(['items' => array_map(static function (Log $log): array {
            return ['timestamp' => $log->getTimestamp(), 'trace_id' => $log->getTraceId(), 'level' => (string) $log->getLevel(), 'body' => $log->getBody(), 'attributes' => array_map(static function (Attribute $attribute): array {
                return ['type' => $attribute->getType(), 'value' => $attribute->getValue()];
            }, $log->attributes()->all())];
        }, $logs)]));
    }
}
