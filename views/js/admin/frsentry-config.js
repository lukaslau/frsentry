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

/**
 * FrSentry module — admin configuration form
 *
 * Manages conditional show/hide for two independent blocks:
 *
 * Backend performance:
 *   Tracing ON   → show Tracing rate + Profiling switch (if excimer loaded)
 *   Profiling ON → show Profiling rate
 *
 * Frontend:
 *   Insights ON  → show Frontend tracing rate + Profiling switch
 *   Profiling ON → show Frontend profiling rate
 *
 * When a parent switch is turned OFF its children are forced to "off" before
 * being hidden, so the saved form values are always internally consistent.
 *
 * Profiling fields are only present in the DOM when the excimer PHP extension
 * is loaded — JS simply skips them when the element is absent.
 *
 * Targets fields by [name] attribute + .closest('.form-group') so no extra
 * markup is injected into HelperForm's output.
 */
(function () {
    'use strict';

    var PREFIX = 'FRSENTRY_';

    // Backend performance fields
    var TRACING_FIELD   = PREFIX + 'BACKEND_TRACING';
    var TRACING_RATE    = PREFIX + 'BACKEND_TRACING_RATE';
    var PROFILING_FIELD = PREFIX + 'BACKEND_PROFILING';
    var PROFILING_RATE  = PREFIX + 'BACKEND_PROFILING_RATE';

    // Frontend fields
    var FE_INSIGHTS_FIELD  = PREFIX + 'INSIGHTS_FRONTEND';
    var FE_TRACING_RATE    = PREFIX + 'FRONTEND_TRACING_RATE';
    var FE_PROFILING_FIELD = PREFIX + 'PROFILING_FRONTEND';
    var FE_PROFILING_RATE  = PREFIX + 'FRONTEND_PROFILING_RATE';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns true when the "on" radio for a PS switch field is checked.
     */
    function isSwitchOn(fieldName) {
        var radio = document.getElementById(fieldName + '_on');
        return radio ? radio.checked : false;
    }

    /**
     * Forces a PS switch field to "off" without triggering a change event
     * (we handle cascading ourselves inside applyVisibility).
     */
    function forceOff(fieldName) {
        var offRadio = document.getElementById(fieldName + '_off');
        if (offRadio && !offRadio.checked) {
            offRadio.checked = true;
        }
    }

    /**
     * Shows or hides the .form-group wrapper of any input by field name.
     * Works for both switch (radio) and text inputs.
     */
    function setFieldVisible(fieldName, visible) {
        var el = document.querySelector('[name="' + fieldName + '"]');
        if (!el) {
            return;
        }
        var formGroup = el.closest('.form-group');
        if (formGroup) {
            formGroup.style.display = visible ? '' : 'none';
        }
    }

    // -------------------------------------------------------------------------
    // Core visibility logic
    // -------------------------------------------------------------------------

    function applyBackendVisibility() {
        var tracingOn   = isSwitchOn(TRACING_FIELD);
        var profilingOn = tracingOn && isSwitchOn(PROFILING_FIELD);

        // Force profiling off when tracing is disabled
        if (!tracingOn) {
            forceOff(PROFILING_FIELD);
        }

        setFieldVisible(TRACING_RATE,    tracingOn);
        // Profiling switch is only in the DOM when excimer is loaded;
        // setFieldVisible is a no-op when the element does not exist.
        setFieldVisible(PROFILING_FIELD, tracingOn);
        setFieldVisible(PROFILING_RATE,  profilingOn);
    }

    function applyFrontendVisibility() {
        var insightsOn  = isSwitchOn(FE_INSIGHTS_FIELD);
        var profilingOn = isSwitchOn(FE_PROFILING_FIELD);

        // Profiling requires insights — force it off when insights is disabled
        if (!insightsOn) {
            forceOff(FE_PROFILING_FIELD);
        }

        setFieldVisible(FE_TRACING_RATE,    insightsOn);
        setFieldVisible(FE_PROFILING_FIELD, insightsOn);
        setFieldVisible(FE_PROFILING_RATE,  insightsOn && profilingOn);
    }

    // -------------------------------------------------------------------------
    // Event binding
    // -------------------------------------------------------------------------

    function bindSwitch(fieldName, handler) {
        var radios = document.querySelectorAll('input[name="' + fieldName + '"]');
        for (var i = 0; i < radios.length; i++) {
            radios[i].addEventListener('change', handler);
        }
    }

    // -------------------------------------------------------------------------
    // Test buttons
    // -------------------------------------------------------------------------

    /**
     * Generates a UUID v4 for Sentry event_id.
     */
    function generateUuid() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID().replace(/-/g, '');
        }
        return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    /**
     * Shows feedback next to the test button.
     */
    function showTestResult(resultEl, success, message) {
        resultEl.style.cssText = 'font-weight:600;color:' + (success ? '#3c763d' : '#a94442') + ';';
        resultEl.textContent = (success ? '✓ ' : '✗ ') + message;
    }

    /**
     * Parses a Sentry DSN string into its components.
     * Returns null when the format is unrecognised.
     */
    function parseDsn(dsn) {
        var m = dsn.match(/^https?:\/\/([^@]+)@([^/]+)\/(.+)$/);
        if (!m) {
            return null;
        }
        return { key: m[1], host: m[2], projectId: m[3] };
    }

    function testBackend(dsn, btn, resultEl) {
        var url = btn.getAttribute('data-url');

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'frsentry_action=test_backend&dsn=' + encodeURIComponent(dsn),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                showTestResult(resultEl, data.success, data.success ? 'Event sent' : (data.error || 'Failed'));
            })
            .catch(function () {
                showTestResult(resultEl, false, 'Request failed');
            })
            .finally(function () { btn.disabled = false; });
    }

    function testFrontend(dsn, btn, resultEl) {
        var parsed = parseDsn(dsn);
        if (!parsed) {
            showTestResult(resultEl, false, 'Invalid DSN format');
            btn.disabled = false;
            return;
        }

        var eventId = generateUuid();
        var now = new Date().toISOString();
        var envelope = [
            JSON.stringify({ event_id: eventId, sent_at: now }),
            JSON.stringify({ type: 'event', content_type: 'application/json' }),
            JSON.stringify({
                event_id: eventId,
                timestamp: now,
                platform: 'javascript',
                level: 'error',
                exception: {
                    values: [{
                        type: 'Error',
                        value: 'FrSentry frontend test event from configuration page',
                        mechanism: { type: 'generic', handled: true },
                    }],
                },
                tags: { type: 'test' },
            }),
        ].join('\n');

        fetch('https://' + parsed.host + '/api/' + parsed.projectId + '/envelope/', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-sentry-envelope',
                'X-Sentry-Auth': 'Sentry sentry_version=7, sentry_key=' + parsed.key,
            },
            body: envelope,
        })
            .then(function (r) {
                showTestResult(resultEl, r.ok, r.ok ? 'Event sent' : 'Sentry returned ' + r.status);
            })
            .catch(function () {
                showTestResult(resultEl, false, 'Request failed');
            })
            .finally(function () { btn.disabled = false; });
    }

    /**
     * Wires up the test button for one DSN field.
     * The button row is shown/hidden based on whether the DSN input is non-empty.
     */
    function initTestButton(target, dsnFieldName) {
        var dsnInput = document.querySelector('[name="' + dsnFieldName + '"]');
        var row = document.querySelector('.frsentry-test-row[data-target="' + target + '"]');
        var btn = row ? row.querySelector('.frsentry-test-btn') : null;
        var resultEl = row ? row.querySelector('.frsentry-test-result') : null;

        if (!dsnInput || !row || !btn) {
            return;
        }

        // Show the button row only when the DSN field already has a saved value
        // (evaluated once on page load — not live-watched).
        row.style.display = dsnInput.value.trim() ? '' : 'none';

        btn.addEventListener('click', function () {
            var dsn = dsnInput.value.trim();
            if (!dsn) {
                return;
            }
            btn.disabled = true;
            resultEl.textContent = '…';
            resultEl.style.cssText = 'color:#888;';

            if (target === 'backend') {
                testBackend(dsn, btn, resultEl);
            } else {
                testFrontend(dsn, btn, resultEl);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', function () {
        // Backend performance block — bind when tracing switch is present
        if (document.querySelector('[name="' + TRACING_FIELD + '"]')) {
            bindSwitch(TRACING_FIELD,   applyBackendVisibility);
            bindSwitch(PROFILING_FIELD, applyBackendVisibility);
            applyBackendVisibility();
        }

        // Frontend block — only bind when the insights switch is present
        if (document.querySelector('[name="' + FE_INSIGHTS_FIELD + '"]')) {
            bindSwitch(FE_INSIGHTS_FIELD,  applyFrontendVisibility);
            bindSwitch(FE_PROFILING_FIELD, applyFrontendVisibility);
            applyFrontendVisibility();
        }

        // Test buttons
        initTestButton('backend',  PREFIX + 'BACKEND_KEY');
        initTestButton('frontend', PREFIX + 'FRONTEND_KEY');
    });
}());
