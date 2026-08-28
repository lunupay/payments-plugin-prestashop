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
<div class="tab">
  <button class="tablinks" onclick="changeTab(event, 'Welcome')" id="defaultOpen">{l s='Welcome' mod='lunu'}</button>
  <button class="tablinks" onclick="changeTab(event, 'Configure Settings')">{l s='Configure Settings' mod='lunu'}</button>
</div>

<div id="Welcome" class="tabcontent">
	<div>
	  <iframe
      src="https://lunu.io/welcome"
      style="display: block; border: 0px solid #000000; width: 100%; min-height: 700px;"
    ></iframe>
	</div>
</div>
<div id="Configure Settings" class="tabcontent">
  {html_entity_decode($form|escape:'htmlall':'UTF-8')}
</div>

<script>
  document.getElementById("defaultOpen").click();
</script>
