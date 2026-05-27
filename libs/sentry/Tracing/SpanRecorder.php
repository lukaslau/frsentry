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

final class SpanRecorder
{
    /**
     * @var int Maximum number of spans that should be stored
     */
    private $maxSpans;
    /**
     * @var Span[] List of spans managed by this recorder
     */
    private $spans = [];

    /**
     * Constructor.
     *
     * @param int $maxSpans The maximum number of spans to record before
     *                      detaching the recorder from the span
     */
    public function __construct(int $maxSpans = 1000)
    {
        $this->maxSpans = $maxSpans;
    }

    /**
     * Adds a span to the list of recorded spans or detaches the recorder if the
     * maximum number of spans to store has been reached.
     */
    public function add(Span $span): self
    {
        if (\count($this->spans) > $this->maxSpans) {
            $span->detachSpanRecorder();
        } else {
            $this->spans[] = $span;
        }

        return $this;
    }

    /**
     * Gets all the spans managed by this recorder.
     *
     * @return Span[]
     */
    public function getSpans(): array
    {
        return $this->spans;
    }
}
