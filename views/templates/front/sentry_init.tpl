{**
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
 *
 * Sentry initialisation snippet -- injected after the static SDK bundle.
 * Variables assigned by frsentryJsModuleFrontController::initContent():
 *   $frsentryApikey         -- Sentry DSN key
 *   $insightsFrontend       -- 0|1 enable tracing integration
 *   $profilingFrontend      -- 0|1 enable profiling integration
 *   $frontendTracingRate    -- float 0.0–1.0 tracing sample rate
 *   $frontendProfilingRate  -- float 0.0–1.0 profiling sample rate
 *   $shopUrl                -- escaped shop domain regex fragment
 *   $ipAddress              -- visitor IP (for Sentry user context)
 *   $trackUser              -- bool, set when customer is logged in
 *   $userId                 -- customer ID (present only when $trackUser)
 *   $denyUrlsJson           -- JSON array of admin-configured denylist domains
 *}

{literal}
    Sentry.init({
        dsn: '{/literal}{$frsentryApikey|escape:'javascript':'UTF-8'}{literal}',
        ignoreErrors: [
            // Facebook in-app browser (FBAN/FBIOS) injects JS that calls
            // window.webkit.messageHandlers — not present in all WKWebView contexts.
            /window\.webkit\.messageHandlers/,
            // Other common Facebook/Instagram in-app browser injected noise.
            /processLargestContentfulPaintEvent/,
            /sendDataToNative/
        ],
        integrations: [{/literal}
            {if $insightsFrontend}
                Sentry.browserTracingIntegration({
                    traceFetch: false,
                    traceXHR: false
                }){if $profilingFrontend},{/if}
            {/if}
            {if $profilingFrontend}Sentry.browserProfilingIntegration(){/if}
        {literal}],
        tracesSampleRate:   {/literal}{$frontendTracingRate|floatval}{literal},
        profilesSampleRate: {/literal}{$frontendProfilingRate|floatval}{literal},
        beforeSend: function (event) {
            var exception = event.exception
                && event.exception.values
                && event.exception.values[0];
            if (exception
                && exception.stacktrace
                && exception.stacktrace.frames
                && exception.stacktrace.frames.length
            ) {
                var firstFrame = exception.stacktrace.frames[0];
                // Only filter when the filename is a real HTTP URL from another domain.
                // Frames with '<anonymous>', 'eval', or no filename are left through —
                // those originate from inline scripts or manual captures, not third parties.
                if (firstFrame && firstFrame.filename && /^https?:\/\//.test(firstFrame.filename)) {
                    var internalUrl = /^https?:\/\/((cdn|www)\.)?{/literal}{$shopUrl|escape:'javascript':'UTF-8'}{literal}/;
                    if (!internalUrl.test(firstFrame.filename)) {
                        return null;
                    }
                }
            }
            return event;
        },
        denyUrls: [
            /extensions\//i,
            /^chrome:\/\//i,
            /^moz-extension:\/\//i,
            /google-analytics\.com/i,
            /googletagmanager\.com/i,
            /facebook\.net/i,
            /connect\.facebook\.net/i,
            /doubleclick\.net/i
        ].concat({/literal}{$denyUrlsJson|nofilter}{literal})
    });
{/literal}

{if $trackUser}
{literal}Sentry.setUser({ ip_address: '{/literal}{$ipAddress|escape:'javascript':'UTF-8'}{literal}', id_user: '{/literal}{$userId|intval}{literal}' });{/literal}
{else}
{literal}Sentry.setUser({ ip_address: '{/literal}{$ipAddress|escape:'javascript':'UTF-8'}{literal}' });{/literal}
{/if}
