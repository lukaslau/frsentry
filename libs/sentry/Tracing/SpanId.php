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

namespace FrSentry\Sentry\Tracing;

use FrSentry\Sentry\Util\SentryUid;

/**
 * This class represents an span ID.
 */
final class SpanId implements \Stringable
{
    /**
     * @var string The ID
     */
    private $value;

    /**
     * Class constructor.
     *
     * @param string $value The ID
     */
    public function __construct(string $value)
    {
        if (!preg_match('/^[a-f0-9]{16}$/i', $value)) {
            throw new \InvalidArgumentException('The $value argument must be a 16 characters long hexadecimal string.');
        }
        $this->value = $value;
    }

    /**
     * Generates a new span ID.
     */
    public static function generate(): self
    {
        return new self(substr(SentryUid::generate(), 0, 16));
    }

    /**
     * Compares whether two objects are equals.
     *
     * @param SpanId $other The object to compare
     */
    public function isEqualTo(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
