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

use PrestashopModuleFrSentry\Rakit\Validation\ErrorBag;
use Frento\FrSentry\src\AdminApi\Exception\QueryValidationException;

class Validator extends \PrestashopModuleFrSentry\Rakit\Validation\Validator
{
    /**
     * @var ErrorBag
     */
    public $errors;

    /**
     * Constructor
     *
     * @param array $messages
     */
    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
        $this->errors = new ErrorBag();
        $this->registerBaseValidators();

        // $this->addValidator('ValidateIp', new ValidateIp());
        // $this->addValidator('TextLength', new TextLength());
    }

    private function trans($value, $parameters = [])
    {
        return \Context::getContext()->getTranslator()->trans(
            $value,
            $parameters,
            'Modules.FrSentry'
        );
    }

    // /**
    // * Run validation
    // *
    // * @param array $inputs
    // * @return void
    // */
    // public function validate(array $inputs, array $rules, array $messages = []): Validation
    // {
    //    $this->inputs = array_merge($this->inputs, $this->resolveInputAttributes($inputs));
    //
    //    // Before validation hooks
    //    foreach ($this->attributes as $attributeKey => $attribute) {
    //        foreach ($attribute->getRules() as $rule) {
    //            if ($rule instanceof BeforeValidate) {
    //                $rule->beforeValidate();
    //            }
    //        }
    //    }
    //
    //    foreach ($this->attributes as $attributeKey => $attribute) {
    //        $this->validateAttribute($attribute);
    //    }
    // }

    public function addError($key, $message)
    {
        $this->errors->add(
            $key,
            '',
            $this->trans($message)
        );
    }

    /**
     * Metoda ma za zadanie liczyc error bag
     *
     * @throws QueryValidationException
     */
    public function check()
    {
        if ($this->errors->count()) {
            throw new QueryValidationException($this->errors);
        }
    }

    /**
     * Given $inputs, $rules and $messages to make the Validation class instance
     *
     * @param array $inputs
     * @param array $rules
     * @param array $messages
     *
     * @return Validation
     */
    public function make(array $inputs, array $rules, array $messages = []): \PrestashopModuleFrSentry\Rakit\Validation\Validation
    {
        $messages = array_merge($this->messages, $messages);
        $validation = new Validation($this, $inputs, $rules, $messages, $this->errors);
        $validation->setTranslations($this->getTranslations());

        return $validation;
    }
}
