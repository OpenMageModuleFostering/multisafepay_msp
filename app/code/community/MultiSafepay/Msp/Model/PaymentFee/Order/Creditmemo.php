<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Model_PaymentFee_Order_Creditmemo extends Mage_Sales_Model_Order_Creditmemo
{
    /**
     * @return $this|void
     */
    public function refund()
    {
        Mage::dispatchEvent('paymentfee_order_creditmemo_refund_before', array($this->_eventObject => $this));
        parent::refund();
    }
}