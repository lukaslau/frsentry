<?php
/**
 * Copyright (c) 2023-2026 Frento IT <info@frentoit.com>
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

require_once __DIR__ . '/vendor/autoload.php';

use Frento\FrSentry\FrConfiguration;
use Frento\FrSentry\Hooks\FrontHook;

class FrSentry extends Module
{
    public function __construct()
    {
        $this->name = 'frsentry';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Frento IT';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Sentry Integration For PrestaShop');
        $this->description = $this->l('Monitors your PrestaShop store for PHP errors, exceptions, database failures, and frontend JavaScript issues by forwarding them to Sentry in real time');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    /**
     * Reports an exception to Sentry from outside the module.
     *
     * Other modules, overrides, or custom scripts can forward a caught
     * throwable through this wrapper and attach arbitrary metadata as tags:
     *
     *   $sentry = Module::getInstanceByName('frsentry');
     *   $sentry->captureException($e, ['type' => 'payment', 'gateway' => 'stripe']);
     *
     * @param Throwable $exception
     * @param array $tags key/value metadata attached to the Sentry event
     *
     * @return void
     */
    public function captureException($exception, $tags = [])
    {
        Frento\FrSentry\Core\SentryReporter::capture($exception, $tags);
    }

    /**
     * Manually registers PHP error/exception handlers for contexts where PrestaShop
     * hooks do not fire automatically (e.g. CLI cron scripts that bootstrap PS via
     * config/config.inc.php without going through the dispatcher or without including init.php).
     *
     * Usage in a cron script:
     *   $sentry = Module::getInstanceByName('frsentry');
     *   if ($sentry) { $sentry->boot(); }
     */
    public function boot(): void
    {
        // Mark as a direct call so isMonitoringEnabled() uses the monitorCli
        // toggle rather than monitorFront — boot() is only for non-dispatch
        // contexts (CLI or HTTP direct call) where hooks never fired.
        Frento\FrSentry\Core\SentryReporter::markDirectCall();
        Frento\FrSentry\Core\SentryReporter::registerHandlers();
    }

    public function install()
    {
        return parent::install()
            && FrontHook::registerHooks($this);
    }

    public function hookModuleRoutes(): array
    {
        $this->passContextToReporter();

        return FrontHook::handleModuleRoutes();
    }

    public function hookActionFrontControllerInitBefore(): void
    {
        $this->passContextToReporter();
        FrontHook::handleFrontControllerInitBefore();
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        $this->passContextToReporter();
        FrontHook::handleSetMedia(
            $this->context->controller,
            $this->context->link,
            (int) $this->context->shop->id
        );
    }

    private function passContextToReporter(): void
    {
        Frento\FrSentry\Core\SentryReporter::setContextTags([
            'shopId' => $this->context->shop->id ?? null,
            'languageId' => $this->context->language->id ?? null,
            'controller' => $this->context->controller->php_self ?? null,
            'customerId' => $this->context->customer->id ?? null,
            'cartId' => $this->context->cart->id ?? null,
        ]);
    }

    public function uninstall()
    {
        $prefix = FrConfiguration::$configPrefix;

        $keys = array_merge(
            FrConfiguration::$dsnKeys,
            FrConfiguration::$textKeys,
            FrConfiguration::toggleKeys(),
            FrConfiguration::rateKeys()
        );

        foreach ($keys as $key) {
            Configuration::deleteByName($prefix . $key);
        }

        return parent::uninstall();
    }

    public function getContent()
    {
        // Handle AJAX test requests before any output.
        if (Tools::getValue('frsentry_action') === 'test_backend') {
            header('Content-Type: application/json');

            $dsn = trim((string) Tools::getValue('dsn'));
            if (empty($dsn)) {
                exit(json_encode(['success' => false, 'error' => 'No DSN provided']));
            }
            try {
                // Collect transport-level error messages so we can return a
                // meaningful error to the admin instead of a generic "failed".
                $transportLogger = new class() extends Psr\Log\AbstractLogger {
                    /** @var string[] */
                    public array $errors = [];

                    public function log($level, $message, array $context = []): void
                    {
                        if (in_array((string) $level, ['error', 'critical', 'alert', 'emergency'], true)) {
                            $this->errors[] = $message;
                        }
                    }
                };

                \FrSentry\Sentry\init([
                    'dsn' => $dsn,
                    'logger' => $transportLogger,
                    'integrations' => function (array $integrations): array {
                        return array_values(array_filter(
                            $integrations,
                            function ($integration): bool {
                                return !$integration instanceof FrSentry\Sentry\Integration\ModulesIntegration;
                            }
                        ));
                    },
                ]);

                $eventId = \FrSentry\Sentry\captureException(new Exception('FrSentry test'));

                if ($eventId !== null) {
                    exit(json_encode(['success' => true, 'eventId' => (string) $eventId]));
                }

                $error = !empty($transportLogger->errors)
                    ? implode(' | ', $transportLogger->errors)
                    : 'Event was not accepted by Sentry (check DSN and project settings)';

                exit(json_encode(['success' => false, 'error' => $error]));
            } catch (Throwable $e) {
                exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
            }
        }

        // Load the admin JS that manages form show/hide and test buttons.
        $this->context->controller->addJS($this->_path . 'views/js/admin/frsentry-config.js');

        $output = '';

        if (Tools::isSubmit('submit_frsentry')) {
            $prefix = FrConfiguration::$configPrefix;

            foreach (FrConfiguration::$dsnKeys as $key) {
                Configuration::updateValue(
                    $prefix . $key,
                    trim(Tools::getValue($prefix . $key))
                );
            }

            foreach (FrConfiguration::$textKeys as $key) {
                Configuration::updateValue(
                    $prefix . $key,
                    trim(Tools::getValue($prefix . $key))
                );
            }

            foreach (FrConfiguration::toggleKeys() as $key) {
                Configuration::updateValue(
                    $prefix . $key,
                    (int) Tools::getValue($prefix . $key)
                );
            }

            // Sampling rates: clamp to 0–100 before saving.
            foreach (FrConfiguration::rateKeys() as $key) {
                $rate = (int) Tools::getValue($prefix . $key);
                Configuration::updateValue($prefix . $key, max(0, min(100, $rate)));
            }

            FrConfiguration::clearCache();

            $output = $this->displayConfirmation($this->l('Settings saved successfully.'));
        }

        return $output . $this->renderForm();
    }

    private function renderForm()
    {
        $prefix = FrConfiguration::$configPrefix;
        $config = FrConfiguration::getConfiguration();

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->submit_action = 'submit_frsentry';

        $backend = $config['backend'];
        $frontend = $config['frontend'];

        $helper->fields_value = [
            $prefix . 'BACKEND_DSN' => $backend['dsn'],
            $prefix . 'FRONTEND_DSN' => $frontend['dsn'],
            $prefix . 'BACKEND_MONITOR_FRONT' => (int) $backend['monitorFront'],
            $prefix . 'BACKEND_MONITOR_ADMIN' => (int) $backend['monitorAdmin'],
            $prefix . 'BACKEND_MONITOR_CLI' => (int) $backend['monitorCli'],
            $prefix . 'BACKEND_TRACK_USER' => (int) $backend['track']['userErrors'],
            $prefix . 'BACKEND_TRACK_DEPRECATION' => (int) $backend['track']['deprecation'],
            $prefix . 'BACKEND_TRACK_WARNING' => (int) $backend['track']['warning'],
            $prefix . 'BACKEND_TRACK_NOTICE' => (int) $backend['track']['notice'],
            $prefix . 'BACKEND_TRACING_FRONT' => (int) $backend['tracing']['front'],
            $prefix . 'BACKEND_TRACING_ADMIN' => (int) $backend['tracing']['admin'],
            $prefix . 'BACKEND_TRACING_RATE' => $backend['tracing']['sampleRate'],
            $prefix . 'BACKEND_PROFILING_FRONT' => (int) $backend['profiling']['front'],
            $prefix . 'BACKEND_PROFILING_ADMIN' => (int) $backend['profiling']['admin'],
            $prefix . 'BACKEND_PROFILING_RATE' => $backend['profiling']['sampleRate'],
            $prefix . 'FRONTEND_DENY_URLS' => $frontend['denyUrls'],
            $prefix . 'FRONTEND_MONITOR' => (int) $frontend['monitor'],
            $prefix . 'FRONTEND_INSIGHTS' => (int) $frontend['insights'],
            $prefix . 'FRONTEND_TRACING_RATE' => $frontend['tracingRate'],
            $prefix . 'FRONTEND_PROFILING' => (int) $frontend['profiling'],
            $prefix . 'FRONTEND_PROFILING_RATE' => $frontend['profilingRate'],
        ];

        $yesNoOptions = [
            ['id' => 'on',  'value' => 1, 'label' => $this->l('Yes')],
            ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
        ];

        $adminUrl = $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name;

        $fieldsForm = [
            [
                'form' => [
                    'legend' => ['title' => $this->l('API Keys'), 'icon' => 'icon-key'],
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->l('Backend DSN Key'),
                            'name' => $prefix . 'BACKEND_DSN',
                            'size' => 90,
                            'required' => false,
                            'desc' => $this->l('Sentry DSN for server-side (PHP) error monitoring.'),
                        ],
                        $this->renderTestButton('backend', $adminUrl),
                        [
                            'type' => 'text',
                            'label' => $this->l('Frontend DSN Key'),
                            'name' => $prefix . 'FRONTEND_DSN',
                            'size' => 90,
                            'required' => false,
                            'desc' => $this->l('Sentry DSN for client-side (JavaScript) error monitoring.'),
                        ],
                        $this->renderTestButton('frontend', $adminUrl),
                    ],
                    'submit' => ['title' => $this->l('Save')],
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => $this->l('Backend Settings (PHP)'), 'icon' => 'icon-cogs'],
                    'input' => [
                        [
                            'type' => 'switch',
                            'label' => $this->l('Monitor Front Office'),
                            'name' => $prefix . 'BACKEND_MONITOR_FRONT',
                            'desc' => $this->l('Enable Sentry PHP error monitoring in the storefront.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Monitor Back Office'),
                            'name' => $prefix . 'BACKEND_MONITOR_ADMIN',
                            'desc' => $this->l('Enable Sentry error monitoring in the PrestaShop back office.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Monitor CLI / Cron Scripts'),
                            'name' => $prefix . 'BACKEND_MONITOR_CLI',
                            'desc' => $this->l('Capture errors in CLI and HTTP cron scripts that call PrestaShop directly. Requires init.php to be loaded. If the script loads init.php, Sentry initializes automatically. If not, call $module->boot() after loading config.inc.php.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'html',
                            'name' => $prefix . 'SEPARATOR_TRACKING',
                            'html_content' => '<hr style="margin:10px 0">',
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Track user errors'),
                            'name' => $prefix . 'BACKEND_TRACK_USER',
                            'desc' => $this->l('Capture E_USER_ERROR, E_USER_WARNING and E_USER_NOTICE errors.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Track deprecations'),
                            'name' => $prefix . 'BACKEND_TRACK_DEPRECATION',
                            'desc' => $this->l('Capture E_DEPRECATED and E_USER_DEPRECATED errors.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Track warnings'),
                            'name' => $prefix . 'BACKEND_TRACK_WARNING',
                            'desc' => $this->l('Capture E_WARNING errors.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Track notices'),
                            'name' => $prefix . 'BACKEND_TRACK_NOTICE',
                            'desc' => $this->l('Capture E_NOTICE errors.'),
                            'values' => $yesNoOptions,
                        ],
                    ],
                    'submit' => ['title' => $this->l('Save')],
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => $this->l('Frontend Settings (JS)'), 'icon' => 'icon-desktop'],
                    'input' => [
                        [
                            'type' => 'switch',
                            'label' => $this->l('Monitor Front Office JavaScript'),
                            'name' => $prefix . 'FRONTEND_MONITOR',
                            'desc' => $this->l('Enable Sentry JavaScript error monitoring in the storefront. Turn this off to keep the Frontend DSN saved without loading the browser SDK.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'textarea',
                            'label' => $this->l('Domain denylist'),
                            'name' => $prefix . 'FRONTEND_DENY_URLS',
                            'required' => false,
                            'rows' => 5,
                            'cols' => 60,
                            'desc' => $this->l('Domains to exclude from Sentry JS error reporting. One domain per line (e.g. ads.example.com). Any error whose source URL contains a listed string will be silently dropped.'),
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Performance insights (frontend)'),
                            'name' => $prefix . 'FRONTEND_INSIGHTS',
                            'desc' => $this->l('Enable Sentry performance monitoring on the client side.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Tracing sample rate (%)'),
                            'name' => $prefix . 'FRONTEND_TRACING_RATE',
                            'class' => 'fixed-width-sm',
                            'suffix' => '%',
                            'required' => false,
                            'desc' => $this->l('Percentage of page loads that create a Sentry transaction. 100 = every page; 20 = one in five. Lower values reduce event volume and cost.'),
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Performance profiling (frontend)'),
                            'name' => $prefix . 'FRONTEND_PROFILING',
                            'desc' => $this->l('Enable Sentry JS profiling. Requires Document-Policy: js-profiling header.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Profiling sample rate (%)'),
                            'name' => $prefix . 'FRONTEND_PROFILING_RATE',
                            'class' => 'fixed-width-sm',
                            'suffix' => '%',
                            'required' => false,
                            'desc' => $this->l('Percentage of traced page loads that also include a JS profile. Applied relative to the tracing rate above. 100 = profile every traced page.'),
                        ],
                    ],
                    'submit' => ['title' => $this->l('Save')],
                ],
            ],
        ];

        $fieldsForm[] = $this->renderPerformanceBlock($prefix, $config, $yesNoOptions);

        return $helper->generateForm($fieldsForm);
    }

    /**
     * Renders an inline test-event button placed below a DSN input field.
     *
     * The button is hidden by default; JS shows it when the sibling DSN field
     * contains a non-empty value. Clicking it either:
     *   backend  → POSTs the DSN to the module's getContent() AJAX handler
     *   frontend → sends a test envelope directly to Sentry's HTTP API via fetch
     *
     * @param string $target 'backend' or 'frontend'
     * @param string $adminUrl URL of the module config page (for the backend AJAX call)
     *
     * @return array
     */
    private function renderTestButton(string $target, string $adminUrl): array
    {
        $this->context->smarty->assign([
            'target' => $target,
            'adminUrl' => $adminUrl,
            'label_send_test' => $this->l('Send test event'),
        ]);

        return [
            'type' => 'html',
            'name' => 'frsentry_test_btn_' . $target,
            'html_content' => $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . $this->name . '/views/templates/admin/test_button.tpl'
            ),
        ];
    }

    /**
     * Builds the "Backend Performance" form section.
     *
     * Tracing is available on any server — no extension required.
     * Profiling (excimer flame graphs) requires the excimer PHP extension;
     * the profiling fields are only rendered when the extension is loaded.
     *
     * JS in views/js/admin/frsentry-config.js handles conditional show/hide:
     *   Tracing ON  → show tracing rate + (if present) profiling switch
     *   Profiling ON → show profiling rate
     *
     * @param string $prefix
     * @param array $config
     * @param array $yesNoOptions
     *
     * @return array
     */
    private function renderPerformanceBlock(string $prefix, array $config, array $yesNoOptions): array
    {
        $excimerLoaded = extension_loaded('excimer');

        // ── Tracing (always available) ────────────────────────────────────────
        $inputs = [
            [
                'type' => 'switch',
                'label' => $this->l('Enable Front Office tracing'),
                'name' => $prefix . 'BACKEND_TRACING_FRONT',
                'desc' => $this->l('Records a Sentry transaction for every sampled storefront request.'),
                'values' => $yesNoOptions,
            ],
            [
                'type' => 'switch',
                'label' => $this->l('Enable Back Office tracing'),
                'name' => $prefix . 'BACKEND_TRACING_ADMIN',
                'desc' => $this->l('Records a Sentry transaction for every sampled admin panel request.'),
                'values' => $yesNoOptions,
            ],
            [
                'type' => 'text',
                'label' => $this->l('Transaction sampling rate (%)'),
                'name' => $prefix . 'BACKEND_TRACING_RATE',
                'class' => 'fixed-width-sm',
                'suffix' => '%',
                'required' => false,
                'desc' => $this->l('Shared rate for front and back office. Percentage of requests that create a Sentry transaction. 100 = every request; 10 = one in ten.'),
            ],
        ];

        // ── Profiling status badge (always visible) ───────────────────────────
        if ($excimerLoaded) {
            $badgeClass = 'label-success';
            $badgeText = $this->l('excimer extension: loaded');
        } else {
            $badgeClass = 'label-danger';
            $badgeText = $this->l('excimer extension: not loaded');
        }

        $this->context->smarty->assign([
            'badgeClass' => $badgeClass,
            'badgeText' => $badgeText,
            'label_profiling' => $this->l('Profiling (excimer)'),
        ]);
        $inputs[] = [
            'type' => 'html',
            'name' => 'frsentry_excimer_status',
            'html_content' => $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . $this->name . '/views/templates/admin/excimer_status.tpl'
            ),
        ];

        if ($excimerLoaded) {
            // ── Profiling fields — only when excimer is loaded ────────────────
            $inputs[] = [
                'type' => 'switch',
                'label' => $this->l('Enable Front Office profiling'),
                'name' => $prefix . 'BACKEND_PROFILING_FRONT',
                'desc' => $this->l('Attaches an excimer flame graph to each sampled storefront transaction. Requires front office tracing to be enabled.'),
                'values' => $yesNoOptions,
            ];
            $inputs[] = [
                'type' => 'switch',
                'label' => $this->l('Enable Back Office profiling'),
                'name' => $prefix . 'BACKEND_PROFILING_ADMIN',
                'desc' => $this->l('Attaches an excimer flame graph to each sampled admin panel transaction. Requires back office tracing to be enabled.'),
                'values' => $yesNoOptions,
            ];
            $inputs[] = [
                'type' => 'text',
                'label' => $this->l('Profile sampling rate (%)'),
                'name' => $prefix . 'BACKEND_PROFILING_RATE',
                'class' => 'fixed-width-sm',
                'suffix' => '%',
                'required' => false,
                'desc' => $this->l('Shared rate for front and back office. Percentage of traced requests that will also include a flame graph profile. 100 = profile every traced request.'),
            ];
        } else {
            $this->context->smarty->assign([
                'label_install_excimer' => $this->l('Install the excimer PHP extension to enable flame graph profiling for backend requests (apt install php-excimer).'),
            ]);
            $inputs[] = [
                'type' => 'html',
                'name' => 'frsentry_profiling_note',
                'html_content' => $this->context->smarty->fetch(
                    _PS_MODULE_DIR_ . $this->name . '/views/templates/admin/profiling_note.tpl'
                ),
            ];
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('PHP Profiling Settings'),
                    'icon' => 'icon-dashboard',
                ],
                'input' => $inputs,
                'submit' => ['title' => $this->l('Save')],
            ],
        ];
    }
}
