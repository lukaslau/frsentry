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
