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

declare(strict_types=1);

namespace FrSentry\Sentry\Integration;

use FrSentry\Sentry\ErrorHandler;
use FrSentry\Sentry\Exception\FatalErrorException;
use FrSentry\Sentry\SentrySdk;

/**
 * This integration hooks into the error handler and captures fatal errors.
 *
 * @author Stefano Arlandini <sarlandini@alice.it>
 */
final class FatalErrorListenerIntegration extends AbstractErrorListenerIntegration
{
    /**
     * {@inheritdoc}
     */
    public function setupOnce(): void
    {
        $errorHandler = ErrorHandler::registerOnceFatalErrorHandler();
        $errorHandler->addFatalErrorHandlerListener(static function (FatalErrorException $exception): void {
            $currentHub = SentrySdk::getCurrentHub();
            $integration = $currentHub->getIntegration(self::class);
            $client = $currentHub->getClient();
            // The client bound to the current hub, if any, could not have this
            // integration enabled. If this is the case, bail out
            if ($integration === null || $client === null) {
                return;
            }
            if (!($client->getOptions()->getErrorTypes() & $exception->getSeverity())) {
                return;
            }
            $integration->captureException($currentHub, $exception);
        });
    }
}
