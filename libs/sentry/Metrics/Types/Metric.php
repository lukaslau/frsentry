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

use FrSentry\Sentry\Attributes\AttributeBag;
use FrSentry\Sentry\Tracing\SpanId;
use FrSentry\Sentry\Tracing\TraceId;
use FrSentry\Sentry\Unit;

abstract class Metric
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var TraceId
     */
    private $traceId;
    /**
     * @var SpanId
     */
    private $spanId;
    /**
     * @var float
     */
    private $timestamp;
    /**
     * @var AttributeBag
     */
    private $attributes;
    /**
     * @var Unit|null
     */
    private $unit;

    /**
     * @param array<string, int|float|string|bool> $attributes
     */
    public function __construct(string $name, TraceId $traceId, SpanId $spanId, float $timestamp, array $attributes, ?Unit $unit)
    {
        $this->name = $name;
        $this->unit = $unit;
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->timestamp = $timestamp;
        $this->attributes = new AttributeBag();
        foreach ($attributes as $key => $value) {
            $this->attributes->set($key, $value);
        }
    }

    /**
     * @param int|float $value
     */
    abstract public function setValue($value): void;

    abstract public function getType(): string;

    /**
     * @return int|float
     */
    abstract public function getValue();

    public function getName(): string
    {
        return $this->name;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function getTraceId(): TraceId
    {
        return $this->traceId;
    }

    public function getSpanId(): SpanId
    {
        return $this->spanId;
    }

    public function getAttributes(): AttributeBag
    {
        return $this->attributes;
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
}
