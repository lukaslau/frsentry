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

namespace FrSentry\Sentry\Exception;

/**
 * This exception is thrown when an issue is preventing the creation of an {@see Event}.
 */
class EventCreationException extends \RuntimeException
{
    /**
     * EventCreationException constructor.
     *
     * @param \Throwable $previous The original error that was thrown while
     *                             instantiating an {@see Event}
     */
    public function __construct(\Throwable $previous)
    {
        parent::__construct('Unable to instantiate an event', 0, $previous);
    }
}
