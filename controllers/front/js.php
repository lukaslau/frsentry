<?php
/**
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
        $config = Frento\FrSentry\FrConfiguration::getConfiguration();
        $frontend = $config['frontend'];

        if (empty($frontend['dsn']) || empty($frontend['monitor'])) {
            return;
        }

        $denyUrlsList = [];
        if (!empty($frontend['denyUrls'])) {
            foreach (explode("\n", str_replace("\r", '', $frontend['denyUrls'])) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $denyUrlsList[] = $line;
                }
            }
        }

        $data = [
            'frsentryApikey' => $frontend['dsn'],
            'insightsFrontend' => (int) $frontend['insights'],
            'profilingFrontend' => (int) $frontend['profiling'],
            'frontendTracingRate' => round($frontend['tracingRate'] / 100, 2),
            'frontendProfilingRate' => round($frontend['profilingRate'] / 100, 2),
            'ipAddress' => Tools::getRemoteAddr(),
            'shopUrl' => preg_quote((string) ($this->context->shop->domain ?? ''), '/'),
            'denyUrlsJson' => json_encode($denyUrlsList, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG),
        ];

        if (!empty($this->context->customer->id)) {
            $data['trackUser'] = true;
            $data['userId'] = $this->context->customer->id;
        }

        // This response contains session-specific data (customer ID, IP).
        // Must never be stored by shared caches (CDN, Varnish, reverse proxies).
        header('Cache-Control: no-store, private');
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
