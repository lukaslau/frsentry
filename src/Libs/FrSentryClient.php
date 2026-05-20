<?php
/**
 * Sentry module for Prestashop
 * Version: 2.1.1
 * Copyright (c) 2023. Mateusz Szymański Frento
 * https://frentoit.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Frento <info@frentoit.com>
 * @copyright Copyright 2016-2025 © Frento Mateusz Szymański All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @category  Frento
 */

namespace Frento\FrSentry\src\Libs;

if (!defined('_PS_VERSION_')) {
    exit;
}

class FrSentryClient
{
    public $dsn;

    public function __construct(string $dsn)
    {
        $this->dsn = FrSentryDsn::createFromString($dsn);
    }

    public function captureException(\Throwable $exception, $tags = [])
    {
        try {
            $tags['url'] = $actualLink;
            $actualLink = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $data = [
                'event_id' => $this->generateId(),
                'timestamp' => microtime(true),
                'platform' => 'php',
                'sdk' => [
                    'name' => 'sentry.php',
                    'version' => '3.21.0',
                ],
                'logger' => 'php',
                'tags' => $tags,
                'exception' => $this->throwableToArray($exception),
            ];

            $curl = curl_init();

            curl_setopt_array($curl, [
                // CURLOPT_URL => 'https://o4505637382062080.ingest.sentry.io/api/4505637384945664/store/',
                CURLOPT_URL => sprintf(
                    'https://%s/api/%s/store/',
                    $this->dsn['host'],
                    $this->dsn['projectId']
                ),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 2,
                CURLOPT_TIMEOUT => 2,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'X-Sentry-Auth: Sentry sentry_version=7, sentry_client=sentry.php/3.21.0, sentry_key=' . $this->dsn['user'],
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function throwableToArray(\Throwable $exception): array
    {
        $frames = [];
        $trace = [];
        $maxLines = 10;

        $trace[] = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'function' => null,
        ];

        foreach ($exception->getTrace() as $e) {
            $trace[] = $e;
        }

        foreach ($trace as $frame) {
            $contextLine = '';
            $inApp = true;
            $preContext = [];
            $postContext = [];

            if (isset($frame['file']) && isset($frame['line'])) {
                $absPath = realpath($frame['file']) ?: $frame['file'];
                $filename = $frame['file'];
                $lineno = $frame['line'];
                $inApp = true;

                $fileContent = file($filename);

                if ($fileContent !== false) {
                    $startLine = max(1, $lineno - $maxLines);
                    $endLine = min(count($fileContent), $lineno + $maxLines);
                    $preContext = array_slice($fileContent, $startLine - 1, $lineno - $startLine);
                    $contextLine = trim($fileContent[$lineno - 1]);
                    $postContext = array_slice($fileContent, $lineno, $endLine - $lineno);
                }
            } else {
                $absPath = '';
                $filename = '';
                $lineno = 0;
                $inApp = false;
            }

            $frames[] = [
                'filename' => $filename,
                'lineno' => $lineno,
                'in_app' => $inApp,
                'abs_path' => $absPath,
                'pre_context' => $preContext,
                'context_line' => $contextLine,
                'post_context' => $postContext,
            ];
        }

        $exceptionData = [
            'type' => get_class($exception),
            'value' => $exception->getMessage(),
            'stacktrace' => [
                'frames' => array_reverse($frames),
            ],
            'mechanism' => [
                'type' => 'generic',
                'handled' => true,
                'data' => [
                    'code' => 0,
                ],
            ],
        ];

        return ['values' => [$exceptionData]];
    }

    private function generateId()
    {
        if (\function_exists('uuid_create')) {
            return strtolower(str_replace('-', '', uuid_create(UUID_TYPE_RANDOM)));
        }

        $uuid = bin2hex(random_bytes(16));

        return sprintf(
            '%08s%04s4%03s%04x%012s',
            // 32 bits for "time_low"
            substr($uuid, 0, 8),
            // 16 bits for "time_mid"
            substr($uuid, 8, 4),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            substr($uuid, 13, 3),
            // 16 bits:
            // * 8 bits for "clk_seq_hi_res",
            // * 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            hexdec(substr($uuid, 16, 4)) & 0x3FFF | 0x8000,
            // 48 bits for "node"
            substr($uuid, 20, 12)
        );
    }
}
