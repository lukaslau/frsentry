<?php

declare (strict_types=1);
namespace FrSentry\Sentry\Serializer\EnvelopItems;

use FrSentry\Sentry\Event;
/**
 * @internal
 */
interface EnvelopeItemInterface
{
    public static function toEnvelopeItem(Event $event): ?string;
}
