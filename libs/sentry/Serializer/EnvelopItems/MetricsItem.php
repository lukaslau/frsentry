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
use FrSentry\Sentry\Metrics\Types\Metric;
use FrSentry\Sentry\Util\JSON;

/**
 * @internal
 */
class MetricsItem implements EnvelopeItemInterface
{
    public static function toEnvelopeItem(Event $event): string
    {
        $metrics = $event->getMetrics();
        $header = ['type' => (string) EventType::metrics(), 'item_count' => \count($metrics), 'content_type' => 'application/vnd.sentry.items.trace-metric+json'];

        return \sprintf("%s\n%s", JSON::encode($header), JSON::encode(['items' => array_map(static function (Metric $metric): array {
            return ['timestamp' => $metric->getTimestamp(), 'trace_id' => (string) $metric->getTraceId(), 'span_id' => (string) $metric->getSpanId(), 'name' => $metric->getName(), 'value' => $metric->getValue(), 'unit' => $metric->getUnit() ? (string) $metric->getUnit() : null, 'type' => $metric->getType(), 'attributes' => array_map(static function (Attribute $attribute): array {
                return ['type' => $attribute->getType(), 'value' => $attribute->getValue()];
            }, $metric->getAttributes()->all())];
        }, $metrics)]));
    }
}
