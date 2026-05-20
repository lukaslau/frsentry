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

use Teamwant\TeamwantSentry\src\Prestashop\TwConfiguration;

abstract class Db extends DbCore
{
    /**
     * @param $sql
     *
     * @throws PrestaShopDatabaseException
     *
     * @return bool|mysqli_result|PDOStatement|resource
     */
    public function query($sql)
    {
        try {
            $r = parent::query($sql);
            $this->logErrorByMonitor($sql);
        } catch (PrestaShopException $exception) {
            $this->logErrorByMonitor($exception->getMessage(), $exception->getCode(), $sql);
            throw new PrestaShopException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
        return $r;
    }

    /**
     * @param $sql
     */
    public function logErrorByMonitor($message = false, $code = 0, $sql = null)
    {
        try {
            if (empty(Context::getContext()->tw_sentry)) {
                if (!class_exists(TwConfiguration::class)) {
                    return false;
                } else {
                    Context::getContext()->tw_sentry = TwConfiguration::getConfiguration();
                }
            }
        } catch (Throwable $e) {
        }
        if (!empty(Context::getContext()->tw_sentry) && Context::getContext()->tw_sentry['backend_key']) {
            $errno = $code ? $code : $this->getNumberError();
            if ($errno) {
                try {
                    /*Teamwant\TeamwantSentry\src\Libs\TeamwantSentry::customCaptureException(new Exception(sprintf(
                        '[SQL Error] %s',
                        $this->getMsgError()
                    )), ['type' => 'MySQL']);*/
                    $tags = ['type' => 'MYSQL'];
                    if (!empty($sql))
                        $tags['sql_query'] = $sql;

                    Teamwant\TeamwantSentry\src\Libs\TeamwantSentry::customCaptureException(new Exception($message, $code), $tags);
                } catch (Throwable $e) {
                }
            }
        }
    }
}
