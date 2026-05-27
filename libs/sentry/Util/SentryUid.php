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

/**
 * @internal
 */
final class SentryUid
{
    /**
     * Generate a random "Sentry UID", a UUID version 4 without dashes.
     *
     * @copyright Fabien Potencier MIT License https://github.com/symfony/polyfill/blob/main/LICENSE
     */
    public static function generate(): string
    {
        if (\function_exists('uuid_create')) {
            return strtolower(str_replace('-', '', uuid_create(\UUID_TYPE_RANDOM)));
        }
        $uuid = bin2hex(random_bytes(16));

        return \sprintf(
            '%08s%04s4%03s%04x%012s',
            // 32 bits for "time_low"
            substr($uuid, 0, 8),
            // 16 bits for "time_mid"
            substr($uuid, 8, 4),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            substr($uuid, 13, 3),
            // 16 bits:
            // * 8 bits for "clk_seq_hi_res",
            // * 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            hexdec(substr($uuid, 16, 4)) & 0x3FFF | 0x8000,
            // 48 bits for "node"
            substr($uuid, 20, 12)
        );
    }
}
