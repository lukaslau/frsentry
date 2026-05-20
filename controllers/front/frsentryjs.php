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
if (!defined('_PS_VERSION_')) {
    exit;
}

use Frento\FrSentry\src\Prestashop\TwConfiguration;

class frsentryfrsentryjsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $context = \Context::getContext();

        if (empty($context->tw_sentry)) {
            Context::getContext()->tw_sentry = TwConfiguration::getConfiguration();
        }

        if (empty($context->tw_sentry['frontend_key'])) {
            return;
        }

        if (!empty($context->customer->id))
        {
            $this->context->smarty->assign([
                'track_user' => true,
                'id_user' => $context->customer->id ?? 0,
                'email' => $context->customer->email ?? '',
            ]);
        }

        header('Content-Type: application/javascript');
        $this->context->smarty->assign([
            'frsentry_apikey' => $context->tw_sentry['frontend_key'],
            'insights_frontend' => (int)$context->tw_sentry['backend']['insights_frontend'],
            'profiling_frontend' => (int)$context->tw_sentry['backend']['profiling_frontend'],
            'ip_address' => \Tools::getRemoteAddr(),
            'shop_url' => str_replace('.', '\.', $context->shop->domain)
        ]);

        ob_clean();
        ob_start();
        echo $this->context->smarty->fetch(_PS_MODULE_DIR_ . '/frsentry/views/templates/front/js_apikey.tpl');
        $content = ob_get_contents();
        $content = preg_replace('/<!--(.|\s)*?-->/', '', $content);
        ob_end_clean();

        echo $content;
        exit;
    }
}
