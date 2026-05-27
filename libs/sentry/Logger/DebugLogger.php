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

namespace FrSentry\Sentry\Logger;

use Psr\Log\AbstractLogger;

abstract class DebugLogger extends AbstractLogger
{
    /**
     * @param mixed $level
     * @param string|\Stringable $message
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        $formattedMessageAndContext = implode(' ', array_filter([(string) $message, json_encode($context)]));
        $this->write(\sprintf("sentry/sentry: [%s] %s\n", $level, $formattedMessageAndContext));
    }

    abstract public function write(string $message): void;
}
