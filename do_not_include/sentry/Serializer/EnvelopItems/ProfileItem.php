<?php

declare(strict_types=1);

namespace FrSentry\Sentry\Serializer\EnvelopItems;

use FrSentry\Sentry\Event;
use FrSentry\Sentry\Profiling\Profile;
use FrSentry\Sentry\Util\JSON;

/**
 * @internal
 */
class ProfileItem implements EnvelopeItemInterface
{
    public static function toEnvelopeItem(Event $event): ?string
    {
        $header = ['type' => 'profile', 'content_type' => 'application/json'];
        $profile = $event->getSdkMetadata('profile');
        if (!$profile instanceof Profile) {
            return null;
        }
        $payload = $profile->getFormattedData($event);
        if ($payload === null) {
            return null;
        }

        return \sprintf("%s\n%s", JSON::encode($header), JSON::encode($payload));
    }
}
