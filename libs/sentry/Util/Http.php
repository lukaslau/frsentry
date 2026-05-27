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

namespace FrSentry\Sentry\Util;

use FrSentry\Sentry\Client;
use FrSentry\Sentry\Dsn;

/**
 * @internal
 */
final class Http
{
    public static function getSentryAuthHeader(Dsn $dsn, string $sdkIdentifier, string $sdkVersion): string
    {
        $authHeader = ['sentry_version=' . Client::PROTOCOL_VERSION, 'sentry_client=' . $sdkIdentifier . '/' . $sdkVersion, 'sentry_key=' . $dsn->getPublicKey()];

        return 'Sentry ' . implode(', ', $authHeader);
    }

    /**
     * @return string[]
     */
    public static function getRequestHeaders(Dsn $dsn, string $sdkIdentifier, string $sdkVersion): array
    {
        return ['Content-Type: application/x-sentry-envelope', 'X-Sentry-Auth: ' . self::getSentryAuthHeader($dsn, $sdkIdentifier, $sdkVersion)];
    }

    /**
     * @param string[][] $headers
     *
     * @param-out string[][] $headers
     */
    public static function parseResponseHeaders(string $headerLine, array &$headers): int
    {
        if (strpos($headerLine, ':') === \false) {
            return \strlen($headerLine);
        }
        [$name, $value] = explode(':', trim($headerLine), 2);
        $name = trim($name);
        $value = trim($value);
        if (isset($headers[$name])) {
            $headers[$name][] = $value;
        } else {
            $headers[$name] = (array) $value;
        }

        return \strlen($headerLine);
    }
}
