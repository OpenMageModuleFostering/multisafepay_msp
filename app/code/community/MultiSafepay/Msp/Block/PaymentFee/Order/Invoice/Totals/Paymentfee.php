<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Block_PaymentFee_Order_Invoice_Totals_Paymentfee extends Mage_Core_Block_Abstract
{
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_invoice  = $parent->getInvoice();
        
		
		if(!$this->_invoice){
			$this->_invoice = Mage::registry('current_invoice');
		}
		
		
        if (($this->_invoice->getPaymentFee() < 0.01 || $this->_invoice->getPaymentFee() < 0.01) &&
            ($this->_invoice->getOrder()->getBasePaymentFee() - $this->_invoice->getOrder()->getBasePaymentFeeInvoiced()) < 0.01) {

            return $this;
        }
        
        $paymentmethodCode = $this->_invoice->getOrder()->getPayment()->getMethod();
        $feeLabel = Mage::helper('msp')->getfeeLabel($paymentmethodCode);
        
        $paymentFee = new Varien_Object();
        $paymentFee->setLabel($feeLabel);
        $paymentFee->setValue($this->_invoice->getPaymentFee());
        $paymentFee->setBaseValue($this->_invoice->getBasePaymentFee());
        $paymentFee->setCode('payment_fee');
        
        $parent->addTotalBefore($paymentFee, 'tax');

        return $this;
    }
}