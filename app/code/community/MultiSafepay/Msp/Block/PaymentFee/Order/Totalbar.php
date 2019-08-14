<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Block_PaymentFee_Order_Totalbar extends Mage_Core_Block_Abstract
{
    /**
     * @return $this
     */
    public function initTotals()
    {
        $parent          = $this->getParentBlock();
        $this->_invoice  = $parent->getInvoice();
         
        if (($this->_invoice->getPaymentFee() < 0.01 || $this->_invoice->getPaymentFee() < 0.01) &&
            ($this->_invoice->getOrder()->getBasePaymentFee() - $this->_invoice->getOrder()->getBasePaymentFeeInvoiced()) < 0.01) {

            return $this;
        }
        
        $paymentmethodCode = $this->_invoice->getOrder()->getPayment()->getMethod();
        $feeLabel = Mage::helper('msp')->getfeeLabel($this->_invoice->getOrder());
        
        $paymentFee = new Varien_Object();
        $paymentFee->setLabel($feeLabel);
        $paymentFee->setValue($this->_invoice->getPaymentFee());
        $paymentFee->setBaseValue($this->_invoice->getBasePaymentFee());
        $paymentFee->setCode('payment_fee');
        
        $parent->addTotalBefore($paymentFee, 'tax');

        return $this;
    }
}