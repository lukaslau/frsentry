<?php
/**
 * Sentry module for Prestashop
 * Version: 2.1.1
 * Copyright (c) 2023. Mateusz Szymański Frento
 * https://frentoit.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Frento <info@frentoit.com>
 * @copyright Copyright 2016-2025 © Frento Mateusz Szymański All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @category  Frento
 */
use Frento\FrSentry\src\AdminApi\Loader;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!function_exists('get_debug_type')) {
    function get_debug_type($string = '')
    {
        return $string;
    }
}

// prestashop autoloader was bugged with that module
require_once __DIR__ . '/vendor/autoload.php';

class FrSentry extends Module
{
    use Frento\FrSentry\src\Prestashop\Hooks\AdminHook;
    use Frento\FrSentry\src\Prestashop\Hooks\FrontHook;

    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'frsentry';
        $this->tab = 'front_office_features';
        $this->version = '2.1.2';
        $this->author = 'Mateusz Szymanski Frento';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Sentry Integration');
        $this->description = $this->l('Monitor bugs and check the availability of your store with sentry integration');

        $this->ps_versions_compliancy = ['min' => '1.7.7', 'max' => _PS_VERSION_];
        $this->module_key = 'bd6f21919c3ec947172864f8603168b2';
    }

    /**
     * for example:
     * Module::getInstanceByName('frsentry')->captureException(new Exception('MESSAGE'), ['type' => 'PHP'])
     *
     * @param [type] $exception
     * @return void
     */
    public function captureException($exception, $tags = []) {
        Frento\FrSentry\src\Libs\FrSentry::customCaptureException($exception, $tags);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        require_once __DIR__ . '/sql/install.php';

        return parent::install()
            && $this->registerAdminHooks()
            && $this->installAdminPrivilages()
            && $this->registerFrontendHooks()
        ;
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->renderTranslation();
        $this->loadAdminApiControllerRoutes();

        $this->context->smarty->assign([
            'moduleAdminLink' => Context::getContext()->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&module_name=' . $this->name,
            'iso_code' => $this->context->language->iso_code,
        ]);

        return $this->context->smarty->fetch('module:frsentry/views/templates/admin/configuration.tpl');
    }

    private function renderTranslation()
    {
        // action=getlang&lng=pl
        if (Tools::getValue('action') === 'getlang') {
            @ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'The form was saved correctly' => $this->l('The form was saved correctly'),
                'selected shop:' => $this->l('selected shop:'),
                'Backend key' => $this->l('Backend key'),
                'Frontend key' => $this->l('Frontend key'),
                'Ignore User error' => $this->l('Ignore User error'),
                'Ignore deprecated' => $this->l('Ignore deprecated'),
                'Ignore noticed' => $this->l('Ignore noticed'),
                'Use monitor in backoffice' => $this->l('Use monitor in backoffice'),
            ]);
            exit;
        }
    }

    private function loadAdminApiControllerRoutes()
    {
        if (Tools::getIsset('FrSentryAdminApiController')) {
            if (defined('_PS_ADMIN_DIR_')) {
                $loader = new Loader($this);
                $loader->run();
            }
        }
    }
}
