<?php
/**
 * Sentry module for Prestashop
 * Version: 2.1.1
 * Copyright (c) 2023. Mateusz Szymański Frento
 * https://frentoit.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Frento <info@frentoit.com>
 * @copyright Copyright 2016-2025 © Frento Mateusz Szymański All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @category  Frento
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
