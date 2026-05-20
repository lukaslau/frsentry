<?php
/**
 * Sentry module for Prestashop
 * Version: 2.1.1
 * Copyright (c) 2023. Mateusz Szymański Teamwant
 * https://teamwant.pl
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Teamwant <kontakt@teamwant.pl>
 * @copyright Copyright 2016-2025 © Teamwant Mateusz Szymański All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @category  Teamwant
 */

namespace Teamwant\TeamwantSentry\src\Libs;

if (!defined('_PS_VERSION_')) {
    exit;
}

class TeamwantSentryDsn
{
    public static function createFromString(string $value)
    {
        $parsedDsn = parse_url($value);

        if (false === $parsedDsn) {
            throw new \PrestaShopException(sprintf('The "%s" DSN is invalid.', $value));
        }

        foreach (['scheme', 'host', 'path', 'user'] as $component) {
            if (!isset($parsedDsn[$component]) || (isset($parsedDsn[$component]) && empty($parsedDsn[$component]))) {
                throw new \PrestaShopException(sprintf('The "%s" DSN must contain a scheme, a host, a user and a path component.', $value));
            }
        }

        if (isset($parsedDsn['pass']) && empty($parsedDsn['pass'])) {
            throw new \PrestaShopException(sprintf('The "%s" DSN must contain a valid secret key.', $value));
        }

        if (!\in_array($parsedDsn['scheme'], ['http', 'https'], true)) {
            throw new \PrestaShopException(sprintf('The scheme of the "%s" DSN must be either "http" or "https".', $value));
        }

        $segmentPaths = explode('/', $parsedDsn['path']);
        $projectId = array_pop($segmentPaths);
        $lastSlashPosition = strrpos($parsedDsn['path'], '/');
        $path = $parsedDsn['path'];

        if (false !== $lastSlashPosition) {
            $path = substr($parsedDsn['path'], 0, $lastSlashPosition);
        }

        return [
            'dsn' => $value,
            'scheme' => $parsedDsn['scheme'],
            'host' => $parsedDsn['host'],
            'port' => $parsedDsn['port'] ?? ('http' === $parsedDsn['scheme'] ? 80 : 443),
            'projectId' => $projectId,
            'path' => $path,
            'user' => $parsedDsn['user'],
            'pass' => $parsedDsn['pass'] ?? null,
        ];
    }
}
