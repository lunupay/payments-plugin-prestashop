<?php
/**
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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . '/lunu/vendor/lunu/init.php';

class LunuRedirectModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public $module;

    public function postProcess()
    {
        parent::initContent();

        $module = $this->module;
        $context = $this->context;
        $cart = $context->cart;
        $id_order = $cart->id;

        $cart_amount = $cart->getOrderTotal(true, Cart::BOTH);
        $cart_amount_without_shipping = $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
        $cart_amount_only_shipping = $cart_amount - $cart_amount_without_shipping;

        if ($cart_amount_without_shipping < 0.01) {
            \Lunu\Lunu::lunu_log('payment amount is invlaid', array(
                'cart_id' => $id_order,
                'cart_amount' => $cart_amount,
                'cart_amount_products' => $cart_amount_without_shipping,
                'cart_amount_only_shipping' => $cart_amount_only_shipping,
            ));
            \Tools::redirect('index.php?controller=order&step=3');
            return;
        }

        if (!$module->checkCurrency($cart)) {
            \Tools::redirect('index.php?controller=order');
            return;
        }

        \Lunu\Lunu::config(\Configuration::getMultiple(\Lunu\Lunu::CONFIG_KEYS));

        $id_customer = $cart->id_customer;

        $description = array();
        foreach ($cart->getProducts() as $product) {
            $quantity_to_add = $product['cart_quantity'];
            $description[] = $quantity_to_add . ' × ' . $product['name'];
        }

        // clear cart
        // $cart->delete();

        if (empty($id_customer)) {
            \Lunu\Lunu::lunu_log('Customer ID not found', array(
                'cart_id' => $id_order,
                'id_customer' => $id_customer
            ));
        }

        $customer = new Customer($id_customer);

        $link = new Link();
        $success_url = $link->getPageLink('order-confirmation', null, null, array(
            'id_cart' => $id_order,
            'id_module' => $module->id,
            'key' => $customer->secure_key
        ));

        $contextLink = $context->link;
        $cancel_url = $contextLink->getModuleLink('lunu', 'cancel');

        $time = time();

        $customer_email = $customer->email;

      if (empty($customer_email)) {
            \Lunu\Lunu::lunu_log('Customer email not found', array(
                'cart_id' => $id_order,
                'id_customer' => $id_customer
            ));
        }

        $lunu_payment_timeout = \Lunu\Lunu::$lunu_payment_timeout * 60;
        if ($lunu_payment_timeout < 1) {
            $lunu_payment_timeout = \Lunu\Lunu::LUNU_DEFAULT_EXPIRES;
        }

        $params = array(
            'email' => $customer_email,
            'shop_order_id' => '' . $id_order,
            'callback_url' => $contextLink->getModuleLink('lunu', 'callback')
              . '?order_id=' . $id_order,
            'amount' => '' . $cart_amount,
            'amount_of_shipping' => '' . $cart_amount_only_shipping,
            'description' => Configuration::get('PS_SHOP_NAME') . ' Order #'
                . $id_order . ' ' . join(', ', $description),
            'expires' => date("c", $time + $lunu_payment_timeout)
        );

      $payment = \Lunu\Lunu::requestPayment('create', $params, array(
            'Idempotence-Key: ps_' . $id_order . '_' . $time,
            'Content-Type: application/json'
        ));

        $error_message = \Lunu\Lunu::$error_message;
        if (!empty($error_message)) {
            \Lunu\Lunu::addCheckoutError($error_message);
            \Tools::redirect('index.php?controller=order&step=3&' . http_build_query(array(
                'errors' => \Lunu\Lunu::$errors
            )));
            return;
        }

        $params['email'] = '*******';
        \Lunu\Lunu::lunu_log('payment create', array(
            'request' => $params,
            'response' => $payment
        ));

        if (empty($payment)) {
            \Lunu\Lunu::addCheckoutError('Lunu Payment service is temporarily unavailable');
            \Tools::redirect('index.php?controller=order&step=3&' . http_build_query(array(
                'errors' => \Lunu\Lunu::$errors
            )));
            return;
        }

        if (\Lunu\Lunu::$lunu_pending_enabled) {
            $module->validateOrder(
                $id_order,
                (int)\Configuration::get('LUNU_AWAITING_PAYMENT'),
                // (int)\Configuration::get('PS_OS_BANKWIRE'),
                $cart_amount,
                $module->displayName,
                null,
                null,
                (int) $context->currency->id,
                false,
                $cart->secure_key
            );
        }

        \Tools::redirect('https://widget.lunupay.com/' . \Lunu\Lunu::$widget_version . '/#/?' . http_build_query(array(
            'action' => 'select',
            'token' => $payment['confirmation_token'],
            'success' => $success_url,
            'cancel' => $cancel_url,
            'enableLunuGift' => \Lunu\Lunu::$lunu_gift_enabled ? 1 : 0,
            'version' => \Lunu\Lunu::VERSION,
        )));
    }
}
