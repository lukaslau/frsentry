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

/**
 * This interface can be used to customize how an object is serialized in the
 * payload of an event.
 */
interface SerializableInterface
{
    /**
     * Returns an array representation of the object for Sentry.
     *
     * @return mixed[]|null
     */
    public function toSentry(): ?array;
}
