<?php
/**
 * NOTICE OF LICENSE
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2019-2021 Lunu Solutions GmbH
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
 *  @copyright 2019-2021 Lunu Solutions GmbH
 *  @license   https://gitlab.lunu.io/widget/presta-shop/blob/master/LICENSE  The MIT License (MIT)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . '/lunu/vendor/lunu/init.php');

DEFINE('LUNU_SERVER_HTTP_REFERER', $_SERVER['HTTP_REFERER']);

class LunuRefundController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
    }
    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();

        $id_order = (int)\Tools::getValue('id_order');
        $new_refund_amount = (float)\Tools::getValue('refunds_via_lunu_amount');

        \Lunu\Lunu::config(\Configuration::getMultiple(\Lunu\Lunu::CONFIG_KEYS));
        \Lunu\Lunu::refund(array(
            'order_id' => $id_order,
            'type' => 'partial',
            'refund_amount' => $new_refund_amount,
            'with_credit' => 1
        ));

        \Tools::redirect(LUNU_SERVER_HTTP_REFERER);
    }
}
