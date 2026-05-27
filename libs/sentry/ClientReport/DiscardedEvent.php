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

class DiscardedEvent
{
    /**
     * @var string
     */
    private $reason;
    /**
     * @var string
     */
    private $category;
    /**
     * @var int
     */
    private $quantity;

    public function __construct(string $category, string $reason, int $quantity)
    {
        $this->category = $category;
        $this->reason = $reason;
        $this->quantity = $quantity;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
