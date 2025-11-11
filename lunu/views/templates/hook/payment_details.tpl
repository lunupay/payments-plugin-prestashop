{*
* NOTICE OF LICENSE
*
* The MIT License (MIT)
*
* Copyright (c) 2019-2025 Lunu Solutions GmbH
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of
* this software and associated documentation files (the "Software"), to deal in
* the Software without restriction, including without limitation the rights to use,
* copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
* and to permit persons to whom the Software is furnished to do so, subject
* to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
* WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
* IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*
*  @author    Lunu Solutions GmbH <info@lunu.io>
*  @copyright 2019-2025 Lunu Solutions GmbH
*  @license   https://gitlab.lunu.io/widget/presta-shop/blob/master/LICENSE  The MIT License (MIT)
*}
<div class="alert alert-warning">
  <button type="button" class="close" data-dismiss="alert">×</button>

  {l s='Lunu payment details' mod='lunu'}
  <br/><br/>ID: {$payment_id|escape:'htmlall':'UTF-8'}
  <br/>{l s='Payment amount:' mod='lunu'} {number_format($amount_paid, 2, '.', ' ')|escape:'htmlall':'UTF-8'}
  {if $already_refunded_amount > 0}
    <br/>{l s='Refunded amount:' mod='lunu'} {number_format($already_refunded_amount, 2, '.', ' ')|escape:'htmlall':'UTF-8'}
  {/if}
  {if $refund_count > 0}
    <br/>{l s='Number of returns:' mod='lunu'} {$refund_count|escape:'htmlall':'UTF-8'}
  {/if}

  {if count($refunds_history) > 0}
    <br/><br/>{l s='Refunds history' mod='lunu'}
    {foreach from=$refunds_history key=index item=item}
      <br/>
      {date('Y-m-d H:i', $item['time'])|escape:'htmlall':'UTF-8'}
      | {$item['type']|escape:'htmlall':'UTF-8'}
      | {number_format($item['amount'], 2, '.', ' ')|escape:'htmlall':'UTF-8'}
      {if empty($item['error_message'])}
        | {l s='iban:' mod='lunu'} {$item['iban']|escape:'htmlall':'UTF-8'}; {l s='purpose:' mod='lunu'} {$item['purpose']|escape:'htmlall':'UTF-8'}
        {if $item['amount_too_big']}
          | {l s='The refund has been partially completed' mod='lunu'} ({number_format($item['fiat_amount'], 2, '.', ' ')|escape:'htmlall':'UTF-8'})
        {/if}
      {else}
        | {l s='Error:' mod='lunu'} {$item['error_message']|escape:'htmlall':'UTF-8'}
      {/if}
    {/foreach}
  {/if}
</div>
