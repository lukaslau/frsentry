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

namespace Frento\FrSentry\src\AdminApi\Controller;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Frento\FrSentry\src\AdminApi\Exception\NoPermissionException;
use Frento\FrSentry\src\AdminApi\Request;
use Frento\FrSentry\src\AdminApi\Validator\Validator;
use Frento\FrSentry\src\Prestashop\TwConfiguration;

class ConfigController extends Controllers
{
    /**
     * @var Validator
     */
    private $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    /**
     * curl
     * 'http://localhost/admin/index.php?controller=AdminFrSentryConfiguration&token=d743724649534bcb7d5f44bd84476a53&FrSentryAdminApiController=config/index/save'
     * -X PATCH
     * -H 'Accept: application/json'
     * -H 'Content-Type: application/json'
     * todo: poprawic data raw
     * --data-raw
     * '{"enable_protection":"on","limit_request_per_min":"32","block_timeout":"31","overwrite_http_x_real_ip":"on","bo_bruteforce_protection":"on","captcha_system":""}'
     */
    public function actionIndexSave()
    {
        if (!$this->checkPrivileges('CREATE')) {
            throw new NoPermissionException();
        }

        $data = Request::getRequestData();

        \Configuration::updateValue(TwConfiguration::$configPrefix . 'backend_key', Request::input($data, 'backend_key', ''));
        \Configuration::updateValue(TwConfiguration::$configPrefix . 'frontend_key', Request::input($data, 'frontend_key', ''));

        if (Request::input($data, 'backend', '')) {
            foreach (Request::input($data, 'backend', '') as $key => $value) {
                \Configuration::updateValue(TwConfiguration::$configPrefix . 'backend--' . $key . '', $value);
            }
        }

        $this->render([]);
    }

    /**
     * curl
     * 'http://localhost/admin/index.php?controller=AdminFrSentryConfiguration&token=d743724649534bcb7d5f44bd84476a53&FrSentryAdminApiController=config/index'
     * GET
     */
    public function actionIndex()
    {
        if (!$this->checkPrivileges('READ')) {
            throw new NoPermissionException();
        }

        $shop = new \Shop(Request::getShopIdInBO(false));

        $this->render([
            'form' => TwConfiguration::getConfiguration(),
            'shop' => $shop->name,
            'error' => null,
        ]);
    }
}
