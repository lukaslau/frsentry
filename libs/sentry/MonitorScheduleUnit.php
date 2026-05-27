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

final class MonitorScheduleUnit implements \Stringable
{
    /**
     * @var string The value of the enum instance
     */
    private $value;
    /**
     * @var array<string, self> A list of cached enum instances
     */
    private static $instances = [];

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function minute(): self
    {
        return self::getInstance('minute');
    }

    public static function hour(): self
    {
        return self::getInstance('hour');
    }

    public static function day(): self
    {
        return self::getInstance('day');
    }

    public static function week(): self
    {
        return self::getInstance('week');
    }

    public static function month(): self
    {
        return self::getInstance('month');
    }

    public static function year(): self
    {
        return self::getInstance('year');
    }

    public function __toString(): string
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
