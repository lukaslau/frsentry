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

namespace FrSentry\Sentry\Transport;

use FrSentry\Sentry\Event;

/**
 * This class contains the details of the sending operation of an event, e.g.
 * if it was sent successfully or if it was skipped because of some reason.
 */
class Result
{
    /**
     * @var ResultStatus The status of the sending operation of the event
     */
    private $status;
    /**
     * @var Event|null The instance of the event being sent, or null if it
     *                 was not available yet
     */
    private $event;

    public function __construct(ResultStatus $status, ?Event $event = null)
    {
        $this->status = $status;
        $this->event = $event;
    }

    /**
     * Gets the status of the sending operation of the event.
     */
    public function getStatus(): ResultStatus
    {
        return $this->status;
    }

    /**
     * Gets the instance of the event being sent, or null if it was not available yet.
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }
}
