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

/**
 * This class provides some utility methods to work with arrays.
 *
 * @internal
 */
class Arr
{
    /**
     * Flatten a multi-dimensional associative array with dots except for keys that contain lists.
     *
     * This method is similar to Laravel's `Arr::dot()` method but does not flatten lists.
     * See: https://github.com/laravel/framework/blob/1bfad3020ec5d542ac7352c6fd0d388cbe29c46c/src/Illuminate/Collections/Arr.php#L163
     *
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    public static function simpleDot(array $array): array
    {
        $results = [];
        $flatten = static function ($data, $prefix = '') use (&$results, &$flatten): void {
            foreach ($data as $key => $value) {
                $newKey = $prefix . $key;
                if (\is_array($value) && !empty($value) && !self::isList($value)) {
                    $flatten($value, $newKey . '.');
                } else {
                    $results[$newKey] = $value;
                }
            }
        };
        $flatten($array);

        return $results;
    }

    /**
     * Checks whether a given array is a list.
     *
     * `array_is_list` is introduced in PHP 8.1, so we have a polyfill for it.
     *
     * @see https://www.php.net/manual/en/function.array-is-list.php#126794
     *
     * @param array<string, mixed> $array
     */
    public static function isList(array $array): bool
    {
        $i = 0;
        foreach ($array as $k => $v) {
            if ($k !== $i++) {
                return \false;
            }
        }

        return \true;
    }
}
