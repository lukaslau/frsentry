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

namespace FrSentry\Sentry\Serializer\Traits;

use FrSentry\Sentry\Breadcrumb;

/**
 * @internal
 */
trait BreadcrumbSeralizerTrait
{
    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array{
     *     type: string,
     *     category: string,
     *     level: string,
     *     timestamp: float,
     *     message?: string,
     *     data?: object
     * }
     */
    protected static function serializeBreadcrumb(Breadcrumb $breadcrumb): array
    {
        $result = ['type' => $breadcrumb->getType(), 'category' => $breadcrumb->getCategory(), 'level' => $breadcrumb->getLevel(), 'timestamp' => $breadcrumb->getTimestamp()];
        if ($breadcrumb->getMessage() !== null) {
            $result['message'] = $breadcrumb->getMessage();
        }
        if (!empty($breadcrumb->getMetadata())) {
            $result['data'] = (object) $breadcrumb->getMetadata();
        }

        return $result;
    }
}
