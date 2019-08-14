<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Model_PaymentFee_Service_Order extends Mage_Sales_Model_Service_Order
{
    /**
     * Initialize creditmemo state based on requested parameters
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param array $data
     */
    protected function _initCreditmemoData($creditmemo, $data)
    {
        if (isset($data['paymentfee'])) {
            $creditmemo->setPaymentFeeToRefund($data['paymentfee']);
        }
        
        return parent::_initCreditmemoData($creditmemo, $data);
    }
}