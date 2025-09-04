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

require_once _PS_MODULE_DIR_ . '/lunu/vendor/lunu/init.php';

class Lunu extends PaymentModule
{
    /**
     * List of objectModel used in this Module
     * @var array
     */
    public $objectModels = array(
        'LunuOrder'
    );

    public function __construct()
    {
        $this->name = 'lunu';
        $this->tab = 'payments_gateways';
        $this->version = '2.2.0';
        $this->author = 'Lunu Solutions GmbH';
        $this->is_eu_compatible = 1;
        $this->controllers = array('payment', 'redirect', 'callback', 'cancel');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->module_key = '15b30a9cbfad69424dd2aac3cbea5616';

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;

        $config = \Configuration::getMultiple(\Lunu\Lunu::CONFIG_KEYS);

        $api_secret = trim($config['LUNU_API_SECRET']);
        $app_id = trim($config['LUNU_APP_ID']);

        parent::__construct();

        $this->displayName = $this->l('Lunu');
        $this->description = $this->l('Accept Bitcoin and other cryptocurrencies as a payment method with Lunu');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (empty($api_secret) || empty($app_id)) {
            $this->warning = $this->l('API Access details must be configured in order to use this module correctly.');
        }
    }

    public function install()
    {
        if (!function_exists('curl_version')) {
            $this->_errors[] = $this->l('This module requires cURL PHP extension in order to function normally.');
            return false;
        }


        $tab = new Tab;
        $tab->class_name = 'LunuRefund';
        $tab->module = $this->name;
        $tab->id_parent = 0;
        $tab->active = false;
        $names = array();
        foreach (\Language::getLanguages() as $lang) {
            $names[$lang['id_lang']] = $this->l('Lunu refund');
        }
        $tab->name = $names;
        $tab->add();

        $order_pending = new \OrderState();
        $order_pending->name = array_fill(0, 10, 'Awaiting Lunu payment');
        $order_pending->send_email = 0;
        $order_pending->invoice = 0;
        $order_pending->color = 'RoyalBlue';
        $order_pending->unremovable = false;
        $order_pending->logable = 0;

        $order_pending_id = (int)\Configuration::get('PS_OS_BANKWIRE');
        if ($order_pending->add()) {
            $order_pending_id = (int)$order_pending->id;
            copy(
                _PS_ROOT_DIR_ . '/modules/lunu/logo.png',
                _PS_ROOT_DIR_ . '/img/os/' . $order_pending_id . '.gif'
            );
        }



        \Configuration::updateValue('LUNU_AWAITING_PAYMENT', $order_pending_id);

        // \Configuration::updateValue('LUNU_APP_ID', '8ce43c7a-2143-467c-b8b5-fa748c598ddd');
        // \Configuration::updateValue('LUNU_API_SECRET', 'f1819284-031e-42ad-8832-87c0f1145696');
        \Configuration::updateValue('LUNU_PENDING_ENABLED', 'on');
        // \Configuration::updateValue('LUNU_LOG_ENABLED', 'on');

        if (!parent::install()
            || !$this->installDB()
            || !$this->registerHook('actionOrderSlipAdd')
            || !$this->registerHook('actionProductCancel')
            || !$this->registerHook('displayAdminOrder')
            || !$this->registerHook('displayAdminOrderTabOrder')
            || !$this->registerHook('displayAdminOrderTop')
            || !$this->registerHook('displayAdminOrderTabLink')
            || !$this->registerHook('displayAdminOrderTabContent')
            || !$this->registerHook('actionEmailSendBefore')
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('paymentOptions')) {
            return false;
        }

        return true;
    }

    public function installDB()
    {
        return \Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lunu_order` (
            `id_lunu_order` int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `id_payment` VARCHAR(250) NOT NULL,
            `id_cart` VARCHAR(250) NOT NULL,
            `id_order` VARCHAR(250) NOT NULL,
            `amount_paid` FLOAT,
            `date_add` DATETIME,
            `date_upd` DATETIME,
            `data_json` MEDIUMTEXT
          )
          COLLATE=\'utf8_unicode_ci\'
          ENGINE=' . _MYSQL_ENGINE_);
    }

    private function uninstallModuleTab($class_name)
    {
        $idTab = Tab::getIdFromClassName($class_name);

        if ($idTab != 0) {
            $tab = new Tab($idTab);
            $tab->delete();
            return true;
        }

        return false;
    }

    public function uninstall()
    {
        $order_state_pending = new \OrderState(\Configuration::get('LUNU_AWAITING_PAYMENT'));

        return \Configuration::deleteByName('LUNU_APP_ID')
            && \Configuration::deleteByName('LUNU_API_SECRET')
            && \Configuration::deleteByName('LUNU_PAYMENT_TIMEOUT')
            && \Configuration::deleteByName('LUNU_PENDING_ENABLED')
            && \Configuration::deleteByName('LUNU_GIFT_ENABLED')
            && \Configuration::deleteByName('LUNU_LOG_ENABLED')
            && \Configuration::deleteByName('LUNU_COUPON_PREFIX')
            && $order_state_pending->delete()
            && parent::uninstall()
            && $this->uninstallModuleTab('LunuRefund');
    }

    public function getContent()
    {
        $html = '';
        if (\Tools::isSubmit('btnSubmit')) {
            $postErrors = array();
            if (empty(\Tools::getValue('LUNU_API_SECRET')) || empty(\Tools::getValue('LUNU_APP_ID'))) {
                $postErrors[] = $this->l('API Auth Token is required.');
            }
            if (count($postErrors) < 1) {
                \Configuration::updateValue('LUNU_SANDBOX_ENABLED', \Tools::getValue('LUNU_SANDBOX_ENABLED'));
                \Configuration::updateValue(
                    'LUNU_API_SECRET',
                    trim($this->stripString(\Tools::getValue('LUNU_API_SECRET')))
                );
                \Configuration::updateValue(
                    'LUNU_APP_ID',
                    trim($this->stripString(\Tools::getValue('LUNU_APP_ID')))
                );

                $lunu_payment_timeout = (int)$this->stripString(\Tools::getValue('LUNU_PAYMENT_TIMEOUT'));
                if ($lunu_payment_timeout < 0) {
                    $lunu_payment_timeout = 0;
                }
                \Configuration::updateValue(
                    'LUNU_PAYMENT_TIMEOUT',
                    $lunu_payment_timeout
                );
                \Configuration::updateValue('LUNU_PENDING_ENABLED', \Tools::getValue('LUNU_PENDING_ENABLED'));
                \Configuration::updateValue('LUNU_GIFT_ENABLED', \Tools::getValue('LUNU_GIFT_ENABLED'));
                \Configuration::updateValue('LUNU_LOG_ENABLED', \Tools::getValue('LUNU_LOG_ENABLED'));
                \Configuration::updateValue(
                    'LUNU_COUPON_PREFIX',
                    trim($this->stripString(\Tools::getValue('LUNU_COUPON_PREFIX')))
                );

                $html .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                foreach ($postErrors as $err) {
                    $html .= $this->displayError($err);
                }
            }
        }

        $context = $this->context;
        $controller = $context->controller;
        $path = $this->_path;
        $controller->addCSS($path . '/views/css/tabs.css', 'all');
        $controller->addJS($path . '/views/js/javascript.js', 'all');
        $context->smarty->assign('form', $this->renderForm());
        $html .= $this->display(__FILE__, 'information.tpl');

        return $html;
    }

    public function hookPayment($params)
    {
        if (_PS_VERSION_ >= 1.7) {
            return;
        }
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $path = $this->_path;

        $this->smarty->assign(array(
            'this_path' => $path,
            'this_path_bw' => $path,
            'this_path_ssl' => \Tools::getShopDomainSsl(true, true)
                . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookActionEmailSendBefore($params)
    {
//        if ($params['template'] === 'order_conf') {
//            return false;
//        }
        return true;
    }

    public function hookDisplayOrderConfirmation($params)
    {
        if (_PS_VERSION_ <= 1.7) {
            return;
        }
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $path = $this->_path;

        $this->smarty->assign(array(
            'this_path' => $path,
            'this_path_bw' => $path,
            'this_path_ssl' => \Tools::getShopDomainSsl(true, true)
                . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));

        return $this->context->smarty->fetch(__FILE__, 'payment.tpl');
    }


    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        if (_PS_VERSION_ < 1.7) {
            $order = $params['objOrder'];
            $state = $order->current_state;
        } else {
            $state = $params['order']->getCurrentState();
        }
        $this->smarty->assign(array(
            'state' => $state,
            'paid_state' => (int)\Configuration::get('PS_OS_PAYMENT'),
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => \Tools::getShopDomainSsl(true, true)
                . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        return $this->display(__FILE__, 'payment_return.tpl');
    }


    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $context = $this->context;
        $smarty = $context->smarty;

        $errors = \Tools::getValue('errors');
        if (!is_array($errors)) {
            $errors = array();
        }

        $error_message = '';
        if (!empty($errors) && count($errors) > 0) {
            foreach ($errors as $error_msg) {
                $error_message .= $error_msg . '\n';
            }
        }

        $smarty->assign('lunu_error_message', $error_message);

        $newOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $newOption->setCallToActionText('Lunu Pay with Bitcoin and other cryptocurrencies')
            ->setAction($context->link->getModuleLink($this->name, 'redirect', array(), true))
            ->setAdditionalInformation(
                $smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/lunu_intro.tpl')
            );

        $payment_options = array($newOption);
        return $payment_options;
    }

    public function hookActionOrderSlipAdd($params)
    {
        if (\Tools::isSubmit('doStandardRefundLunu')) {
            return;
        }
        $order = $params['order'];
        $with_credit = \Tools::isSubmit('doPartialRefundLunu');
        $refund_voucher = (int)\Tools::getValue('refund_voucher_off');
        $refund_voucher_choose = \Tools::getValue('refund_voucher_choose');

        $refund_partial_amount = 0;
        if (\Tools::getValue('partialRefundShippingCost')) {
            $refund_partial_amount += \Tools::getValue('partialRefundShippingCost');
        }

        \Lunu\Lunu::lunu_log('partialRefund', array(
            'refund_partial_amount' => $refund_partial_amount,
            'refund_voucher' => $refund_voucher,
            'refund_voucher_choose' => $refund_voucher_choose
        ));

        \Lunu\Lunu::config(\Configuration::getMultiple(\Lunu\Lunu::CONFIG_KEYS));

        if ($refund_voucher == 0) {
            $product_amount = 0;
            $productList = $params['productList'];
            foreach ($productList as $product) {
                $product_amount += $product['amount'];
            }
            $refund_partial_amount += $product_amount;
            \Lunu\Lunu::refund(array(
                'order' => $order,
                'type' => 'partial',
                'refund_amount' => $refund_partial_amount,
                'with_credit' => $with_credit
            ), $this->context->smarty);
        } elseif ($refund_voucher == 1) {
            \Lunu\Lunu::refund(array(
                'order' => $order,
                'type' => 'partial',
                'without_already_refunded' => 1,
                'with_credit' => $with_credit
            ), $this->context->smarty);
        } else {
            $refund_partial_amount += $refund_voucher_choose;
            \Lunu\Lunu::refund(array(
                'order' => $order,
                'type' => 'partial',
                'refund_amount' => $refund_partial_amount,
                'with_credit' => $with_credit
            ), $this->context->smarty);
        }
    }

    public function hookActionProductCancel($params)
    {
        $order = $params['order'];
        $with_credit = \Tools::isSubmit('doStandardRefundLunu')
            && \Tools::isSubmit('generateCreditSlip');
        $refund_total_voucher = (int)\Tools::getValue('refund_total_voucher_off');
        $refund_total_voucher_choose = \Tools::getValue('refund_total_voucher_choose');

        \Lunu\Lunu::lunu_log('hookActionProductCancel', array(
            'refund_total_voucher' => $refund_total_voucher,
            'refund_total_voucher_choose' => $refund_total_voucher_choose
        ));

        \Lunu\Lunu::config(\Configuration::getMultiple(\Lunu\Lunu::CONFIG_KEYS));

        if ($refund_total_voucher == 0) {
            \Lunu\Lunu::refund(array(
                'order' => $order,
                'type' => 'full',
                'with_credit' => $with_credit
            ), $this->context->smarty);
        } elseif ($refund_total_voucher == 1) {
            \Lunu\Lunu::refund(array(
                'order' => $order,
                'type' => 'full',
                'without_already_refunded' => 1,
                'with_credit' => $with_credit
            ), $this->context->smarty);
        } elseif ($refund_total_voucher == 2) {
            \Lunu\Lunu::refund(array(
                'order' => $order,
                'type' => 'full',
                'real_partial' => 1,
                'refund_amount' => $refund_total_voucher_choose,
                'with_credit' => $with_credit
            ), $this->context->smarty);
        }
    }

    public function hookDisplayAdminOrder($params)
    {
        // Since Ps 1.7.7 this hook is displayed at bottom of a page and we should use a hook DisplayAdminOrderTop
        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            return false;
        }

        $order_id = $params['id_order'];
        $order = new \Order((int) $order_id);

        if (\Lunu\Lunu::isInvalidPayment($order)) {
            return false;
        }

        $params['order'] = $order;

        $return = '';
        $return .= $this->getAdminOrderPageMessages($params);
        $return .= $this->getPartialRefund();
        return $return;
    }

    protected function getPartialRefund()
    {
        $context = $this->context;
        $smarty = $context->smarty;

        $smarty->assign(array(
            'lunu_refund_submit_url' => $context->link->getAdminLink('LunuRefund') . '&' . http_build_query(array(
                'id_order' => \Tools::getValue('id_order')
            )),
            'chb_lunu_refund' => $this->l('Refund on Lunu'),
            'partial_refund_enable' => _PS_VERSION_ >= '1.7' ? 'true' : 'false'
        ));
        return $smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/partialRefund.tpl');
    }

    public function hookDisplayAdminOrderTop($params)
    {
        $order_id = $params['id_order'];
        $order = new \Order((int) $order_id);

        if (\Lunu\Lunu::isInvalidPayment($order)) {
            return false;
        }

        $params['order'] = $order;

        $return = '';
        $return .= $this->getAdminOrderPageMessages($params);
        $return .= $this->getPartialRefund();

        return $return;
    }

    public function hookDisplayAdminOrderTabOrder($params)
    {
        // ...
        return '';
    }

    public function hookDisplayAdminOrderTabLink($params)
    {
        // ...
        return '';
    }

    public function hookDisplayAdminOrderTabContent($params)
    {
        // ...
        return '';
    }

    public function hookDisplayAdminOrderContentOrder($params)
    {
        // ...
        return '';
    }


    protected function getAdminOrderPageMessages($params)
    {
        $order_id = $params['id_order'];
        $order = $params['order'];

        if (\Lunu\Lunu::isInvalidPayment($order)) {
            return '';
        }

        $lunu_order = \Lunu\LunuOrder::loadByOrderId($order_id);
        $inner = '';
        $data = null;

        $context = $this->context;
        $smarty = $context->smarty;

        if (empty($lunu_order)) {
            $error_message = 'Payment not found';
            \Lunu\Lunu::addWarnMessage($error_message);
            \Lunu\Lunu::lunu_log($error_message . ' on message', array(
                'lunu_order' => $lunu_order
            ));
        } else {
            $payment_id = $lunu_order->id_payment;
            $amount_paid = $lunu_order->amount_paid;
            $data = $lunu_order->getMetaData();

            $already_refunded_amount = empty($data['already_refunded_amount'])
                ? 0
                : $data['already_refunded_amount'];

            $refund_count = empty($data['refund_count'])
                ? 0
                : $data['refund_count'];

            $history = isset($data['history'])
                ? $data['history']
                : array();


            $refunds_history = array();
            foreach ($history as $item) {
                $amount_too_big = false;
                $error_message = '';
                $iban = '';
                $purpose = '';
                $fiat_amount = 0;
                if (!empty($item['response'])) {
                    $response = $item['response'];

                    if (empty($response['error'])) {
                        $response = empty($response['response']) ? array() : $response['response'];
                        $purpose = empty($response['purpose']) ? '' : $response['purpose'];
                        $iban = empty($response['iban']) ? '' : $response['iban'];
                        $amount_too_big = empty($response['amount_too_big']) ? false : true;
                        $fiat_amount = empty($response['fiat_amount']) ? '' : $response['fiat_amount'];
                    } else {
                        $error = $response['error'];
                        $error_message = empty($error['message']) ? '' : $error['message'];
                    }
                }
                $refunds_history[] = array(
                    'time' => $item['time'],
                    'amount' => $item['amount'],
                    'type' => $item['type'],
                    'iban' => $iban,
                    'purpose' => $purpose,
                    'amount_too_big' => $amount_too_big,
                    'fiat_amount' => $fiat_amount,
                    'error_message' => $error_message
                );
            }

            $smarty->assign(array(
                'payment_id' => $payment_id,
                'amount_paid' => $amount_paid,
                'already_refunded_amount' => $already_refunded_amount,
                'refund_count' => $refund_count,
                'refunds_history' => $refunds_history
            ));
            $inner = $smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/payment_details.tpl');
        }

        $lunu_refunded_messages = \Lunu\Lunu::$lunu_refunded_messages;
        if (empty($lunu_refunded_messages)) {
            $lunu_refunded_messages = array();
        }

        if (is_array($data)) {
            $_refunded_messages = empty($data['messages']) ? null :$data['messages'];
            if (is_array($_refunded_messages)) {
                unset($data['messages']);
                $lunu_order->setMetaData($data);
                $lunu_order->lunu_save();
                $lunu_refunded_messages = array_merge($lunu_refunded_messages, $_refunded_messages);
            }
        }

        if (count($lunu_refunded_messages) < 1 && $inner === '') {
            return '';
        }

        foreach ($lunu_refunded_messages as $messageData) {
            $messageType = $messageData['type'];
            $message = $messageData['message'];

            if ($messageType === 'warn') {
                $inner .= $this->displayWarning($message);
            } elseif ($messageType === 'danger') {
                $inner .= $this->displayError($message);
            } elseif ($messageType === 'success') {
                $inner .= $this->displaySuccess($message);
            } else {
                $inner .= $this->displayInformation($message);
            }
        }

        $smarty->assign('inner', $inner);
        return $smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/order_info.tpl');
    }

    public function displayInformation($message)
    {
        return $this->displayAlert($message, 'info');
    }

    public function displayError($message)
    {
        return $this->displayAlert($message, 'danger');
    }

    public function displayWarning($message)
    {
        return $this->displayAlert($message, 'warning');
    }

    public function displaySuccess($message)
    {
        return $this->displayAlert($message, 'success');
    }

    public function displayAlert($message, $type)
    {
        $smarty = $this->context->smarty;
        $smarty->assign(array(
            'message' => $message,
            'type' => $type
        ));
        return $smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/alert.tpl');
    }

    public function checkCurrency($cart)
    {
        $id_currency = $cart->id_currency;
        $currency_order = new \Currency($id_currency);
        $currency_order_id = $currency_order->id;
        $currencies_module = $this->getCurrency($id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order_id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function renderForm()
    {
        $form_input = array(
            array(
                'type' => 'checkbox',
                'label' => $this->l('Sandbox mode'),
                'name' => 'LUNU_SANDBOX',
                'desc' => $this->l(''),
                'required' => false,
                'values' => array(
                    'query' => array(
                        array(
                            'id_option' => 'ENABLED',
                            'name' => 'on'
                        )
                    ),
                    'id' => 'id_option',
                    'name' => 'name'
                )
            ),
            array(
                'type' => 'text',
                'label' => $this->l('App ID'),
                'name' => 'LUNU_APP_ID',
                'desc' => $this->l('Your App ID'),
                'required' => true
            ),
            array(
                'type' => 'text',
                'label' => $this->l('API Secret'),
                'name' => 'LUNU_API_SECRET',
                'desc' => $this->l('Your API Secret Token'),
                'required' => true
            ),
            array(
                'type' => 'text',
                'label' => $this->l('Payment timeout, minutes'),
                'name' => 'LUNU_PAYMENT_TIMEOUT',
                'desc' => $this->l(''),
                'required' => false
            ),
            array(
                'type' => 'checkbox',
                'label' => $this->l('Allow pending orders'),
                'name' => 'LUNU_PENDING',
                'desc' => $this->l('Creation of orders with a pending status before proceeding to the payment process'),
                'required' => false,
                'values' => array(
                    'query' => array(
                        array(
                            'id_option' => 'ENABLED',
                            'name' => 'on'
                        )
                    ),
                    'id' => 'id_option',
                    'name' => 'name'
                )
            ),
            array(
                'type' => 'checkbox',
                'label' => $this->l('Enable Lunu Gift'),
                'name' => 'LUNU_GIFT',
                'desc' => $this->l('Enable payments by Lunu Gifts if you are marketing partners of Lunu'),
                'required' => false,
                'values' => array(
                    'query' => array(
                        array(
                            'id_option' => 'ENABLED',
                            'name' => 'on'
                        )
                    ),
                    'id' => 'id_option',
                    'name' => 'name'
                )
            ),
            array(
                'type' => 'checkbox',
                'label' => $this->l('Enable logs'),
                'name' => 'LUNU_LOG',
                'desc' => $this->l(''),
                'required' => false,
                'values' => array(
                    'query' => array(
                        array(
                            'id_option' => 'ENABLED',
                            'name' => 'on'
                        )
                    ),
                    'id' => 'id_option',
                    'name' => 'name'
                )
            )
        );
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Lunu'),
                    'icon' => 'icon-bitcoin',
                ),
                'input' => $form_input,
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $context = $this->context;
        $helper = new \HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new \Language((int)\Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = (
            \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
                ? \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
                : 0
        );
        $this->fields_form = array();
        $helper->id = (int)\Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module='
            . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => array(
                'LUNU_SANDBOX_ENABLED' => \Tools::getValue(
                    'LUNU_SANDBOX_ENABLED',
                    \Configuration::get('LUNU_SANDBOX_ENABLED')
                ),
                'LUNU_APP_ID' => $this->stripString(\Tools::getValue(
                    'LUNU_APP_ID',
                    \Configuration::get('LUNU_APP_ID')
                )),
                'LUNU_API_SECRET' => $this->stripString(\Tools::getValue(
                    'LUNU_API_SECRET',
                    \Configuration::get('LUNU_API_SECRET')
                )),
                'LUNU_PAYMENT_TIMEOUT' => \Tools::getValue(
                    'LUNU_PAYMENT_TIMEOUT',
                    \Configuration::get('LUNU_PAYMENT_TIMEOUT')
                ),
                'LUNU_PENDING_ENABLED' => \Tools::getValue(
                    'LUNU_PENDING_ENABLED',
                    \Configuration::get('LUNU_PENDING_ENABLED')
                ),
                'LUNU_GIFT_ENABLED' => \Tools::getValue(
                    'LUNU_GIFT_ENABLED',
                    \Configuration::get('LUNU_GIFT_ENABLED')
                ),
                'LUNU_LOG_ENABLED' => \Tools::getValue(
                    'LUNU_LOG_ENABLED',
                    \Configuration::get('LUNU_LOG_ENABLED')
                ),
                'LUNU_COUPON_PREFIX' => \Tools::getValue(
                    'LUNU_COUPON_PREFIX',
                    \Configuration::get('LUNU_COUPON_PREFIX')
                )
            ),
            'languages' => $context->controller->getLanguages(),
            'id_language' => $context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    private function stripString($item)
    {
        return preg_replace('/\s+/', '', $item);
    }
}
