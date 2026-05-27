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

namespace FrSentry\Sentry\Transport;

/**
 * This enum represents all possible reasons an event sending operation succeeded
 * or failed.
 */
class ResultStatus implements \Stringable
{
    /**
     * @var string The value of the enum instance
     */
    private $value;
    /**
     * @var array<string, self>
     */
    private static $instances = [];

    /**
     * Constructor.
     *
     * @param string $value The value of the enum instance
     */
    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Returns an instance of this enum representing the fact that the event
     * failed to be sent due to unknown reasons.
     */
    public static function unknown(): self
    {
        return self::getInstance('UNKNOWN');
    }

    /**
     * Returns an instance of this enum representing the fact that event was
     * skipped from being sent.
     */
    public static function skipped(): self
    {
        return self::getInstance('SKIPPED');
    }

    /**
     * Returns an instance of this enum representing the fact that the event
     * was sent successfully.
     */
    public static function success(): self
    {
        return self::getInstance('SUCCESS');
    }

    /**
     * Returns an instance of this enum representing the fact that the event
     * failed to be sent because the content was too large.
     */
    public static function contentTooLarge(): self
    {
        return self::getInstance('CONTENT_TOO_LARGE');
    }

    /**
     * Returns an instance of this enum representing the fact that the event
     * failed to be sent because of API rate limiting.
     */
    public static function rateLimit(): self
    {
        return self::getInstance('RATE_LIMIT');
    }

    /**
     * Returns an instance of this enum representing the fact that the event
     * failed to be sent because the server was not able to process the request.
     */
    public static function invalid(): self
    {
        return self::getInstance('INVALID');
    }

    /**
     * Returns an instance of this enum representing the fact that the event
     * failed to be sent because the server returned a server error.
     */
    public static function failed(): self
    {
        return self::getInstance('FAILED');
    }

    /**
     * Returns an instance of this enum according to the given HTTP status code.
     *
     * @param int $statusCode The HTTP status code
     */
    public static function createFromHttpStatusCode(int $statusCode): self
    {
        switch (\true) {
            case $statusCode >= 200 && $statusCode < 300:
                return self::success();
            case $statusCode === 413:
                return self::contentTooLarge();
            case $statusCode === 429:
                return self::rateLimit();
            case $statusCode >= 400 && $statusCode < 500:
                return self::invalid();
            case $statusCode >= 500:
                return self::failed();
            default:
                return self::unknown();
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function getInstance(string $value): self
    {
        if (!isset(self::$instances[$value])) {
            self::$instances[$value] = new self($value);
        }

        return self::$instances[$value];
    }
}
