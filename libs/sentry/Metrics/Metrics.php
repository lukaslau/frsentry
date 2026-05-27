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

namespace FrSentry\Sentry\Metrics;

use FrSentry\Sentry\EventId;
use FrSentry\Sentry\Tracing\SpanContext;
use FrSentry\Sentry\Unit;

use function FrSentry\Sentry\trace;

class_alias(Unit::class, 'FrSentry\Sentry\Metrics\MetricsUnit');
/**
 * @deprecated use TraceMetrics instead
 */
class Metrics
{
    /**
     * @var self|null
     */
    private static $instance;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param array<string, string> $tags
     *
     * @deprecated Use TraceMetrics::count() instead. To be removed in 5.x.
     */
    public function increment(string $key, float $value, ?Unit $unit = null, array $tags = [], ?int $timestamp = null, int $stackLevel = 0): void
    {
    }

    /**
     * @param array<string, string> $tags
     *
     * @deprecated Use TraceMetrics::distribution() instead. Metrics API is a no-op and will be removed in 5.x.
     */
    public function distribution(string $key, float $value, ?Unit $unit = null, array $tags = [], ?int $timestamp = null, int $stackLevel = 0): void
    {
    }

    /**
     * @param array<string, string> $tags
     *
     * @deprecated Use TraceMetrics::gauge() instead. To be removed in 5.x.
     */
    public function gauge(string $key, float $value, ?Unit $unit = null, array $tags = [], ?int $timestamp = null, int $stackLevel = 0): void
    {
    }

    /**
     * @param int|string $value
     * @param array<string, string> $tags
     *
     * @deprecated Metrics are no longer supported. Metrics API is a no-op and will be removed in 5.x.
     */
    public function set(string $key, $value, ?Unit $unit = null, array $tags = [], ?int $timestamp = null, int $stackLevel = 0): void
    {
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     * @param array<string, string> $tags
     *
     * @return T
     *
     * @deprecated Metrics are no longer supported. Metrics API is a no-op and will be removed in 5.x.
     */
    public function timing(string $key, callable $callback, array $tags = [], int $stackLevel = 0)
    {
        return trace(static function () use ($callback) {
            return $callback();
        }, SpanContext::make()->setOp('metric.timing')->setOrigin('auto.measure.metrics.timing')->setDescription($key));
    }

    /**
     * @deprecated Metrics are no longer supported. Metrics API is a no-op and will be removed in 5.x.
     */
    public function flush(): ?EventId
    {
        return null;
    }
}
