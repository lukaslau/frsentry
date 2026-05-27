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

namespace FrSentry\Sentry\Logs;

use FrSentry\Sentry\Attributes\AttributeBag;

class Log
{
    /**
     * @var float
     */
    private $timestamp;
    /**
     * @var string
     */
    private $traceId;
    /**
     * @var LogLevel
     */
    private $level;
    /**
     * @var string
     */
    private $body;
    /**
     * @var AttributeBag
     */
    private $attributes;

    public function __construct(float $timestamp, string $traceId, LogLevel $level, string $body)
    {
        $this->timestamp = $timestamp;
        $this->traceId = $traceId;
        $this->level = $level;
        $this->body = $body;
        $this->attributes = new AttributeBag();
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    public function setTimestamp(float $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function setTraceId(string $traceId): self
    {
        $this->traceId = $traceId;

        return $this;
    }

    public function getLevel(): LogLevel
    {
        return $this->level;
    }

    public function setLevel(LogLevel $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getPsrLevel(): string
    {
        return $this->level->toPsrLevel();
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function attributes(): AttributeBag
    {
        return $this->attributes;
    }

    /**
     * @param mixed $value
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes->set($key, $value);

        return $this;
    }
}
