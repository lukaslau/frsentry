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

namespace FrSentry\Sentry\HttpClient;

final class Request
{
    /**
     * @var string
     */
    private $stringBody;

    public function hasStringBody(): bool
    {
        return $this->stringBody !== null;
    }

    public function getStringBody(): ?string
    {
        return $this->stringBody;
    }

    public function setStringBody(string $stringBody): void
    {
        $this->stringBody = $stringBody;
    }
}
