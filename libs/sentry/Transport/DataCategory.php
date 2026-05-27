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

namespace FrSentry\Sentry\Transport;

class DataCategory
{
    /**
     * @var string
     */
    private $value;
    /**
     * @var array<self>
     */
    private static $instances = [];

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function error(): self
    {
        return self::getInstance('error');
    }

    public static function transaction(): self
    {
        return self::getInstance('transaction');
    }

    // TODO: not sure if this should be called monitor or checkIn.
    public static function checkIn(): self
    {
        return self::getInstance('monitor');
    }

    public static function logItem(): self
    {
        return self::getInstance('log_item');
    }

    public static function logBytes(): self
    {
        return self::getInstance('log_byte');
    }

    public static function profile(): self
    {
        return self::getInstance('profile');
    }

    public static function metric(): self
    {
        return self::getInstance('trace_metric');
    }

    public static function internal(): self
    {
        return self::getInstance('internal');
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }

    private static function getInstance(string $value): self
    {
        if (!isset(self::$instances[$value])) {
            self::$instances[$value] = new self($value);
        }

        return self::$instances[$value];
    }
}
