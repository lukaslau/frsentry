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

namespace Frento\FrSentry\src\AdminApi;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Loader
{
    /**
     * @var Routes
     */
    private $routes;

    /**
     * @var \Module
     */
    private $module;

    /**
     * @param \Module $module
     *
     * @throws \Exception
     */
    public function __construct($module)
    {
        $this->module = $module;

        try {
            if (!defined('_PS_ADMIN_DIR_')) {
                throw new \Exception('Only admin api avaliable');
            }

            if (empty(\Tools::getValue('FrSentryAdminApiController'))) {
                throw new \Exception('Route is empty');
            }

            if (empty(\Context::getContext()->employee->id)) {
                throw new \Exception('You are not logged');
            }
        } catch (\Throwable $t) {
            $this->renderException(get_class($t), $t);
        }

        $this->route = (string) \Tools::getValue('FrSentryAdminApiController');
        $this->routes = new Routes();
    }

    /**
     * Ładujemy cały mechanizm
     */
    public function run()
    {
        try {
            $routes = $this->routes->get($this->route);
            $routes->getController()->load($routes, $this->module);
        } catch (\Throwable $t) {
            $this->renderException(get_class($t), $t);
        }
    }

    private function renderException(string $className, \Throwable $throwable)
    {
        $response = new JsonRender();
        $code = ($throwable->getCode()) ? $throwable->getCode() : JsonRender::HTTP_INTERNAL_SERVER_ERROR;
        $data = [
            'code' => $code,
            'message' => $this->trans($throwable->getMessage(), []),
            'file' => $throwable->getFile() . ':' . $throwable->getLine(),
        ];

        if (isset($throwable->showFile) && $throwable->showFile === false) {
            unset($data['file']);
        }

        $response->render($data, $code);
    }

    private function trans($value, $parameters = [])
    {
        return \Context::getContext()->getTranslator()->trans(
            $value,
            $parameters,
            'Modules.FrSentry.Exceptions'
        );
    }
}
