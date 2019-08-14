<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Block_PaymentFee_Order_Totals_Paymentfee extends Mage_Core_Block_Abstract
{
    public function initTotals()
    {
        $parent        = $this->getParentBlock();
        $this->_order  = $parent->getOrder();
     
        if ($this->_order->getPaymentFee() < 0.01 || $this->_order->getPaymentFee() < 0.01) {
            return $this;
        }
        
        $paymentmethodCode = $this->_order->getPayment()->getMethod();
        $feeLabel = Mage::helper('msp')->getfeeLabel($this->_order->getPayment()->getMethod());
        
        $paymentFee = new Varien_Object();
        $paymentFee->setLabel($feeLabel);
        $paymentFee->setValue($this->_order->getPaymentFee());
        $paymentFee->setBaseValue($this->_order->getBasePaymentFee());
        $paymentFee->setCode('payment_fee');
  
        $parent->addTotalBefore($paymentFee, 'tax');

        return $this;
    }
}