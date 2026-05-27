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

namespace FrSentry\Sentry\ClientReport;

use FrSentry\Sentry\Event;
use FrSentry\Sentry\State\HubAdapter;
use FrSentry\Sentry\Transport\DataCategory;

class ClientReportAggregator
{
    /**
     * @var self
     */
    private static $instance;
    /**
     * Nested array for local aggregation. The first key is the category and the second one is the reason.
     *
     * ```
     * [
     *  'example-category' => [
     *      'example-reason' => 10
     *   ]
     * ]
     *```
     *
     * @var array<array<string, int>>
     */
    private $reports = [];

    public function add(DataCategory $category, Reason $reason, int $quantity): void
    {
        $category = $category->getValue();
        $reason = $reason->getValue();
        if ($quantity <= 0) {
            $client = HubAdapter::getInstance()->getClient();
            if ($client !== null) {
                $logger = $client->getOptions()->getLoggerOrNullLogger();
                $logger->debug('Dropping Client report with category={category} and reason={reason} because quantity is zero or negative ({quantity})', ['category' => $category, 'reason' => $reason, 'quantity' => $quantity]);
            }

            return;
        }
        $this->reports[$category][$reason] = ($this->reports[$category][$reason] ?? 0) + $quantity;
    }

    public function flush(): void
    {
        if (empty($this->reports)) {
            return;
        }
        $reports = [];
        foreach ($this->reports as $category => $reasons) {
            foreach ($reasons as $reason => $quantity) {
                $reports[] = new DiscardedEvent($category, $reason, $quantity);
            }
        }
        $event = Event::createClientReport();
        $event->setClientReports($reports);
        $client = HubAdapter::getInstance()->getClient();
        // Reset the client reports only if we successfully sent an event. If it fails it
        // can be sent on the next flush, or it gets discarded anyway.
        if ($client !== null && $client->captureEvent($event) !== null) {
            $this->reports = [];
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
