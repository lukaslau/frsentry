<?php
/*
 * Copyright (c) 2023-2026 Frento IT <info@frentoit.com>
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

namespace Frento\FrSentry\src\Types;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomBoolean
{
    /**
     * @var array
     */
    private static $trueValue = [
        true,
        'true',
        'on',
        1,
        '1',
    ];

    public static function createVO($value)
    {
        if (in_array($value, self::$trueValue, true)) {
            return true;
        }

        return false;
    }
}
