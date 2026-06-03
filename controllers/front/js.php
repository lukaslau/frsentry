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
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Serves the per-shop Sentry initialisation config as a JavaScript snippet.
 *
 * The static SDK bundle (sentry.min.js) is registered separately in
 * FrontHook::hookActionFrontControllerSetMedia() and loaded before this script.
 *
 * Route: /module/frsentry/js?shop={id}
 */
class frsentryJsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $context = Context::getContext();
        $config = Frento\FrSentry\src\Prestashop\FrConfiguration::getConfiguration();

        if (empty($config['frontendKey'])) {
            return;
        }

        $data = [
            'frsentryApikey' => $config['frontendKey'],
            'insightsFrontend' => (int) ($config['backend']['insightsFrontend'] ?? false),
            'profilingFrontend' => (int) ($config['backend']['profilingFrontend'] ?? false),
            'frontendTracingRate' => round((int) ($config['backend']['frontendTracingRate'] ?? 20) / 100, 2),
            'frontendProfilingRate' => round((int) ($config['backend']['frontendProfilingRate'] ?? 20) / 100, 2),
            'ipAddress' => Tools::getRemoteAddr(),
            'shopUrl' => str_replace('.', '\.', $context->shop->domain ?? ''),
        ];

        if (!empty($context->customer->id)) {
            $data['trackUser'] = true;
            $data['userId'] = $context->customer->id;
            $data['email'] = $context->customer->email ?? '';
        }

        header('Content-Type: application/javascript');

        $this->context->smarty->assign($data);

        ob_clean();
        ob_start();
        echo $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . '/frsentry/views/templates/front/sentry_init.tpl'
        );
        $output = ob_get_clean();

        // Strip any stray HTML comments the template engine may inject
        echo preg_replace('/<!--(.|\s)*?-->/', '', (string) $output);
        exit;
    }
}
