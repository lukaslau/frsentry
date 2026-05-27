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

namespace FrSentry\Sentry\Monolog;

use FrSentry\Monolog\Handler\AbstractProcessingHandler;
use FrSentry\Monolog\Level;
use FrSentry\Monolog\Logger;
use FrSentry\Monolog\LogRecord;
use FrSentry\Sentry\Breadcrumb;
use FrSentry\Sentry\Event;
use FrSentry\Sentry\State\HubInterface;
use FrSentry\Sentry\State\Scope;
use Psr\Log\LogLevel;

/**
 * This Monolog handler logs every message as a {@see Breadcrumb} into the current {@see Scope},
 * to enrich any event sent to Sentry.
 */
final class BreadcrumbHandler extends AbstractProcessingHandler
{
    /**
     * @var HubInterface
     */
    private $hub;

    /**
     * @param HubInterface $hub The hub to which errors are reported
     * @param int|string $level The minimum logging level at which this
     *                          handler will be triggered
     * @param bool $bubble Whether the messages that are handled can
     *                     bubble up the stack or not
     *
     * @phpstan-param int|string|Level|LogLevel::* $level
     */
    public function __construct(HubInterface $hub, $level = Logger::DEBUG, bool $bubble = \true)
    {
        $this->hub = $hub;
        parent::__construct($level, $bubble);
    }

    /**
     * @param LogRecord|array{
     *      level: int,
     *      channel: string,
     *      datetime: \DateTimeImmutable,
     *      message: string,
     *      extra?: array<string, mixed>
     * } $record {@see https://github.com/Seldaek/monolog/blob/main/doc/message-structure.md}
     */
    protected function write($record): void
    {
        $datetime = $record['datetime'] ?? null;
        $timestamp = $datetime instanceof \DateTimeInterface ? $datetime->getTimestamp() + (int) $datetime->format('u') / 1000000 : null;
        $breadcrumb = new Breadcrumb($this->getBreadcrumbLevel($record['level']), $this->getBreadcrumbType($record['level']), $record['channel'], $record['message'], ($record['context'] ?? []) + ($record['extra'] ?? []), $timestamp);
        $this->hub->addBreadcrumb($breadcrumb);
    }

    /**
     * @param Level|int $level
     */
    private function getBreadcrumbLevel($level): string
    {
        if ($level instanceof Level) {
            $level = $level->value;
        }
        switch ($level) {
            case Logger::DEBUG:
                return Breadcrumb::LEVEL_DEBUG;
            case Logger::INFO:
            case Logger::NOTICE:
                return Breadcrumb::LEVEL_INFO;
            case Logger::WARNING:
                return Breadcrumb::LEVEL_WARNING;
            case Logger::ERROR:
                return Breadcrumb::LEVEL_ERROR;
            default:
                return Breadcrumb::LEVEL_FATAL;
        }
    }

    private function getBreadcrumbType(int $level): string
    {
        if ($level >= Logger::ERROR) {
            return Breadcrumb::TYPE_ERROR;
        }

        return Breadcrumb::TYPE_DEFAULT;
    }
}
