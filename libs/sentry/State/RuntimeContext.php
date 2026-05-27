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

namespace FrSentry\Sentry\State;

use FrSentry\Sentry\Logs\LogsAggregator;
use FrSentry\Sentry\Metrics\MetricsAggregator;

/**
 * Holds runtime-local state for a single unit of work.
 *
 * A unit of work can be an HTTP request, a queue job, a worker task, or any
 * explicit lifecycle wrapped with startContext()/endContext().
 *
 * @internal
 */
final class RuntimeContext
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var HubInterface
     */
    private $hub;
    /**
     * @var LogsAggregator
     */
    private $logsAggregator;
    /**
     * @var MetricsAggregator
     */
    private $metricsAggregator;

    public function __construct(string $id, HubInterface $hub)
    {
        $this->id = $id;
        $this->hub = $hub;
        $this->logsAggregator = new LogsAggregator();
        $this->metricsAggregator = new MetricsAggregator();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getHub(): HubInterface
    {
        return $this->hub;
    }

    public function setHub(HubInterface $hub): void
    {
        $this->hub = $hub;
    }

    public function getLogsAggregator(): LogsAggregator
    {
        return $this->logsAggregator;
    }

    public function getMetricsAggregator(): MetricsAggregator
    {
        return $this->metricsAggregator;
    }
}
