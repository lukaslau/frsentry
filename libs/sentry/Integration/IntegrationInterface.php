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

namespace FrSentry\Sentry\Integration;

/**
 * This interface defines a contract that must be implemented by integrations,
 * bindings or hooks that integrate certain frameworks or environments with the SDK.
 */
interface IntegrationInterface
{
    /**
     * Initializes the current integration by registering it once.
     */
    public function setupOnce(): void;
}
