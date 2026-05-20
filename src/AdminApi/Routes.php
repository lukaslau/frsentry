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

use Frento\FrSentry\src\AdminApi\Controller\ConfigController;
use Frento\FrSentry\src\AdminApi\Controller\Controllers;
use Frento\FrSentry\src\AdminApi\Exception\ControllerActionNotExistsException;
use Frento\FrSentry\src\AdminApi\Exception\ControllerNotExistsException;
use Frento\FrSentry\src\AdminApi\Exception\ControllerNotUseControllerClassException;
use Frento\FrSentry\src\AdminApi\Exception\HTTPMethodIsInvalidException;
use Frento\FrSentry\src\AdminApi\Exception\RouteNotFoundException;

class Routes
{
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PURGE = 'PURGE';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_TRACE = 'TRACE';
    public const METHOD_CONNECT = 'CONNECT';

    /**
     * @var array
     */
    private $routeList;

    /**
     * @var Controllers
     */
    private $controller;
    /**
     * @var string
     */
    private $action;

    public function __construct()
    {
        $this->routeList = [
            'config/index' => [
                'name' => 'config_index',
                'type' => self::METHOD_GET,
                'class' => ConfigController::class,
                'action' => 'index',
            ],
            'config/index/save' => [
                'name' => 'config_index_save',
                'type' => self::METHOD_PATCH,
                'class' => ConfigController::class,
                'action' => 'indexSave',
            ],
        ];
    }

    public function get(string $route): self
    {
        $route = strtolower($route);

        if (empty($this->routeList[$route])) {
            throw new RouteNotFoundException();
        }

        if (!class_exists($this->routeList[$route]['class'])) {
            throw new ControllerNotExistsException();
        }

        $controller = new $this->routeList[$route]['class']();

        if (!($controller instanceof Controllers)) {
            throw new ControllerNotUseControllerClassException();
        }

        $action = 'action' . ucfirst($this->routeList[$route]['action']);

        if (!method_exists($controller, $action)) {
            throw new ControllerActionNotExistsException();
        }

        if ($this->routeList[$route]['type'] !== $_SERVER['REQUEST_METHOD']) {
            $method = Request::getRequestData('_method', '');

            if ($method) {
                if ($this->routeList[$route]['type'] !== $method) {
                    throw new HTTPMethodIsInvalidException();
                }
            } else {
                throw new HTTPMethodIsInvalidException();
            }
        }

        $this->controller = $controller;
        $this->action = $action;

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteList(): array
    {
        return $this->routeList;
    }

    /**
     * @return Controllers
     */
    public function getController(): Controllers
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }
}
