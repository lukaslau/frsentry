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
 * Extends PrestaShopException to forward unhandled exceptions to Sentry.
 *
 * PrestaShop's Dispatcher catches PrestaShopException and calls displayMessage()
 * rather than letting it propagate to set_exception_handler. This override
 * intercepts that path so these exceptions are still reported.
 *
 * Deduplication is handled inside SentryReporter::capture() — DB exceptions already
 * captured by the Db class override are silently skipped here via the per-request
 * sentErrors hash.
 */
class PrestaShopException extends PrestaShopExceptionCore
{
    public function displayMessage($dieAfterDisplay = true)
    {
        try {
            Frento\FrSentry\Core\SentryReporter::capture($this, ['type' => 'PrestaShopException']);
        } catch (Throwable $ignored) {
            // Never let the error reporter itself crash the application
        }

        parent::displayMessage($dieAfterDisplay);
    }
}
