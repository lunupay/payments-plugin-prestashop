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
<script>
  {literal}
    // add checkbox
    (function($doc) {
      $doc.ready(function() {
        var chb_lunu_refund = "{/literal}{$chb_lunu_refund|escape:'javascript':'UTF-8'}{literal}";
        var lunu_refund_submit_url = "{/literal}{$lunu_refund_submit_url|escape:'javascript':'UTF-8'}{literal}";
        var partial_refund_enable = {/literal}{$partial_refund_enable|escape:'javascript':'UTF-8'}{literal};

        if (partial_refund_enable) {
          // Make partial order refund in Order page in BO
          $doc.on('click', '#desc-order-partial_refund', function() {

            // Create checkbox and insert for Lunu refund
            if ($('.partial_refund_fields #doPartialRefundLunu').length > 0) {
              return;
            }

            $('.partial_refund_fields [name=partialRefund]')
              .parent('.partial_refund_fields')
              .prepend(
                `<p class="checkbox">
                  <label for="doPartialRefundLunu">
                    <input
                      type="checkbox"
                      id="doPartialRefundLunu"
                      name="doPartialRefundLunu"
                      value="1"
                    >
                    ${chb_lunu_refund}
                  </label>
                </p>`
              );

            $('#doPartialRefundLunu')[0].checked = true;
          });

          $doc.on('click', '#generateCreditSlip', function() {

            // Create checkbox and insert for Lunu refund
            if ($('#doStandardRefundLunu').length < 1) {

              $('#generateCreditSlip')
                .closest('.checkbox')
                .after(`<p
                  class="checkbox"
                  id="checkbox_doStandardRefundLunu"
                >
                  <label for="doStandardRefundLunu">
                    <input
                      type="checkbox"
                      id="doStandardRefundLunu"
                      name="doStandardRefundLunu"
                      value="1"
                    >
                    ${chb_lunu_refund}
                  </label>
                </p>`);

              $('#doStandardRefundLunu')[0].checked = true;
            }

            var $doStandardRefundLunuCheckbox = $('#checkbox_doStandardRefundLunu');
            if ($('#generateCreditSlip')[0].checked) {
              $doStandardRefundLunuCheckbox.show();
            } else {
              $doStandardRefundLunuCheckbox.hide();
            }

          });

          $doc.on('click', '.partial-refund-display', function() {

            // Create checkbox and insert for Lunu refund
            if ($('#doPartialRefundLunu').length < 0) {
              $('.refund-checkboxes-container').prepend(`
                <div class="cancel-product-element form-group" style="display: block;">
                  <div class="checkbox">
                    <div class="md-checkbox md-checkbox-inline">
                      <label>
                        <input
                          type="checkbox"
                          id="doPartialRefundLunu"
                          name="doPartialRefundLunu"
                          material_design="material_design"
                          value="1"
                        >
                        <i class="md-checkbox-control"></i>
                        ${chb_lunu_refund}
                      </label>
                    </div>
                  </div>
                </div>`
              );

              $('#doPartialRefundLunu')[0].checked = true;
            }
          });

        }


        $('.standard_refund_fields.form-horizontal.panel').before(`<form
          class="form-horizontal panel"
          action="${lunu_refund_submit_url}"
          method="POST"
        >
          <div class="row">
            <div style="float: left; width: 125px;">
              <div class="input-group">
                <input
                  type="text"
                  class="input fixed-width-md"
                  name="refunds_via_lunu_amount"
                  value="0"
                />
              </div>
            </div>
            <div style="float: left; width: 150px;">
              <input
                type="submit"
                name="refunds_via_lunu"
                value="Refunds via Lunu"
                class="btn btn-default"
              />
            </div>
          </div>
        </div>`);

      });

			var $topBlock = $('#modules_list_container');
			var $lunuModuleMessages = $('#lunu_module_messages');

			if ($topBlock.length > 0) {
				$topBlock.after($lunuModuleMessages);

				if ($lunuModuleMessages.find('.alert-danger').length) {
					$('.bootstrap>.alert-success').remove();
				}
			}

    })($(document));

  {/literal}
</script>
