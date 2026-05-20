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

namespace Frento\FrSentry\src\AdminApi\Controller;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Frento\FrSentry\src\AdminApi\JsonRender;
use Frento\FrSentry\src\AdminApi\Routes;

class Controllers
{
    /**
     * @var \Module
     */
    protected $module;

    /**
     * @param Routes $routes
     * @param \Module $module
     */
    public function load(Routes $routes, $module)
    {
        $this->module = $module;
        $this->{$routes->getAction()}();
    }

    // /**
    // * Metoda ma za zadanie dodac dane z put/patch/delete do post tak aby spelnic wymagania tools::getAllValues
    // * inaczej addons nie pusci tego
    // * @deprecated Od teraz to request dto steruje danymi ktore przychodza
    // */
    // public function loadParams()
    // {
    //    $params = array();
    //    $method = $_SERVER['REQUEST_METHOD'];
    //
    //    if (in_array($method, [Routes::METHOD_POST, Routes::METHOD_PUT, Routes::METHOD_PATCH, Routes::METHOD_DELETE])) {
    //        $params = file_get_contents('php://input');
    //        $params = json_decode($params, true);
    //        if (json_last_error() === JSON_ERROR_NONE) {
    //            if ($params) {
    //                $_POST = array_merge($_POST, $params);
    //            }
    //        } else {
    //            if (!empty($_FILES)) {
    //                $_POST['__FILES'] = $_FILES;
    //            }
    //        }
    //    }
    // }

    protected function render(array $data = [], int $code = JsonRender::HTTP_OK)
    {
        $jsonRender = new JsonRender();
        $jsonRender->render($data, $code);
    }

    protected function checkPrivileges(string $slug, string $prefix = 'ROLE_MOD_MODULE_FRSENTRY_'): bool
    {
        $employeId = \Context::getContext()->employee->id_profile;

        $row = \Db::getInstance()->getRow(
            (new \DbQuery())
                ->from('access', 'a')
                ->innerJoin('authorization_role', 'ar', 'ar.id_authorization_role = a.id_authorization_role')
                ->where(
                    'a.id_profile = ' . pSQL($employeId)
                    . ' and ar.slug = "' . pSQL($prefix . $slug) . '"'
                )
        );

        if ($row) {
            return true;
        }

        $row = \Db::getInstance()->getRow(
            (new \DbQuery())
                ->from('module_access', 'a')
                ->innerJoin('authorization_role', 'ar', 'ar.id_authorization_role = a.id_authorization_role')
                ->where(
                    'a.id_profile = ' . pSQL($employeId)
                    . ' and ar.slug = "' . pSQL($prefix . $slug) . '"'
                )
        );

        if ($row) {
            return true;
        }

        return false;
    }

    /**
     * Pobieranie ID sklepu
     *
     * @param false $forceId
     *
     * @return int|null
     *
     * @deprecated  pozbyc sie tego
     */
    protected function getShopId($forceId = false)
    {
        $isMultiShopActive = \Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');

        if (!$isMultiShopActive && !$forceId) {
            return null;
        }

        if (empty($this->context)) {
            $this->context = \Context::getContext();
        }

        $idShop = null; // domyslnie wrzucamy null
        $re = '/^s\-(\d+)$/m';
        preg_match($re, $this->context->cookie->shopContext, $matches);

        if (!empty($matches[1]) && (int) $matches[1] > 0) {
            $idShop = (int) $matches[1]; // multistore
        }

        return $idShop;
    }
}
