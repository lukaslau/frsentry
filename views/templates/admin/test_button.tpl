{**
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
 *}
<div class="form-group frsentry-test-row" data-target="{$target|escape:'html':'UTF-8'}" style="display:none;">
    <div class="col-lg-9 col-lg-offset-3" style="display:flex;align-items:center;gap:12px;">
        <button type="button"
                class="btn btn-default frsentry-test-btn"
                data-target="{$target|escape:'html':'UTF-8'}"
                data-url="{$adminUrl|escape:'html':'UTF-8'}">
            <i class="icon-paper-plane"></i>&nbsp;{$label_send_test|escape:'html':'UTF-8'}
        </button>
        <span class="frsentry-test-result"></span>
    </div>
</div>
