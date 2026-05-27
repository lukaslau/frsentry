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

class PHPConfiguration
{
    public static function isBooleanIniOptionEnabled(string $option): bool
    {
        $value = \ini_get($option);
        if (empty($value)) {
            return \false;
        }

        // https://www.php.net/manual/en/function.ini-get.php#refsect1-function.ini-get-notes
        return \in_array(strtolower($value), ['1', 'on', 'true'], \true);
    }
}
