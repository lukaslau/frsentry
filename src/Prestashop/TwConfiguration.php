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

namespace Frento\FrSentry\src\Prestashop;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Frento\FrSentry\src\Types\CustomBoolean;

class TwConfiguration
{
    public static $configPrefix = 'FRSENTRY_';

    public static function getConfiguration()
    {
        return [
            'backend_key' => \Configuration::get(self::$configPrefix . 'backend_key', null, null, null, null),
            'frontend_key' => \Configuration::get(self::$configPrefix . 'frontend_key', null, null, null, null),
            'backend' => [
                'php_ignore_user' => CustomBoolean::createVO(\Configuration::get(self::$configPrefix . 'backend--php_ignore_user', null, null, null, true)),
                'php_ignore_deprecated' => CustomBoolean::createVO(\Configuration::get(self::$configPrefix . 'backend--php_ignore_deprecated', null, null, null, true)),
                'php_ignore_warning' => CustomBoolean::createVO(\Configuration::get(self::$configPrefix . 'backend--php_ignore_warning', null, null, null, true)),
                'php_ignore_noticed' => CustomBoolean::createVO(\Configuration::get(self::$configPrefix . 'backend--php_ignore_noticed', null, null, null, true)),
                'use_backoffice' => CustomBoolean::createVO(\Configuration::get(self::$configPrefix . 'backend--use_backoffice', null, null, null, false)),
                'insights_frontend' => CustomBoolean::createVO(\Configuration::get(self::$configPrefix . 'backend--insights_frontend', null, null, null, false)),
                'profiling_frontend' => CustomBoolean::createVO(\Configuration::get(self::$configPrefix . 'backend--profiling_frontend', null, null, null, false)),
                'insights_backend' => CustomBoolean::createVO(\Configuration::get(self::$configPrefix . 'backend--insights_backend', null, null, null, false)),
                'profiling_backend' => CustomBoolean::createVO(\Configuration::get(self::$configPrefix . 'backend--profiling_backend', null, null, null, false)),
            ],
        ];
    }
}
