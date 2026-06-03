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

namespace Frento\FrSentry\src\Libs;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FrSentryDsn
{
    /**
     * Parses a Sentry DSN string into its components.
     *
     * @param string $dsn e.g. https://publicKey@o123.ingest.sentry.io/456789
     *
     * @return array{scheme: string, host: string, port: int, user: string, projectId: string}
     *
     * @throws \InvalidArgumentException on malformed DSN
     */
    public static function parse(string $dsn): array
    {
        $parts = parse_url($dsn);

        if ($parts === false) {
            throw new \InvalidArgumentException("Malformed Sentry DSN: {$dsn}");
        }

        foreach (['scheme', 'host', 'path', 'user'] as $field) {
            if (empty($parts[$field])) {
                throw new \InvalidArgumentException("Sentry DSN is missing '{$field}': {$dsn}");
            }
        }

        if (!in_array($parts['scheme'], ['http', 'https'], true)) {
            throw new \InvalidArgumentException("Sentry DSN scheme must be http or https: {$dsn}");
        }

        return [
            'scheme' => $parts['scheme'],
            'host' => $parts['host'],
            'port' => $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80),
            'user' => $parts['user'],
            'projectId' => trim($parts['path'], '/'),
        ];
    }
}
