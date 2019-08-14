<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/** @var $this MultiSafepay_Msp_Model_Setup */
$this->startSetup();

/** @var $conn Varien_Db_Adapter_Pdo_Mysql */
$conn = $this->getConnection();

$additionalColumns = array(
    $this->getTable('sales/order') => array(
        'payment_fee',
        'base_payment_fee',
        'payment_fee_invoiced',
        'base_payment_fee_invoiced',
        'payment_fee_tax',
        'base_payment_fee_tax',
        'payment_fee_tax_invoiced',
        'base_payment_fee_tax_invoiced',
        'payment_fee_refunded',
        'base_payment_fee_refunded',
        'payment_fee_tax_refunded',
        'base_payment_fee_tax_refunded',
    ),
    $this->getTable('sales/invoice') => array(
        'payment_fee',
        'base_payment_fee',
        'payment_fee_tax',
        'base_payment_fee_tax',
    ),
    $this->getTable('sales/quote') => array(
        'payment_fee',
        'base_payment_fee',
        'payment_fee_tax',
        'base_payment_fee_tax',
    ),
    $this->getTable('sales/creditmemo') => array(
        'payment_fee',
        'base_payment_fee',
        'payment_fee_tax',
        'base_payment_fee_tax',
    ),
);

foreach ($additionalColumns as $table => $columns) {
    foreach ($columns as $column) {
        $conn->addColumn($table, $column, array(
            'type'      => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'precision' => 12,
            'scale'     => 4,
            'nullable'  => true,
            'default'   => null,
            'comment'   => ucwords(str_replace('_', ' ', $column)),
        ));
    }
}

$this->endSetup();