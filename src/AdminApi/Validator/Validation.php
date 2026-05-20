<?php
/**
 * Sentry module for Prestashop
 * Version: 2.1.1
 * Copyright (c) 2023. Mateusz Szymański Frento IT
 * https://frentoit.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Frento IT <info@frentoit.com>
 * @copyright Copyright 2016-2025 © Frento IT Mateusz Szymański All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 * @category  Frento IT
 */

namespace Frento\FrSentry\src\AdminApi\Validator;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestashopModuleFrSentry\Rakit\Validation\Rules\Interfaces\BeforeValidate;

class Validation extends \PrestashopModuleFrSentry\Rakit\Validation\Validation
{
    /**
     * Constructor
     *
     * @param \Rakit\Validation\Validator $validator
     * @param array $inputs
     * @param array $rules
     * @param array $messages
     */
    public function __construct(
        Validator $validator,
        array $inputs,
        array $rules,
        array $messages = [],
        $errorBag,
    ) {
        $this->validator = $validator;
        $this->inputs = $this->resolveInputAttributes($inputs);
        $this->messages = $messages;
        $this->errors = $errorBag;

        foreach ($rules as $attributeKey => $rules) {
            $this->addAttribute($attributeKey, $rules);
        }
    }

    /**
     * Run validation
     *
     * @param array $inputs
     */
    public function validate(array $inputs = [])
    {
        $this->inputs = array_merge($this->inputs, $this->resolveInputAttributes($inputs));

        // Before validation hooks
        foreach ($this->attributes as $attributeKey => $attribute) {
            foreach ($attribute->getRules() as $rule) {
                if ($rule instanceof BeforeValidate) {
                    $rule->beforeValidate();
                }
            }
        }

        foreach ($this->attributes as $attributeKey => $attribute) {
            $this->validateAttribute($attribute);
        }
    }
}
