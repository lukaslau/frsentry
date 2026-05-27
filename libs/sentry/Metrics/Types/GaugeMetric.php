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

namespace FrSentry\Sentry\Metrics\Types;

use FrSentry\Sentry\Tracing\SpanId;
use FrSentry\Sentry\Tracing\TraceId;
use FrSentry\Sentry\Unit;

/**
 * @internal
 */
final class GaugeMetric extends Metric
{
    /**
     * @var string
     */
    public const TYPE = 'gauge';
    /**
     * @var int|float
     */
    private $value;

    /**
     * @param int|float $value
     * @param array<string, int|float|string|bool> $attributes
     */
    public function __construct(string $name, $value, TraceId $traceId, SpanId $spanId, array $attributes, float $timestamp, ?Unit $unit)
    {
        parent::__construct($name, $traceId, $spanId, $timestamp, $attributes, $unit);
        $this->value = $value;
    }

    /**
     * @param int|float $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return int|float
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getType(): string
    {
        return self::TYPE;
    }
}
