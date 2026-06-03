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

namespace Frento\FrSentry\Core;

if (!defined('_PS_VERSION_')) {
    exit;
}

use FrSentry\Sentry\Integration\ErrorListenerIntegration;
use FrSentry\Sentry\Integration\ExceptionListenerIntegration;
use FrSentry\Sentry\Integration\FatalErrorListenerIntegration;
use FrSentry\Sentry\Integration\ModulesIntegration;
use FrSentry\Sentry\Integration\RequestIntegration;

class SentryClient
{
    /**
     * @param array $config full module config array from FrConfiguration::getConfiguration()
     */
    public function __construct(array $config)
    {
        $backend = $config['backend'] ?? [];
        $dsn = $backend['dsn'] ?? '';
        $tracing = $backend['tracing'] ?? [];
        $profiling = $backend['profiling'] ?? [];

        // Tracing and profiling rates — computed here so SentryReporter::registerHandlers()
        // does not need to know SDK internals.
        //
        // Profiling requires both:
        //   (a) the excimer PHP extension to be actually loaded at runtime, AND
        //   (b) tracing to be enabled (profiles attach to transactions).
        // We re-check extension_loaded() here as a belt-and-suspenders guard: even
        // if the admin form could not be reached (excimer was loaded, user saved
        // "enabled", then excimer was removed), profiles_sample_rate stays 0.0 and
        // the SDK logs a silent warning instead of crashing.
        $tracingRate = 0.0;
        $profilingRate = 0.0;

        if (!empty($tracing['enabled'])) {
            $tracingRate = (int) ($tracing['sampleRate'] ?? 100) / 100;

            if (!empty($profiling['enabled']) && extension_loaded('excimer')) {
                $profilingRate = (int) ($profiling['sampleRate'] ?? 100) / 100;
            }
        }

        $options = [
            'dsn' => $dsn,
            'environment' => defined('_PS_MODE_DEV_') && _PS_MODE_DEV_ ? 'development' : 'production',
            'server_name' => $_SERVER['HTTP_HOST'] ?? gethostname(),
            'traces_sample_rate' => $tracingRate,
            'send_default_pii' => false,
            'max_breadcrumbs' => 50,
            'http_ssl_verify_peer' => false,
        ];

        // profiles_sample_rate is only registered as a valid SDK option when
        // the excimer extension is loaded — passing it on servers without
        // excimer throws an "option does not exist" exception.
        if (extension_loaded('excimer')) {
            $options['profiles_sample_rate'] = $profilingRate;
        }

        \FrSentry\Sentry\init($options + [
            // Disabled integrations:
            //   ErrorListenerIntegration / ExceptionListenerIntegration /
            //   FatalErrorListenerIntegration — FrSentry registers its own
            //   PrestaShop-aware handlers; duplicate registration would send
            //   every event twice.
            //   ModulesIntegration — attaches the full list of installed
            //   Composer packages to every event (noisy, large payload).
            'integrations' => function (array $integrations): array {
                $disabled = [
                    ErrorListenerIntegration::class,
                    ExceptionListenerIntegration::class,
                    FatalErrorListenerIntegration::class,
                    ModulesIntegration::class,
                    // RequestIntegration uses GuzzleHttp\Psr7\ServerRequest to build
                    // a PSR-7 request object. On sites with modules that bundle older
                    // php-http/message (e.g. psshipping, ps_mbo), their prepended
                    // autoloaders serve a FilteredStream whose seek(int, int) signature
                    // is incompatible with the untyped StreamInterface on PHP 8.4,
                    // causing a fatal error. We build the HTTP request block manually
                    // instead — see buildRequestData() / registerRequestProcessor().
                    RequestIntegration::class,
                ];

                return array_values(array_filter(
                    $integrations,
                    function ($integration) use ($disabled): bool {
                        return !in_array(get_class($integration), $disabled, true);
                    }
                ));
            },
        ]);

        self::registerRequestProcessor();
    }

    /**
     * Registers a one-time global Sentry event processor that attaches a
     * structured HTTP request block to every event.
     *
     * Uses a static guard so multiple SentryClient instances (e.g. the normal
     * capture path and the admin test button) never register the processor twice.
     */
    private static function registerRequestProcessor(): void
    {
        static $registered = false;

        if ($registered) {
            return;
        }

        $registered = true;

        \FrSentry\Sentry\State\Scope::addGlobalEventProcessor(
            // Must be a non-static closure so it retains no class binding —
            // Sentry calls it from its own scope. buildRequestData() is public
            // precisely because of this: a static closure has no class context.
            static function (\FrSentry\Sentry\Event $event): \FrSentry\Sentry\Event {
                if (PHP_SAPI === 'cli' || empty($_SERVER['REQUEST_METHOD'])) {
                    return $event;
                }

                $event->setRequest(SentryClient::buildRequestData());

                return $event;
            }
        );
    }

    /**
     * Builds the Sentry "request" payload from PHP superglobals.
     *
     * Mirrors what RequestIntegration does via PSR-7, but reads directly from
     * $_SERVER / $_POST / $_FILES — no GuzzleHttp\Psr7 classes are loaded,
     * so there is no autoloader conflict with psshipping / ps_mbo.
     *
     * Sensitive headers (Authorization, Cookie, X-Forwarded-For, X-Real-IP)
     * are replaced with [Filtered] — consistent with send_default_pii: false.
     *
     * @internal called only from the event processor registered above
     */
    public static function buildRequestData(): array
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $requestData = [
            'url' => $host ? $scheme . '://' . $host . $uri : $uri,
            'method' => $_SERVER['REQUEST_METHOD'],
        ];

        if (!empty($_SERVER['QUERY_STRING'])) {
            $requestData['query_string'] = $_SERVER['QUERY_STRING'];
        }

        // Collect and sanitise headers from $_SERVER HTTP_* keys.
        $sensitive = ['authorization', 'cookie', 'set-cookie', 'x-forwarded-for', 'x-real-ip'];
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strncmp($key, 'HTTP_', 5) !== 0) {
                continue;
            }

            // HTTP_ACCEPT_LANGUAGE → Accept-Language
            $name = str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_'));
            $headers[$name] = in_array(strtolower($name), $sensitive, true)
                ? ['[Filtered]']
                : [(string) $value];
        }

        // Content-Type and Content-Length have no HTTP_* prefix in $_SERVER.
        foreach (['CONTENT_TYPE' => 'Content-Type', 'CONTENT_LENGTH' => 'Content-Length'] as $server => $header) {
            if (!empty($_SERVER[$server])) {
                $headers[$header] = [(string) $_SERVER[$server]];
            }
        }

        if (!empty($headers)) {
            $requestData['headers'] = $headers;
        }

        // POST fields — or raw JSON body when Content-Type is application/json.
        if (!empty($_POST)) {
            $requestData['data'] = $_POST;
        } elseif (
            !empty($_SERVER['CONTENT_TYPE'])
            && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        ) {
            $raw = (string) file_get_contents('php://input');

            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                $requestData['data'] = $decoded !== null ? $decoded : $raw;
            }
        }

        // Uploaded file metadata (names, types, sizes — never file content).
        if (!empty($_FILES)) {
            $files = [];

            foreach ($_FILES as $field => $file) {
                $files[$field] = [
                    'client_filename' => $file['name'] ?? null,
                    'client_media_type' => $file['type'] ?? null,
                    'size' => $file['size'] ?? null,
                ];
            }

            $requestData['data'] = array_merge($requestData['data'] ?? [], $files);
        }

        return $requestData;
    }

    /**
     * Captures a throwable inside an isolated scope.
     *
     * Tags are set as Sentry tags.  Two keys receive special treatment:
     *   - 'sqlQuery'    → stored as a named Sentry context block so the full
     *                      query is visible without tag-length truncation.
     *   - 'customerId'  → stored as the Sentry user id alongside the visitor IP.
     *
     * @param \Throwable $exception
     * @param array $tags
     *
     * @return bool
     */
    public function capture(\Throwable $exception, array $tags = []): bool
    {
        $eventId = \FrSentry\Sentry\withScope(static function (\FrSentry\Sentry\State\Scope $scope) use ($exception, $tags) {
            // SQL queries go into a named context block, not a tag, so the
            // full query text is preserved without length truncation.
            if (!empty($tags['sqlQuery'])) {
                $scope->setContext('SQL Query', ['query' => $tags['sqlQuery']]);
                unset($tags['sqlQuery']);
            }

            // Build the Sentry user object.
            // Use the real IP — the PHP SDK validates via filter_var(FILTER_VALIDATE_IP)
            // so the {{auto}} token accepted by the JS SDK is rejected here.
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $user = filter_var($ip, FILTER_VALIDATE_IP) ? ['ip_address' => $ip] : [];

            if (!empty($tags['customerId'])) {
                $user['id'] = $tags['customerId'];
                unset($tags['customerId']);
            }

            if (!empty($tags['customerEmail'])) {
                $user['email'] = $tags['customerEmail'];
                unset($tags['customerEmail']);
            }

            $scope->setUser($user);

            // Everything else becomes a flat Sentry tag.
            foreach ($tags as $key => $value) {
                if ($value !== null && $value !== '') {
                    $scope->setTag((string) $key, (string) $value);
                }
            }

            return \FrSentry\Sentry\captureException($exception);
        });

        return $eventId !== null;
    }
}
