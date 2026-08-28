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

namespace Lunu;

// Production settings (default)
DEFINE('LUNUPAYMENT_PROCESSING_VERSION', 'api');
DEFINE('LUNUPAYMENT_WIDGET_HOST', 'widget.lunupay.com');

// Sandbox settings (used when Sandbox mode is enabled)
DEFINE('LUNUPAYMENT_PROCESSING_VERSION_SANDBOX', 'api.sandbox');
DEFINE('LUNUPAYMENT_WIDGET_HOST_SANDBOX', 'widget.sandbox.lunupay.com');


class Lunu
{
    const LUNU_DEFAULT_EXPIRES = 3600;
    const VERSION = '2.2.1';
    const CONFIG_KEYS = array(
        'LUNU_SANDBOX_ENABLED',
        'LUNU_API_SECRET',
        'LUNU_APP_ID',
        'LUNU_PAYMENT_TIMEOUT',
        'LUNU_PENDING_ENABLED',
        'LUNU_GIFT_ENABLED',
        'LUNU_LOG_ENABLED',
        'LUNU_COUPON_PREFIX'
    );

    public static $app_id = '';
    public static $api_secret = '';
    public static $lunu_sandbox_enabled = 0;
    public static $lunu_pending_enabled = 0;
    public static $lunu_payment_timeout = 0;
    public static $lunu_gift_enabled = 0;
    public static $lunu_log_enabled = false;
    public static $lunu_coupon_prefix = '';

    // Default to production; will be overridden to sandbox if LUNU_SANDBOX_ENABLED is checked
    public static $endpoint_version = LUNUPAYMENT_PROCESSING_VERSION;
    public static $widget_host = LUNUPAYMENT_WIDGET_HOST;

    public static $lunu_order = null;
    public static $request_response = null;
    public static $error_message = '';
    public static $errors = null;
    public static $lunu_refunded_messages = null;

    public static function config($config)
    {
        if (!empty($config['LUNU_SANDBOX_ENABLED'])) {
            self::$lunu_sandbox_enabled = $config['LUNU_SANDBOX_ENABLED'] == 'on';
            if (self::$lunu_sandbox_enabled) {
                self::$endpoint_version = LUNUPAYMENT_PROCESSING_VERSION_SANDBOX;
                self::$widget_host = LUNUPAYMENT_WIDGET_HOST_SANDBOX;
            }

        }
        if (!empty($config['LUNU_APP_ID'])) {
            self::$app_id = trim($config['LUNU_APP_ID']);
        }
        if (!empty($config['LUNU_API_SECRET'])) {
            self::$api_secret = trim($config['LUNU_API_SECRET']);
        }
        if (!empty($config['LUNU_PAYMENT_TIMEOUT'])) {
            self::$lunu_payment_timeout = (int)$config['LUNU_PAYMENT_TIMEOUT'];
        }
        if (!empty($config['LUNU_PENDING_ENABLED'])) {
            self::$lunu_pending_enabled = $config['LUNU_PENDING_ENABLED'] == 'on';
        }
        if (!empty($config['LUNU_GIFT_ENABLED'])) {
            self::$lunu_gift_enabled = $config['LUNU_GIFT_ENABLED'] == 'on';
        }
        if (!empty($config['LUNU_LOG_ENABLED'])) {
            self::$lunu_log_enabled = $config['LUNU_LOG_ENABLED'] == 'on';
        }
        if (!empty($config['LUNU_COUPON_PREFIX'])) {
            self::$lunu_coupon_prefix = trim($config['LUNU_COUPON_PREFIX']);
        }
    }

    public static function lunu_log($message = '', $data = array())
    {
        if (!self::$lunu_log_enabled) return;
        
        // Use PrestaShop's Logger class for secure logging
        $log_message = 'Lunu Payment: ' . $message;
        if (!empty($data)) {
            // Remove sensitive data before logging
            if (isset($data['email'])) {
                $data['email'] = '***@***';
            }
            if (isset($data['request_data']['email'])) {
                $data['request_data']['email'] = '***@***';
            }
            $log_message .= ' | Data: ' . json_encode($data);
        }
        
        \PrestaShopLogger::addLog($log_message, 1, null, 'Lunu', null, true);
        return null;
    }


    public static function addCheckoutError($error_message)
    {
        $errors = self::$errors;
        if (!is_array($errors)) {
            $errors = array();
        }
        $errors[] = $error_message;
        self::$errors = $errors;
    }

    public static function payment_get($order_id)
    {
        return empty($order_id)
            ? null
            : self::requestPayment('get/' . $order_id);
    }

    public static function request(
        $url,
        $params = array(),
        $headers = array()
    )
    {
        $app_id = self::$app_id;
        $api_secret = self::$api_secret;

        # Check if credentials was passed
        if (empty($app_id) || empty($api_secret)) {
            self::$error_message = 'Lunu payment gateway credentials is invalid';
            self::lunu_log(self::$error_message);
            return null;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1
        ));
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge(array(
            'Authorization: Basic ' . base64_encode($app_id . ':' . $api_secret)
        ), $headers));
        curl_setopt($curl, CURLOPT_USERAGENT, 'Prestashop v' . _PS_VERSION_ . ' | Lunu Extension ' . self::VERSION);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        $raw_response = curl_exec($curl);
        $decoded_response = json_decode($raw_response, true);
        $response = $decoded_response ? $decoded_response : $raw_response;
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        self::$request_response = $response;
        if ($http_status === 200 && !empty($response['response'])) {
            return $response['response'];
        }
        if ($http_status === 502) {
            self::$request_response = array(
                'error' => array(
                    'message' => '502 Bad Gateway'
                )
            );
        }

        $params['email'] = '*******';
        self::lunu_log('Error of request to the Lunu Processing Service', array(
            'url' => $url,
            'request_data' => $params,
            'status' => $http_status,
            'body' => $raw_response
        ));
        return null;
    }

    public static function requestPayment(
        $route = '',
        $params = array(),
        $headers = array()
    )
    {
        return self::request(
            'https://' . self::$endpoint_version . '.lunupay.com/legacy-api/v1/payments/' . $route,
            $params,
            $headers
        );
    }

    public static function requestRefund(
        $route = '',
        $params = array(),
        $headers = array()
    )
    {
        return self::request(
            'https://' . self::$endpoint_version . '.lunupay.com/legacy-api/v1/refund/' . $route,
            $params,
            $headers
        );
    }

    public static function isInvalidPayment($order)
    {
        return strpos(\Tools::strtolower($order->payment), 'lunu') === false;
    }

    public static function refund($params, $smarty = null)
    {
        self::lunu_log('refund', $params);

        $with_credit = empty($params['with_credit'])
            ? false
            : $params['with_credit'];
        $without_already_refunded = empty($params['without_already_refunded'])
            ? false
            : $params['without_already_refunded'];
        $order = empty($params['order']) ? null : $params['order'];
        $order_id = empty($order)
            ? (int)$params['order_id']
            : $order->id;
        $refund_type = empty($params['type']) ? 'partial' : $params['type'];
        $refund_amount = (float)$params['refund_amount'];

        $real_partial = empty($params['real_partial']) ? false : true;
        $hasFull = $real_partial ? false : $refund_type !== 'partial';

        if (empty($order_id)) {
            $error_message = 'Order ID is invalid';
            self::addErrorMessage($error_message);
            self::lunu_log($error_message, array(
                'order_id' => $order_id
            ));
            return;
        }

        if (empty($order)) {
            $order = new \Order($order_id);
        }

        if (self::isInvalidPayment($order)) {
            $error_message = 'Payment gateway does not match the order';
            self::addErrorMessage($error_message);
            self::lunu_log($error_message, array(
                'payment_name' => $order->payment
            ));
            return;
        }

        $lunu_order = \Lunu\LunuOrder::loadByOrderId($order_id);
        if (empty($lunu_order)) {
            self::lunu_log('Payment not found', array(
                'order_id' => $order_id,
                'lunu_order' => $lunu_order
            ));
            return;
        }
        $data = $lunu_order->getMetaData();

        $id_customer = $order->id_customer;
        if (empty($id_customer)) {
            $error_message = 'Customer ID not found';
            self::addErrorMessage($error_message);
            self::lunu_log($error_message, array(
                'order_id' => $order_id,
                'id_customer' => $id_customer
            ));
            $data['messages'] = self::$lunu_refunded_messages;
            $lunu_order->setMetaData($data);
            $lunu_order->lunu_save();
            return;
        }

        $customer = new \Customer($id_customer);
        $customer_email = $customer->email;

        if (empty($customer_email)) {
            $error_message = 'Customer email not found';
            self::addErrorMessage($error_message);
            self::lunu_log($error_message, array(
                'order_id' => $order_id
            ));
            $data['messages'] = self::$lunu_refunded_messages;
            $lunu_order->setMetaData($data);
            $lunu_order->lunu_save();
            return;
        }

        $payment_id = $lunu_order->id_payment;
        $amount_paid = $lunu_order->amount_paid;

        $history = isset($data['history'])
            ? $data['history']
            : array();

        $already_refunded_amount = empty($data['already_refunded_amount'])
            ? 0
            : $data['already_refunded_amount'];

        $refund_count = empty($data['refund_count'])
            ? 0
            : $data['refund_count'];

        $refunds = isset($data['refunds'])
            ? $data['refunds']
            : array();


        $products_with_details = $order->getProductsDetail();

        $product_amount = 0;
        foreach ($products_with_details as $product) {
            $product_id = $product['product_id'];
            $product_quantity_refunded = $product['product_quantity_refunded'];
            $price = $product['price'];

            $already_refunded_products_quantity = empty($refunds[$product_id])
                ? 0
                : $refunds[$product_id];

            if ($product_quantity_refunded > $already_refunded_products_quantity) {
                if ($with_credit && $hasFull) {
                    $product_amount += ($product_quantity_refunded - $already_refunded_products_quantity) * $price;
                }
                $refunds[$product_id] = $product_quantity_refunded;
            }
        }

        $new_refund_amount = $without_already_refunded
            ? ($amount_paid - $already_refunded_amount)
            : ($product_amount + $refund_amount);

        if ($new_refund_amount == 0) {
            $error_message = 'Refund amount is invalid';
            self::addErrorMessage($error_message);
            self::lunu_log($error_message, array(
                'order_id' => $order_id,
                'refund_amount' => $new_refund_amount
            ));
            $data['messages'] = self::$lunu_refunded_messages;
            $lunu_order->setMetaData($data);
            $lunu_order->lunu_save();
            return;
        }

        $total_refund_amount = $already_refunded_amount + $new_refund_amount;

        if ($total_refund_amount > $amount_paid) {
            \Lunu\Lunu::lunu_log('The refund amount exceeds the payment amount', array(
                'amount_paid' => $amount_paid,
                'new_refund_amount' => $new_refund_amount,
                'already_refunded_amount' => $already_refunded_amount,
                'total_refund_amount' => $total_refund_amount
            ));
            return;
        }

        $time = time();
        $idempotence_key = 'ps_refund_' . $order_id . '_' . $time;

        if ($with_credit) {

            $refund = self::requestRefund('create', array(
                'payment_id' => $payment_id,
                'value_fiat' => '' . $new_refund_amount,
                'email' => $customer_email
            ), array(
                'Idempotence-Key: ' . $idempotence_key,
                'Content-Type: application/json'
            ));

            $refund_response = self::$request_response;
            $noopError = array(
                'message' => 'Error of request to the Lunu Processing Service'
            );
            if (empty($refund_response)) {
                $refund_response = array(
                    'error' => $noopError
                );
            }

            if (empty($refund)) {
                $refund_error = empty($refund_response['error'])
                    ? $noopError
                    : $refund_response['error'];
                self::addErrorMessage($refund_error['message']);
            } else {
                $amount_too_big = empty($refund['amount_too_big']) ? '' : $refund['amount_too_big'];
                $fiat_amount = empty($refund['fiat_amount']) ? 0 : $refund['fiat_amount'];

                if (!empty($smarty)) {
                    $smarty->assign(array(
                        'fiat_amount' => $fiat_amount,
                        'amount_too_big' => $amount_too_big
                    ));

                    self::addSuccessMessage(
                        $smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/successful_refund.tpl')
                    );
                }

                $data['already_refunded_amount'] = $already_refunded_amount + $fiat_amount;
            }

            array_unshift($history, array(
                'time' => $time,
                'type' => $refund_type,
                'amount' => $new_refund_amount,
                'response' => $refund_response
            ));

            $data['refund_count'] = $refund_count + 1;
            $data['history'] = $history;
        }

        $data['refunds'] = $refunds;
        $data['messages'] = self::$lunu_refunded_messages;
        $lunu_order->setMetaData($data);

        $lunu_order->lunu_save();
    }


    public static function addErrorMessage($message)
    {
        return self::addMessage($message, 'danger');
    }

    public static function addInfoMessage($message)
    {
        return self::addMessage($message, 'info');
    }

    public static function addWarnMessage($message)
    {
        return self::addMessage($message, 'warn');
    }

    public static function addSuccessMessage($message)
    {
        return self::addMessage($message, 'success');
    }

    public static function addMessage($message, $type = 'info')
    {
        $lunu_refunded_messages = self::$lunu_refunded_messages;
        if (!is_array($lunu_refunded_messages)) {
            $lunu_refunded_messages = array();
        }

        $lunu_refunded_messages[] = array(
            'type' => $type,
            'message' => $message
        );

        self::$lunu_refunded_messages = $lunu_refunded_messages;
    }
}
