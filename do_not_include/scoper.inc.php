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

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

/**
 * PHP-Scoper configuration.
 *
 * Prefixes all Sentry SDK namespaces (and their dependencies) with
 * FrSentry\ so they are fully isolated from ps_mbo's sentry/sentry
 * and any other module that ships its own copy of the SDK.
 *
 * Output goes to libs/sentry/ — the module ships that directory,
 * not the development vendor/.
 *
 * Usage (from project root):
 *   php do_not_include/php-scoper.phar add-prefix --config=do_not_include/scoper.inc.php --force
 *   cd libs/sentry && composer dump-autoload --no-scripts --optimize
 */
return [
    'prefix' => 'FrSentry',

    'output-dir' => __DIR__ . '/../libs/sentry',

    'finders' => [
        // Scope ONLY the Sentry SDK source files.
        // Dependencies (Symfony\OptionsResolver, Psr\Log, etc.) are NOT scoped —
        // they are loaded from the original vendor/ via vendor/autoload.php.
        // Only Sentry\* conflicts with ps_mbo; other packages do not.
        Finder::create()
            ->files()
            ->in(__DIR__ . '/../vendor/sentry/sentry/src')
            ->name('*.php'),
    ],

    // Namespaces that must NOT be prefixed.
    // Our own module classes stay in their original namespace.
    // External dependencies that we do NOT scope (they are loaded from
    // vendor/ via vendor/autoload.php) must also be left untouched so
    // the scoped Sentry code can reference them without the FrSentry\ prefix.
    'exclude-namespaces' => [
        'Frento',
        'PrestaShop',
        'Symfony',
        'Psr',
        'Jean85',
        'PackageVersions',
        'Composer',
        'React',
        'Clue',
    ],

    // Global symbols (functions, classes) that must NOT be prefixed
    // because they belong to PHP itself or to PrestaShop.
    'exclude-classes' => [
        'Module',
        'Configuration',
        'Tools',
        'Context',
        'Order',
    ],

    'exclude-functions' => [],

    'exclude-constants' => [
        'PHP_INT_SIZE',
        'DIRECTORY_SEPARATOR',
    ],

    // Patch files that cannot be handled automatically by the AST rewriter.
    // Currently empty — add entries here if the scoped autoloader breaks.
    'patchers' => [],
];
