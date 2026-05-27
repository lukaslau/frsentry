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
 * Serializes a value into a representation that should reasonably suggest
 * both the type and value, and be serializable into JSON.
 */
interface RepresentationSerializerInterface
{
    /**
     * Serialize an object (recursively) into something safe to be sent as a stacktrace frame argument.
     *
     * The main difference with the {@link SerializerInterface} is the fact that even basic types
     * (bool, int, float) are converted into strings, to avoid misrepresentations on the server side.
     *
     * @param mixed $value
     *
     * @return mixed[]|string|null
     */
    public function representationSerialize($value);
}
