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

namespace FrSentry\Sentry\Util;

use FrSentry\Sentry\Options;

trait PrefixStripper
{
    /**
     * Removes from the given file path the specified prefixes in the SDK options.
     */
    protected function stripPrefixFromFilePath(?Options $options, string $filePath): string
    {
        if ($options === null) {
            return $filePath;
        }
        foreach ($options->getPrefixes() as $prefix) {
            if (mb_substr($filePath, 0, mb_strlen($prefix)) === $prefix) {
                return mb_substr($filePath, mb_strlen($prefix));
            }
        }

        return $filePath;
    }
}
