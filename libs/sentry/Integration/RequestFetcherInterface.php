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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Allows customizing the request information that is attached to the logged event.
 * An implementation of this interface can be passed to RequestIntegration.
 */
interface RequestFetcherInterface
{
    /**
     * Returns the PSR-7 request object that will be attached to the logged event.
     */
    public function fetchRequest(): ?ServerRequestInterface;
}
