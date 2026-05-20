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

namespace Frento\FrSentry\src\AdminApi;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Tools;

class Request
{
    /**
     * Metoda ma za zadanie zwrócić dane z put/patch/delete do post tak aby spelnic wymagania tools::getAllValues
     * inaczej addons nie pusci tego
     */
    public static function getRequestData($key = '', $default_value = false)
    {
        $params = [];
        $method = $_SERVER['REQUEST_METHOD'];

        if (in_array($method, [Routes::METHOD_POST, Routes::METHOD_PUT, Routes::METHOD_PATCH, Routes::METHOD_DELETE], true)) {
            $params = \Tools::file_get_contents('php://input');
            $params = json_decode($params, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                if ($params) {
                    $_POST = array_merge($_POST, $params);
                }
            } else {
                if (!empty($_FILES)) {
                    $_POST['__FILES'] = $_FILES;
                }
            }
        }

        return !$key ? \Tools::getAllValues() : \Tools::getValue($key, $default_value);
    }

    public static function input($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                $result = [];

                // todo: tu cos jest nei tak
                foreach ($target as $item) {
                    $result[] = self::input($item, $key);
                }

                return $result;
            }

            if (is_array($target) && isset($target[$segment])) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }

    /**
     * Pobieranie ID sklepu
     *
     * @param false $forceId
     *
     * @return int|null
     */
    public static function getShopIdInBO($forceId = false)
    {
        $isMultiShopActive = \Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');

        if (!$isMultiShopActive && !$forceId) {
            return null;
        }

        $idShop = null; // domyslnie wrzucamy null
        $re = '/^s\-(\d+)$/m';
        $context = \Context::getContext();
        preg_match($re, $context->cookie->shopContext, $matches);

        if (!empty($matches[1]) && (int) $matches[1] > 0) {
            $idShop = (int) $matches[1]; // multistore
        }

        return $idShop;
    }

    /**
     * Pobieranie ID sklepu
     *
     * @param false $forceId
     *
     * @return int|null
     */
    public function getLangId()
    {
        $context = \Context::getContext();

        return $context->language->id;
    }
}
