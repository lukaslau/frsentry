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
class RepresentationSerializer extends AbstractSerializer implements RepresentationSerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function representationSerialize($value)
    {
        $value = $this->serializeRecursively($value);
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value;
    }

    /**
     * This method is overridden to return even basic types as strings.
     *
     * @param mixed $value The value that needs to be serialized
     *
     * @return string
     */
    protected function serializeValue($value)
    {
        if ($value === null) {
            return 'null';
        }
        if ($value === \false) {
            return 'false';
        }
        if ($value === \true) {
            return 'true';
        }
        if (\is_float($value) && (int) $value == $value) {
            return $value . '.0';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }

        return (string) parent::serializeValue($value);
    }
}
