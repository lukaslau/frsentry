<?php
/*
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

// Load the scoped Sentry SDK library
require_once __DIR__ . '/libs/sentry/autoload.php';

// Load dependencies: Symfony\OptionsResolver, Psr\Log, etc.
require_once __DIR__ . '/vendor/autoload.php';

use Frento\FrSentry\src\Prestashop\FrConfiguration;
use Frento\FrSentry\src\Prestashop\Hooks\FrontHook;

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
     * Capture exception manually from external code.
     *
     * Usage: Module::getInstanceByName('frsentry')->captureException(new Exception('MESSAGE'), ['type' => 'PHP'])
     *
     * @param Throwable $exception
     * @param array $tags
     *
     * @return void
     */
    public function captureException($exception, $tags = [])
    {
        Frento\FrSentry\src\Libs\FrSentry::capture($exception, $tags);
    }

    public function install()
    {
        return parent::install()
            && FrontHook::registerHooks($this);
    }

    public function hookModuleRoutes(): array
    {
        return FrontHook::handleModuleRoutes();
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        FrontHook::handleSetMedia();
    }

    public function uninstall()
    {
        $prefix = FrConfiguration::$configPrefix;

        Configuration::deleteByName($prefix . 'BACKEND_KEY');
        Configuration::deleteByName($prefix . 'FRONTEND_KEY');

        foreach (FrConfiguration::$booleanKeys as $key) {
            Configuration::deleteByName($prefix . $key);
        }

        foreach (FrConfiguration::$rateKeys as $key) {
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
                die(json_encode(['success' => false, 'error' => 'No DSN provided']));
            }
            try {
                // Collect transport-level error messages so we can return a
                // meaningful error to the admin instead of a generic "failed".
                $transportErrors = [];
                $transportLogger = new class($transportErrors) extends Psr\Log\AbstractLogger {
                    private $errors;

                    public function __construct(array &$errors)
                    {
                        $this->errors = &$errors;
                    }

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
                    die(json_encode(['success' => true, 'eventId' => (string) $eventId]));
                }

                $error = !empty($transportErrors)
                    ? implode(' | ', $transportErrors)
                    : 'Event was not accepted by Sentry (check DSN and project settings)';

                die(json_encode(['success' => false, 'error' => $error]));
            } catch (Throwable $e) {
                die(json_encode(['success' => false, 'error' => $e->getMessage()]));
            }
        }

        // Load the admin JS that manages form show/hide and test buttons.
        $this->context->controller->addJS($this->_path . 'views/js/admin/frsentry-config.js');

        $output = '';

        if (Tools::isSubmit('submit_frsentry')) {
            $prefix = FrConfiguration::$configPrefix;

            Configuration::updateValue(
                $prefix . 'BACKEND_KEY',
                trim(Tools::getValue($prefix . 'BACKEND_KEY'))
            );
            Configuration::updateValue(
                $prefix . 'FRONTEND_KEY',
                trim(Tools::getValue($prefix . 'FRONTEND_KEY'))
            );

            foreach (FrConfiguration::$booleanKeys as $key) {
                Configuration::updateValue(
                    $prefix . $key,
                    (int) Tools::getValue($prefix . $key)
                );
            }

            // Sampling rates: clamp to 0–100 before saving.
            foreach (FrConfiguration::$rateKeys as $key) {
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

        $helper->fields_value = [
            $prefix . 'BACKEND_KEY' => $config['backendKey'],
            $prefix . 'FRONTEND_KEY' => $config['frontendKey'],
            $prefix . 'PHP_IGNORE_USER' => (int) $config['backend']['phpIgnoreUser'],
            $prefix . 'PHP_IGNORE_DEPRECATED' => (int) $config['backend']['phpIgnoreDeprecated'],
            $prefix . 'PHP_IGNORE_WARNING' => (int) $config['backend']['phpIgnoreWarning'],
            $prefix . 'PHP_IGNORE_NOTICED' => (int) $config['backend']['phpIgnoreNoticed'],
            $prefix . 'USE_BACKOFFICE' => (int) $config['backend']['useBackoffice'],
            $prefix . 'INSIGHTS_FRONTEND' => (int) $config['backend']['insightsFrontend'],
            $prefix . 'PROFILING_FRONTEND' => (int) $config['backend']['profilingFrontend'],
            $prefix . 'FRONTEND_TRACING_RATE' => $config['backend']['frontendTracingRate'],
            $prefix . 'FRONTEND_PROFILING_RATE' => $config['backend']['frontendProfilingRate'],
            $prefix . 'BACKEND_TRACING' => (int) $config['backend']['tracingEnabled'],
            $prefix . 'BACKEND_TRACING_RATE' => $config['backend']['tracingRate'],
            $prefix . 'BACKEND_PROFILING' => (int) $config['backend']['profilingEnabled'],
            $prefix . 'BACKEND_PROFILING_RATE' => $config['backend']['profilingRate'],
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
                            'name' => $prefix . 'BACKEND_KEY',
                            'size' => 90,
                            'required' => false,
                            'desc' => $this->l('Sentry DSN for server-side (PHP) error monitoring.'),
                        ],
                        $this->renderTestButton('backend', $adminUrl),
                        [
                            'type' => 'text',
                            'label' => $this->l('Frontend DSN Key'),
                            'name' => $prefix . 'FRONTEND_KEY',
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
                    'legend' => ['title' => $this->l('Backend Settings'), 'icon' => 'icon-cogs'],
                    'input' => [
                        [
                            'type' => 'switch',
                            'label' => $this->l('Monitor back office'),
                            'name' => $prefix . 'USE_BACKOFFICE',
                            'desc' => $this->l('Enable Sentry error monitoring in the PrestaShop back office.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Ignore user errors'),
                            'name' => $prefix . 'PHP_IGNORE_USER',
                            'desc' => $this->l('Ignore E_USER_ERROR, E_USER_WARNING and E_USER_NOTICE errors.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Ignore deprecated'),
                            'name' => $prefix . 'PHP_IGNORE_DEPRECATED',
                            'desc' => $this->l('Ignore E_DEPRECATED and E_USER_DEPRECATED errors.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Ignore warnings'),
                            'name' => $prefix . 'PHP_IGNORE_WARNING',
                            'desc' => $this->l('Ignore E_WARNING errors.'),
                            'values' => $yesNoOptions,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Ignore notices'),
                            'name' => $prefix . 'PHP_IGNORE_NOTICED',
                            'desc' => $this->l('Ignore E_NOTICE errors.'),
                            'values' => $yesNoOptions,
                        ],
                    ],
                    'submit' => ['title' => $this->l('Save')],
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => $this->l('Frontend Settings'), 'icon' => 'icon-desktop'],
                    'input' => [
                        [
                            'type' => 'switch',
                            'label' => $this->l('Performance insights (frontend)'),
                            'name' => $prefix . 'INSIGHTS_FRONTEND',
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
                            'name' => $prefix . 'PROFILING_FRONTEND',
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
        $html = '<div class="form-group frsentry-test-row" data-target="' . $target . '"
                      style="display:none;">
            <div class="col-lg-9 col-lg-offset-3" style="display:flex;align-items:center;gap:12px;">
                <button type="button"
                        class="btn btn-default frsentry-test-btn"
                        data-target="' . $target . '"
                        data-url="' . htmlspecialchars($adminUrl) . '">
                    <i class="icon-paper-plane"></i>&nbsp;' . $this->l('Send test event') . '
                </button>
                <span class="frsentry-test-result"></span>
            </div>
        </div>';

        return [
            'type' => 'html',
            'name' => 'frsentry_test_btn_' . $target,
            'html_content' => $html,
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
                'label' => $this->l('Enable tracing'),
                'name' => $prefix . 'BACKEND_TRACING',
                'desc' => $this->l('Records a Sentry transaction for every sampled request. Shows request duration, controller, and child spans in the Sentry Performance tab. Requires a valid Backend DSN key.'),
                'values' => $yesNoOptions,
            ],
            [
                'type' => 'text',
                'label' => $this->l('Transaction sampling rate (%)'),
                'name' => $prefix . 'BACKEND_TRACING_RATE',
                'class' => 'fixed-width-sm',
                'suffix' => '%',
                'required' => false,
                'desc' => $this->l('Percentage of requests that create a Sentry transaction. 100 = every request; 10 = one in ten. Lower values reduce event volume and cost.'),
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

        $inputs[] = [
            'type' => 'html',
            'name' => 'frsentry_excimer_status',
            'html_content' => '<div class="form-group">
                <label class="control-label col-lg-3">' . $this->l('Profiling (excimer)') . '</label>
                <div class="col-lg-9">
                    <span class="label ' . $badgeClass . '" style="font-size:13px;padding:5px 10px;">'
                        . htmlspecialchars($badgeText) .
                    '</span>
                </div>
            </div>',
        ];

        if ($excimerLoaded) {
            // ── Profiling fields — only when excimer is loaded ────────────────
            $inputs[] = [
                'type' => 'switch',
                'label' => $this->l('Enable profiling'),
                'name' => $prefix . 'BACKEND_PROFILING',
                'desc' => $this->l('Attaches an excimer flame graph to each sampled transaction. Shows exactly which PHP functions consumed time during the request. Requires tracing to be enabled.'),
                'values' => $yesNoOptions,
            ];
            $inputs[] = [
                'type' => 'text',
                'label' => $this->l('Profile sampling rate (%)'),
                'name' => $prefix . 'BACKEND_PROFILING_RATE',
                'class' => 'fixed-width-sm',
                'suffix' => '%',
                'required' => false,
                'desc' => $this->l('Percentage of traced requests that will also include a flame graph profile. Applied relative to the transaction sampling rate above. 100 = profile every traced request.'),
            ];
        } else {
            $inputs[] = [
                'type' => 'html',
                'name' => 'frsentry_profiling_note',
                'html_content' => '<div class="form-group"><div class="col-lg-9 col-lg-offset-3">'
                    . '<p class="help-block">' . $this->l('Install the excimer PHP extension to enable flame graph profiling for backend requests (apt install php-excimer).') . '</p>'
                    . '</div></div>',
            ];
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Backend Settings'),
                    'icon' => 'icon-dashboard',
                ],
                'input' => $inputs,
                'submit' => ['title' => $this->l('Save')],
            ],
        ];
    }
}
