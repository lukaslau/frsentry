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

namespace FrSentry\Sentry\Profiling;

use FrSentry\Sentry\Options;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class Profiler
{
    /**
     * @var \ExcimerProfiler|null
     */
    private $profiler;
    /**
     * @var Profile
     */
    private $profile;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var float The sample rate (10.01ms/101 Hz)
     */
    private const SAMPLE_RATE = 0.0101;
    /**
     * @var int The maximum stack depth
     */
    private const MAX_STACK_DEPTH = 128;

    public function __construct(?Options $options = null)
    {
        $this->logger = $options !== null ? $options->getLoggerOrNullLogger() : new NullLogger();
        $this->profile = new Profile($options);
        $this->initProfiler();
    }

    public function start(): void
    {
        if ($this->profiler !== null) {
            $this->profiler->start();
        }
    }

    public function stop(): void
    {
        if ($this->profiler !== null) {
            $this->profiler->stop();
            $this->profile->setExcimerLog($this->profiler->flush());
        }
    }

    public function getProfile(): ?Profile
    {
        if ($this->profiler === null) {
            return null;
        }

        return $this->profile;
    }

    private function initProfiler(): void
    {
        if (!\extension_loaded('excimer')) {
            $this->logger->warning('The profiler was started but is not available because the "excimer" extension is not loaded.');

            return;
        }
        $this->profiler = new \ExcimerProfiler();
        $this->profile->setStartTimeStamp(microtime(\true));
        $this->profiler->setEventType(\EXCIMER_REAL);
        $this->profiler->setPeriod(self::SAMPLE_RATE);
        $this->profiler->setMaxDepth(self::MAX_STACK_DEPTH);
    }
}
