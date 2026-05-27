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

namespace FrSentry\Sentry\Context;

/**
 * This class stores information about the current runtime.
 *
 * @author Stefano Arlandini <sarlandini@alice.it>
 */
final class RuntimeContext
{
    /**
     * @var string The name of the runtime
     */
    private $name;
    /**
     * @var string|null The version of the runtime
     */
    private $version;
    /**
     * @var string|null The SAPI (Server API) name
     */
    private $sapi;

    /**
     * Constructor.
     *
     * @param string $name The name of the runtime
     * @param string|null $version The version of the runtime
     * @param string|null $sapi The SAPI name of the runtime
     */
    public function __construct(string $name, ?string $version = null, ?string $sapi = null)
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('The $name argument cannot be an empty string.');
        }
        $this->name = $name;
        $this->version = $version;
        $this->sapi = $sapi;
    }

    /**
     * Gets the name of the runtime.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the runtime.
     *
     * @param string $name The name
     */
    public function setName(string $name): void
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('The $name argument cannot be an empty string.');
        }
        $this->name = $name;
    }

    /**
     * Gets the version of the runtime.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Sets the version of the runtime.
     *
     * @param string|null $version The version
     */
    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    /**
     * Gets the SAPI of the runtime.
     */
    public function getSAPI(): ?string
    {
        return $this->sapi;
    }

    /**
     * Sets the SAPI of the runtime.
     *
     * @param string|null $sapi The SAPI name
     */
    public function setSAPI(?string $sapi): void
    {
        $this->sapi = $sapi;
    }
}
