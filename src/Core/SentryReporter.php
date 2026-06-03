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

class SentryReporter
{
    /** @var SentryClient|null Lazily initialised per request */
    private static $client;

    /** @var bool Prevents registering handlers more than once */
    private static $handlersRegistered = false;

    /** @var \FrSentry\Sentry\Tracing\Transaction|null Active transaction for the current request */
    private static $transaction;

    /** @var array<string, true> Hashes of exceptions already sent this request */
    private static $sentErrors = [];

    /** @var array<string, true> MD5 hashes of SQL queries already reported this request */
    private static $capturedSqlHashes = [];

    /** @var array<string, true> MD5 hashes of DB exception messages already reported this request */
    private static $capturedSqlMsgHashes = [];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Captures a throwable and forwards it to Sentry.
     *
     * Errors with the same signature raised more than once during a single
     * request are sent only once — see the per-request hash stores declared
     * above. For manual reporting, prefer the module's captureException()
     * wrapper rather than calling this internal method directly.
     *
     * @param \Throwable $exception
     * @param array $tags extra metadata (key → value) attached to the Sentry event
     */
    public static function capture(\Throwable $exception, array $tags = []): void
    {
        $config = self::config();

        if (empty($config['backend']['dsn'])) {
            return;
        }

        // SQL-specific dedup: the Db override passes sqlQuery in tags on first
        // capture. Store both the SQL hash and the exception message hash so
        // that when the same exception is re-thrown and reaches the global
        // handler (without sqlQuery), we can match it by message and skip.
        if (!empty($tags['sqlQuery'])) {
            $sqlHash = md5($tags['sqlQuery']);

            if (isset(self::$capturedSqlHashes[$sqlHash])) {
                return;
            }

            self::$capturedSqlHashes[$sqlHash] = true;
            self::$capturedSqlMsgHashes[md5($exception->getMessage())] = true;
        } elseif (stripos($exception->getMessage(), 'SQLSTATE[') !== false) {
            // DB exception arriving without sqlQuery — re-thrown after the Db
            // override already captured it. Skip only when the message hash
            // matches a query we actually sent; if the DB override is not
            // installed the hash won't be present and the event falls through.
            if (isset(self::$capturedSqlMsgHashes[md5($exception->getMessage())])) {
                return;
            }
        }

        $hash = md5($exception->getMessage() . $exception->getCode() . $exception->getFile() . $exception->getLine());

        if (isset(self::$sentErrors[$hash])) {
            return;
        }

        self::$sentErrors[$hash] = true;

        if (stripos($exception->getMessage(), 'SQLSTATE[') !== false) {
            $tags['type'] = $tags['type'] ?? 'MYSQL';
        }

        $tags = array_merge(self::buildTags(), $tags);

        self::client()->capture($exception, $tags);
    }

    // -------------------------------------------------------------------------
    // PHP error / exception handlers (registered via set_error_handler etc.)
    // -------------------------------------------------------------------------

    public static function onShutdown(): void
    {
        // Finish the active transaction first so it is included in the SDK's
        // final flush, even when a fatal error terminates the request.
        if (self::$transaction !== null) {
            try {
                self::$transaction->finish();
            } catch (\Throwable $ignored) {
            }
            self::$transaction = null;
        }

        $error = error_get_last();

        if (!$error || !$error['type']) {
            return;
        }

        if (self::classifyError($error['type']) === null) {
            return;
        }

        self::onError($error['type'], $error['message'], $error['file'], $error['line']);
    }

    /**
     * @param int $errno
     * @param string $message
     * @param string $file
     * @param int $line
     *
     * @return bool always false — lets PHP continue its own error handling
     */
    public static function onError(int $errno, string $message, string $file, int $line): bool
    {
        if (self::classifyError($errno) === null) {
            return false;
        }

        // SQLSTATE errors from PDO (E_WARNING triggered by DbPDOCore::_query)
        // are already captured with richer context — the full SQL query — by
        // the Db class override. Skip the raw warning here to avoid duplicates.
        if (stripos($message, 'SQLSTATE[') !== false) {
            return false;
        }

        try {
            self::capture(new \ErrorException($message, 0, $errno, $file, $line), ['type' => 'PHP']);
        } catch (\Throwable $ignored) {
            // Never let the error handler itself throw
        }

        return false;
    }

    public static function onException(\Throwable $exception): void
    {
        if (self::isMonitoringEnabled()) {
            self::capture($exception, ['type' => 'PHP']);
        }
    }

    // -------------------------------------------------------------------------
    // Handler registration (called once per request)
    // -------------------------------------------------------------------------

    public static function registerHandlers(): void
    {
        if (self::$handlersRegistered) {
            return;
        }

        self::$handlersRegistered = true;

        // Initialise the SDK immediately so its integrations (breadcrumbs,
        // request context, etc.) start collecting from this point forward,
        // rather than only from the moment the first error is captured.
        $config = self::config();
        if (!empty($config['backend']['dsn'])) {
            self::client();

            // Start a transaction when tracing is configured.
            // The transaction is stored in self::$transaction and finished in
            // onShutdown() — this ensures it wraps the entire request lifetime
            // and is flushed even on fatal errors.
            if (!empty($config['backend']['tracing']['enabled'])) {
                self::$transaction = self::startTransaction();
            }
        }

        register_shutdown_function([self::class, 'onShutdown']);
        set_error_handler([self::class, 'onError']);

        $previous = set_exception_handler(null);
        $handlers = array_filter([[self::class, 'onException'], $previous]);

        set_exception_handler(static function (\Throwable $exception) use ($handlers): void {
            foreach ($handlers as $handler) {
                $handler($exception);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private static function client(): SentryClient
    {
        if (self::$client === null) {
            self::$client = new SentryClient(self::config());
        }

        return self::$client;
    }

    /**
     * Starts a Sentry transaction covering the current HTTP request.
     *
     * The transaction is required for both tracing and excimer-based profiling —
     * profiles attach to transactions automatically when profiles_sample_rate > 0
     * and the excimer extension is loaded.
     *
     * Returns null (and swallows the error) if the SDK is not ready or the
     * transaction context cannot be built, so a misconfiguration here never
     * breaks the actual page request.
     */
    private static function startTransaction(): ?\FrSentry\Sentry\Tracing\Transaction
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';

            $context = new \FrSentry\Sentry\Tracing\TransactionContext();
            $context->setName($method . ' ' . $uri);
            $context->setOp('http.server');
            $context->setStartTimestamp(microtime(true));

            $transaction = \FrSentry\Sentry\startTransaction($context);

            // Register as the active span so child spans (e.g. from future
            // instrumentation) attach to this transaction automatically.
            \FrSentry\Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

            return $transaction;
        } catch (\Throwable $ignored) {
            return null;
        }
    }

    private static function config(): array
    {
        return \Frento\FrSentry\FrConfiguration::getConfiguration();
    }

    private static function isMonitoringEnabled(): bool
    {
        // CLI (cron jobs, test scripts) is treated as front-office context —
        // _PS_ADMIN_DIR_ is always defined after PS bootstrap regardless of
        // whether we are actually inside the admin panel.
        if (PHP_SAPI === 'cli') {
            return true;
        }

        $config = self::config();

        if (defined('_PS_ADMIN_DIR_') && empty($config['backend']['monitorAdmin'])) {
            return false;
        }

        return true;
    }

    /**
     * Collects runtime context tags attached to every Sentry event.
     */
    private static function buildTags(): array
    {
        $context = \Context::getContext();
        $tags = [
            'phpVersion' => PHP_VERSION,
            'psVersion' => defined('_PS_VERSION_') ? _PS_VERSION_ : null,
            'shopId' => $context->shop->id ?? null,
            'languageId' => $context->language->id ?? null,
        ];

        if (!empty($context->controller->php_self)) {
            $tags['controller'] = $context->controller->php_self;
        }

        if (!empty($context->customer->id)) {
            $tags['customerId'] = $context->customer->id;
            $tags['customerEmail'] = $context->customer->email ?? null;
        }

        if (!empty($context->cart->id)) {
            $tags['cartId'] = $context->cart->id;

            // Resolve order ID when the cart has already been converted
            // (e.g. on the order-confirmation page or during post-payment hooks).
            $orderId = (int) \Order::getIdByCartId($context->cart->id);
            if ($orderId > 0) {
                $tags['orderId'] = $orderId;
            }
        }

        return array_filter($tags);
    }

    /**
     * Returns the label for a PHP error constant, or null when the current
     * settings do not opt in to capturing it.
     *
     * Capture follows an allow-list ("track") model. A baseline set of hard
     * errors — fatals, parse/compile failures and engine/compiler warnings — is
     * always captured because those indicate broken code regardless of config.
     * The softer categories are added to the allowed set only when their
     * matching TRACK_* switch is enabled.
     *
     * E_USER_NOTICE is captured when EITHER "track notices" OR "track user
     * errors" is on; E_USER_DEPRECATED likewise reacts to "track deprecations"
     * OR "track user errors" — hence those constants appear in two groups.
     */
    private static function classifyError(int $errno): ?string
    {
        static $labels = [
            E_ERROR => 'E_ERROR',
            E_PARSE => 'E_PARSE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_STRICT => 'E_STRICT',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_WARNING => 'E_WARNING',
            E_NOTICE => 'E_NOTICE',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];

        if (!isset($labels[$errno]) || !self::isMonitoringEnabled()) {
            return null;
        }

        $track = self::config()['backend']['track'] ?? [];

        // Hard errors are always captured; engine/compiler warnings too.
        static $alwaysCaptured = [
            E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR,
            E_RECOVERABLE_ERROR, E_STRICT, E_CORE_WARNING, E_COMPILE_WARNING,
        ];

        $captured = array_merge(
            $alwaysCaptured,
            !empty($track['warning']) ? [E_WARNING] : [],
            !empty($track['notice']) ? [E_NOTICE, E_USER_NOTICE] : [],
            !empty($track['deprecation']) ? [E_DEPRECATED, E_USER_DEPRECATED] : [],
            !empty($track['userErrors']) ? [E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED] : []
        );

        return in_array($errno, $captured, true) ? $labels[$errno] : null;
    }
}
