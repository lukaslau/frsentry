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

namespace FrSentry\Sentry\Agent\Transport;

use FrSentry\Sentry\Client;
use FrSentry\Sentry\HttpClient\HttpClient;
use FrSentry\Sentry\HttpClient\HttpClientInterface;

final class AgentClientBuilder
{
    /**
     * @var string
     */
    private $host = '127.0.0.1';
    /**
     * @var int
     */
    private $port = 5148;
    /**
     * @var (callable(): HttpClientInterface)|null
     */
    private $fallbackClientFactory;
    /**
     * @var bool
     */
    private $isFallbackClientDisabled = \false;
    /**
     * @var string
     */
    private $sdkIdentifier = Client::SDK_IDENTIFIER;
    /**
     * @var string
     */
    private $sdkVersion = Client::SDK_VERSION;

    public static function create(): self
    {
        return new self();
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function disableFallbackClient(): self
    {
        $this->isFallbackClientDisabled = \true;
        $this->fallbackClientFactory = null;

        return $this;
    }

    public function setFallbackClient(HttpClientInterface $fallbackClient): self
    {
        return $this->setFallbackClientFactory(static function () use ($fallbackClient): HttpClientInterface {
            return $fallbackClient;
        });
    }

    /**
     * @phpstan-param callable(): HttpClientInterface $fallbackClientFactory
     */
    public function setFallbackClientFactory(callable $fallbackClientFactory): self
    {
        $this->isFallbackClientDisabled = \false;
        $this->fallbackClientFactory = $fallbackClientFactory;

        return $this;
    }

    public function setSdkIdentifier(string $sdkIdentifier): self
    {
        $this->sdkIdentifier = $sdkIdentifier;

        return $this;
    }

    public function setSdkVersion(string $sdkVersion): self
    {
        $this->sdkVersion = $sdkVersion;

        return $this;
    }

    public function getClient(): AgentClient
    {
        if ($this->isFallbackClientDisabled) {
            return new AgentClient($this->host, $this->port, null);
        }
        if ($this->fallbackClientFactory !== null) {
            return new AgentClient($this->host, $this->port, $this->fallbackClientFactory);
        }

        return new AgentClient($this->host, $this->port, $this->createDefaultFallbackClientFactory());
    }

    /**
     * @return callable(): HttpClientInterface
     */
    private function createDefaultFallbackClientFactory(): callable
    {
        $sdkIdentifier = $this->sdkIdentifier;
        $sdkVersion = $this->sdkVersion;

        return static function () use ($sdkIdentifier, $sdkVersion): HttpClientInterface {
            return new HttpClient($sdkIdentifier, $sdkVersion);
        };
    }
}
