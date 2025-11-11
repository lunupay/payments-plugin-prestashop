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

class LunuCallbackModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        if (_PS_VERSION_ >= '1.7') {
            $this->setTemplate('module:lunu/views/templates/front/payment_callback.tpl');
        } else {
            $this->setTemplate('payment_callback.tpl');
        }

        $callback_data = json_decode(\Tools::file_get_contents('php://input'), true);

        \Lunu\Lunu::config(\Configuration::getMultiple(\Lunu\Lunu::CONFIG_KEYS));

        \Lunu\Lunu::lunu_log('callback payment', $callback_data);

        if (!is_array($callback_data)) {
            \Lunu\Lunu::lunu_log('Callback data is empty');
            return;
        }


        if (empty($callback_data['id'])) {
            \Lunu\Lunu::lunu_log('Parameter id in the body is empty', $callback_data);
            return;
        }
        $payment_id = $callback_data['id'];

        $shop_order_id = (int)(
          empty($callback_data['shop_order_id'])
            ? 0
            : $callback_data['shop_order_id']
        );
        if (empty($shop_order_id)) {
            $shop_order_id = (int)\Tools::getValue('order_id');
        }

        if (empty($shop_order_id)) {
            \Lunu\Lunu::lunu_log('Parameter shop_order_id is empty');
            return;
        }

        $cart = new \Cart($shop_order_id);
        $qty = $cart->nbProducts();

        if (!\Validate::isLoadedObject($cart) || $qty < 1) {
            \Lunu\Lunu::lunu_log('Cart is invalid', array(
                'qty' => $qty,
                'order_id' => $shop_order_id
            ));
            return;
        }

        $callback_payment_status = \Tools::strtolower($callback_data['status']);

        if (empty($callback_payment_status)) {
            return;
        }

        $lunu_payment = \Lunu\Lunu::payment_get($payment_id);

        if (empty($lunu_payment)) {
            \Lunu\Lunu::lunu_log('Lunu Payment #' . $payment_id . ' does not exist');
            return;
        }

        \Lunu\Lunu::lunu_log('checking payment', $lunu_payment);

        $payment_status = \Tools::strtolower($lunu_payment['status']);
        $payment_amount = (float)$lunu_payment['amount'];

        if ($callback_payment_status !== $payment_status) {
            return;
        }

        $id_order = \Order::getOrderByCartId($shop_order_id);

        $ps_order_paid = (int)\Configuration::get('PS_OS_PAYMENT');
        $ps_order_canceled = (int)\Configuration::get('PS_OS_CANCELED');
        $ps_order_error = (int)\Configuration::get('PS_OS_ERROR');

        if (!empty($id_order)) {
            $order = new \Order((int) $id_order);

            $ps_current_status = (int)$order->getCurrentState();
            if ($ps_current_status === $ps_order_paid
                || $ps_current_status === $ps_order_canceled
                || $ps_current_status === $ps_order_error) {
                return;
            }

            if ($payment_status === 'expired') {
                $history = new \OrderHistory();
                $history->id_order = $id_order;
                $history->changeIdOrderState($ps_order_canceled, $id_order);
                $history->addWithemail(true);

                // 'PS_OS_REFUND'
            } elseif ($payment_status === 'failed') {
                $history = new \OrderHistory();
                $history->id_order = $id_order;
                $history->changeIdOrderState($ps_order_error, $id_order);
                $history->addWithemail(true);
            }
        }

        if ($payment_status === 'paid') {
            $ps_order_status = $ps_order_paid;
        } else {
            return;
        }

        $order_amount = (float)$cart->getOrderTotal(true, \Cart::BOTH);

        if ($order_amount !== $payment_amount) {
            \Lunu\Lunu::lunu_log('Incorrect payment amount', array(
                'payment_amount' => $payment_amount,
                'order_amount' => $order_amount
            ));
            return;
        }

        $module = $this->module;

        if (empty($id_order)) {
            $module->validateOrder(
                $shop_order_id,
                $ps_order_status,
                $payment_amount,
                $module->displayName,
                null,
                null,
                (int) $this->context->currency->id,
                false,
                $cart->secure_key
            );
            $id_order = \Order::getOrderByCartId($shop_order_id);
        } else {
            $history = new \OrderHistory();
            $history->id_order = $id_order;

            $history->changeIdOrderState($ps_order_status, $id_order);

            $history->addWithemail(true);
        }

        $lunu_order = new \Lunu\LunuOrder();
        $lunu_order->id_cart = $shop_order_id;
        $lunu_order->id_order = $id_order;
        $lunu_order->id_payment = $payment_id;
        $lunu_order->amount_paid = $payment_amount;

        $lunu_order->lunu_add();
    }
}
