<?php
/**
 * Sentry module for Prestashop
 * Version: 2.1.1
 * Copyright (c) 2023. Mateusz Szymański Frento IT
 * https://frentoit.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Frento IT <info@frentoit.com>
 * @copyright Copyright 2016-2025 © Frento IT Mateusz Szymański All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @category  Frento IT
 */

namespace Frento\FrSentry\src\AdminApi\Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Frento\FrSentry\src\AdminApi\JsonRender;

class RouteNotFoundException extends \Exception
{
    protected $code = JsonRender::HTTP_NOT_FOUND;
    protected $message = 'Route to resource not found';
    public $showFile = false;
}
