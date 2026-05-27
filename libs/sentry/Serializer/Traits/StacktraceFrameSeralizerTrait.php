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

use FrSentry\Sentry\Frame;

/**
 * @internal
 */
trait StacktraceFrameSeralizerTrait
{
    /**
     * @return array<string, mixed>
     *
     * @phpstan-return array{
     *     filename: string,
     *     lineno: int,
     *     in_app: bool,
     *     abs_path?: string,
     *     function?: string,
     *     raw_function?: string,
     *     pre_context?: string[],
     *     context_line?: string,
     *     post_context?: string[],
     *     vars?: array<string, mixed>
     * }
     */
    protected static function serializeStacktraceFrame(Frame $frame): array
    {
        $result = ['filename' => $frame->getFile(), 'lineno' => $frame->getLine(), 'in_app' => $frame->isInApp()];
        if ($frame->getAbsoluteFilePath() !== null) {
            $result['abs_path'] = $frame->getAbsoluteFilePath();
        }
        if ($frame->getFunctionName() !== null) {
            $result['function'] = $frame->getFunctionName();
        }
        if ($frame->getRawFunctionName() !== null) {
            $result['raw_function'] = $frame->getRawFunctionName();
        }
        if (!empty($frame->getPreContext())) {
            $result['pre_context'] = $frame->getPreContext();
        }
        if ($frame->getContextLine() !== null) {
            $result['context_line'] = $frame->getContextLine();
        }
        if (!empty($frame->getPostContext())) {
            $result['post_context'] = $frame->getPostContext();
        }
        if (!empty($frame->getVars())) {
            $result['vars'] = $frame->getVars();
        }

        return $result;
    }
}
