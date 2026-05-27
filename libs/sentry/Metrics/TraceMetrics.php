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
use FrSentry\Sentry\Metrics\Types\CounterMetric;
use FrSentry\Sentry\Metrics\Types\DistributionMetric;
use FrSentry\Sentry\Metrics\Types\GaugeMetric;
use FrSentry\Sentry\SentrySdk;
use FrSentry\Sentry\Unit;

class TraceMetrics
{
    /**
     * @var self|null
     */
    private static $instance;

    public function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new TraceMetrics();
        }

        return self::$instance;
    }

    /**
     * @param int|float $value
     * @param array<string, int|float|string|bool> $attributes
     */
    public function count(string $name, $value, array $attributes = [], ?Unit $unit = null): void
    {
        $this->aggregator()->add(CounterMetric::TYPE, $name, $value, $attributes, $unit);
    }

    /**
     * @param int|float $value
     * @param array<string, int|float|string|bool> $attributes
     */
    public function distribution(string $name, $value, array $attributes = [], ?Unit $unit = null): void
    {
        $this->aggregator()->add(DistributionMetric::TYPE, $name, $value, $attributes, $unit);
    }

    /**
     * @param int|float $value
     * @param array<string, int|float|string|bool> $attributes
     */
    public function gauge(string $name, $value, array $attributes = [], ?Unit $unit = null): void
    {
        $this->aggregator()->add(GaugeMetric::TYPE, $name, $value, $attributes, $unit);
    }

    public function flush(): ?EventId
    {
        return $this->aggregator()->flush();
    }

    private function aggregator(): MetricsAggregator
    {
        return SentrySdk::getCurrentRuntimeContext()->getMetricsAggregator();
    }
}
