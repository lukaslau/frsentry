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
 * Extends the core Db class to forward database exceptions to Sentry.
 */
abstract class Db extends DbCore
{
    /**
     * @param string $sql
     *
     * @return bool|mysqli_result|PDOStatement|resource
     *
     * @throws PrestaShopException
     */
    public function query($sql)
    {
        try {
            return parent::query($sql);
        } catch (PrestaShopException $exception) {
            $this->forwardException($exception, $sql);
            throw $exception;
        }
    }

    /**
     * Forwards a database exception to Sentry, including the offending query
     * as additional context. Deduplication (same SQL in one request) is handled
     * inside SentryReporter::capture() via a per-request SQL hash store.
     * Silently swallowed if reporting fails — the try/catch also covers the
     * case where the module is not installed and the FrSentry class does not exist.
     *
     * @param PrestaShopException $exception
     * @param string $sql
     *
     * @return void
     */
    private function forwardException(PrestaShopException $exception, string $sql): void
    {
        try {
            Frento\FrSentry\Core\SentryReporter::capture(
                $exception,
                ['type' => 'MYSQL', 'sqlQuery' => $sql]
            );
        } catch (Throwable $ignored) {
            // Never let the error reporter itself crash the application
        }
    }
}
