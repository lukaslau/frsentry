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
        $config = Frento\FrSentry\FrConfiguration::getConfiguration();
        $frontend = $config['frontend'];

        if (empty($frontend['dsn'])) {
            return;
        }

        $data = [
            'frsentryApikey' => $frontend['dsn'],
            'insightsFrontend' => (int) ($frontend['insights'] ?? false),
            'profilingFrontend' => (int) ($frontend['profiling'] ?? false),
            'frontendTracingRate' => round((int) ($frontend['tracingRate'] ?? 20) / 100, 2),
            'frontendProfilingRate' => round((int) ($frontend['profilingRate'] ?? 20) / 100, 2),
            'ipAddress' => Tools::getRemoteAddr(),
            'shopUrl' => preg_quote((string) ($context->shop->domain ?? ''), '/'),
        ];

        if (!empty($context->customer->id)) {
            $data['trackUser'] = true;
            $data['userId'] = $context->customer->id;
            $data['email'] = $context->customer->email ?? '';
        }

        header('Content-Type: application/javascript');

        $this->context->smarty->assign($data);

        $script = (string) $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . '/frsentry/views/templates/front/sentry_init.tpl'
        );

        // Discard whatever PrestaShop may have buffered before this controller
        // ran, then emit only the script body with any HTML comments removed.
        if (ob_get_length() !== false) {
            ob_clean();
        }

        echo preg_replace('#<!--.*?-->#s', '', $script);
        exit;
    }
}
