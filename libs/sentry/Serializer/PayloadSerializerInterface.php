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

namespace FrSentry\Sentry\Serializer;

use FrSentry\Sentry\Event;

/**
 * This interface defines the contract for the classes willing to serialize an
 * event object to a format suitable for sending over the wire to Sentry.
 */
interface PayloadSerializerInterface
{
    /**
     * Serializes the given event object into a string.
     *
     * @param Event $event The event object
     */
    public function serialize(Event $event): string;
}
