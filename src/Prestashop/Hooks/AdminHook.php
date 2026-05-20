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

namespace Frento\FrSentry\src\Prestashop\Hooks;

if (!defined('_PS_VERSION_')) {
    exit;
}

trait AdminHook
{
    public function registerAdminHooks()
    {
        return
            $this->registerHook('displayBackOfficeHeader');
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->loadAsset();
    }

    /**
     * load dependencies
     */
    private function loadAsset()
    {
        // Media::addJsDef([
        // ]);

        if (in_array(\Tools::getValue('configure'), ['frsentry'])) {
            if (defined('IS_FRSENTRY_DEV_ENVIROMENT') && IS_FRSENTRY_DEV_ENVIROMENT === true) {
                $this->context->controller->addJS(
                    FRSENTRY . '/static/js/bundle.js'
                );
            } else {
                $this->context->controller->addJS($this->_path . 'views/js/frsentry.js');
                $this->context->controller->addCss($this->_path . 'views/css/frsentry.css');
            }
        }
    }

    public function installAdminPrivilages($class_name = 'ROLE_MOD_MODULE_FRSENTRY')
    {
        $loopData = ['_READ', '_CREATE', '_UPDATE', '_DELETE'];

        foreach ($loopData as $prv_prefix) {
            $check = \Db::getInstance()->getRow(
                (new \DbQuery())
                    ->from('authorization_role')
                    ->where(sprintf('slug = "%s"', pSQL($class_name . $prv_prefix)))
            );

            if (!$check) {
                \Db::getInstance()->insert(
                    'authorization_role',
                    [
                        'slug' => pSQL($class_name . $prv_prefix),
                    ]
                );

                $check = \Db::getInstance()->getRow(
                    (new \DbQuery())
                        ->from('authorization_role')
                        ->where(sprintf('slug = "%s"', pSQL($class_name . $prv_prefix)))
                );
            }

            if ($check['id_authorization_role']) {
                $check2 = \Db::getInstance()->getRow(
                    (new \DbQuery())
                        ->from('access')
                        ->where(implode(' and ', [
                            'id_profile = ' . 1,
                            'id_authorization_role = ' . pSQL($check['id_authorization_role']),
                        ]))
                );

                if (!$check2) {
                    \Db::getInstance()->insert(
                        'access',
                        [
                            'id_profile' => 1,
                            'id_authorization_role' => pSQL($check['id_authorization_role']),
                        ]
                    );
                }
            }
        }

        return true;
    }
}
