{**
 * @author    Frento IT <info@frentoit.com>
 * @copyright Copyright 2016-2025 © Frento IT All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
 *   $userId / $email        -- customer data (present only when $trackUser)
 *}

{literal}
    Sentry.init({
        dsn: '{/literal}{$frsentryApikey|escape:'javascript'}{literal}',
        integrations: [{/literal}
            {if $insightsFrontend}
                Sentry.browserTracingIntegration({
                    traceFetch: false,
                    traceXHR: false
                }){if $profilingFrontend},{/if}
            {/if}
            {if $profilingFrontend}Sentry.browserProfilingIntegration(){/if}
        {literal}],
        allowUrls: [/https?:\/\/((cdn|www)\.)?{/literal}{$shopUrl|escape:'javascript'}{literal}/],
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
                if (firstFrame && firstFrame.filename) {
                    var internalUrl = /^https?:\/\/((cdn|www)\.)?{/literal}{$shopUrl|escape:'javascript'}{literal}/;
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
        ]
    });
{/literal}

{if $trackUser}
{literal}Sentry.setUser({ ip_address: '{/literal}{$ipAddress|escape:'javascript'}{literal}', id: '{/literal}{$userId|intval}{literal}', email: '{/literal}{$email|escape:'javascript'}{literal}' });{/literal}
{else}
{literal}Sentry.setUser({ ip_address: '{/literal}{$ipAddress|escape:'javascript'}{literal}' });{/literal}
{/if}
