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

namespace FrSentry\Sentry;

use FrSentry\Sentry\Util\SentryUid;

/**
 * This class represents an event ID.
 *
 * @author Stefano Arlandini <sarlandini@alice.it>
 */
final class EventId implements \Stringable
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
        if (!preg_match('/^[a-f0-9]{32}$/i', $value)) {
            throw new \InvalidArgumentException('The $value argument must be a 32 characters long hexadecimal string.');
        }
        $this->value = $value;
    }

    /**
     * Generates a new event ID.
     *
     * @copyright Matt Farina MIT License https://github.com/lootils/uuid/blob/master/LICENSE
     */
    public static function generate(): self
    {
        return new self(SentryUid::generate());
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
