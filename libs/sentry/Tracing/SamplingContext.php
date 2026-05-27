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

namespace FrSentry\Sentry\Tracing;

final class SamplingContext
{
    /**
     * @var TransactionContext|null The context of the transaction
     */
    private $transactionContext;
    /**
     * @var bool|null Sampling decision from the parent transaction, if any
     */
    private $parentSampled;
    /**
     * @var float|null The parent sample rate
     */
    private $sampleRand;
    /**
     * @var array<string, mixed>|null Additional context, depending on where the SDK runs
     */
    private $additionalContext;

    /**
     * Returns an instance populated with the data of the transaction context.
     */
    public static function getDefault(TransactionContext $transactionContext): self
    {
        $context = new self();
        $context->transactionContext = $transactionContext;
        $context->parentSampled = $transactionContext->getParentSampled();
        $context->sampleRand = $transactionContext->getMetadata()->getSampleRand();

        return $context;
    }

    public function getTransactionContext(): ?TransactionContext
    {
        return $this->transactionContext;
    }

    /**
     * Gets the sampling decision from the parent transaction, if any.
     */
    public function getParentSampled(): ?bool
    {
        return $this->parentSampled;
    }

    public function getSampleRand(): ?float
    {
        return $this->sampleRand;
    }

    /**
     * Sets the sampling decision from the parent transaction, if any.
     */
    public function setParentSampled(?bool $parentSampled): self
    {
        $this->parentSampled = $parentSampled;

        return $this;
    }

    /**
     * Sets additional data that will be provided as a second argument to {@link \Sentry\startTransaction()}.
     *
     * @param array<string, mixed>|null $additionalContext
     */
    public function setAdditionalContext(?array $additionalContext): self
    {
        $this->additionalContext = $additionalContext;

        return $this;
    }

    /**
     * Gets the additional data that will be provided as a second argument to {@link \Sentry\startTransaction()}.
     *
     * @return array<string, mixed>|null
     */
    public function getAdditionalContext(): ?array
    {
        return $this->additionalContext;
    }
}
