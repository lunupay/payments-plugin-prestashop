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
/**
 * Class LunuOrder.
 */
class LunuOrder extends \ObjectModel
{
    /** @var integer Prestashop Order generated ID */
    public $id_order;

    /** @var integer Prestashop Cart generated ID */
    public $id_cart;

    /** @var string Payment ID */
    public $id_payment;

    /** @var float Total paid amount by customer */
     public $amount_paid;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /** @var string JSON data */
    public $data_json;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'lunu_order',
        'primary' => 'id_lunu_order',
        'multilang' => false,
        'fields' => array(
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_payment' => array('type' => self::TYPE_STRING),
            'amount_paid' => array('type' => self::TYPE_FLOAT, 'size' => 10, 'scale' => 2),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'data_json' => array('type' => self::TYPE_STRING, 'validate' => 'isString')
        ),
        'collation' => 'utf8_general_ci'
    );

    /**
     * Get LunuOrder by PrestaShop order ID
     * @param integer $id_order Order ID
     * @return array LunuOrder
     */
    public static function getOrderById($id_order) {
        $query = new \DBQuery();
        $query->from('lunu_order');
        $query->where('id_order = ' . (int)$id_order);
        $rowOrder = \Db::getInstance()->getRow($query);

        if (is_array($rowOrder)) {
            return $rowOrder;
        } else {
            return array();
        }
    }

    /**
     * Load LunuOrder object by PrestaShop order ID
     * @param integer $id_order Order ID
     * @return object LunuOrder
     */
    public static function loadByOrderId($id_order) {
        $sql = new \DbQuery();
        $sql->select('id_lunu_order');
        $sql->from('lunu_order');
        $sql->where('id_order = ' . (int)$id_order);
        $id_lunu_order = \Db::getInstance()->getValue($sql);
        return new self($id_lunu_order);
    }

    public function getMetaData() {
        $data = json_decode($this->data_json, true);
        return is_array($data) ? $data : array();
    }
    public function setMetaData($data) {
        $this->data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function lunu_add() {
        // Use parameterized query for better security
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "lunu_order` (
            `id_payment`, `id_cart`, `id_order`, `amount_paid`, `data_json`, `date_add`, `date_upd`
        ) VALUES ('" . pSQL($this->id_payment) . "', '"
            . (int)$this->id_cart . "', '" . (int)$this->id_order . "', '"
            . (float)$this->amount_paid . "', '" . pSQL($this->data_json) . "', NOW(), NOW())";
        
        if (\Db::getInstance()->execute($sql)) {
            $this->id = \Db::getInstance()->Insert_ID();
        }
        return $this;
    }

    public function lunu_save() {
        // Use parameterized query for better security
        $sql = "UPDATE `" . _DB_PREFIX_ . "lunu_order`
        SET
            `id_payment` = '" . pSQL($this->id_payment) . "',
            `id_cart` = '" . (int)$this->id_cart . "',
            `id_order` = '" . (int)$this->id_order . "',
            `amount_paid` = '" . (float)$this->amount_paid . "',
            `data_json` = '" . pSQL($this->data_json) . "',
            `date_upd` = NOW()
        WHERE `id_lunu_order` = " . (int)$this->id;
        
        \Db::getInstance()->execute($sql);
        return $this;
    }

}
