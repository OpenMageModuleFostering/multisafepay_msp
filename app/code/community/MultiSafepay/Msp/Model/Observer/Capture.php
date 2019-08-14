<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Model_Observer_Capture extends Mage_Core_Model_Abstract
{
    public function sales_order_invoice_register(Varien_Event_Observer $observer)
    {
        //to prevent the script from running twice
        if (Mage::registry('captureStarted')) {
            Mage::unregister('captureStarted');
            return $this;
        }
        
        return $this->_capture($observer->getOrder(), $observer->getInvoice());
    }
    
    public function sales_order_payment_capture(Varien_Event_Observer $observer)
    {
        Mage::register('captureStarted', 1);
        
        return $this->_capture($observer->getInvoice()->getOrder(), $observer->getInvoice());
    }
    
    public function sales_order_invoice_save_before(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getInvoice();
        
        if (!$invoice->getTransactionId() && $invoice->getOrder()->getMspOrderReference()) {
            $invoice->setTransactionId($invoice->getOrder()->getMspOrderReference());
        }
        
        return $this;
    }
    
    protected function _capture($order, $invoice)
    {    
        try {  
            if ($this->_captureIsAllowed($order, $invoice) !== true) {
                return $this;
            }
        
            $captureRequest = Mage::getModel('msp/request_capture'); 
            $captureRequest->setOrder($order)
                           ->setMethod($order->getPayment()->getMethod())
                           ->setInvoice($invoice);
            
            $result = $captureRequest->sendCaptureRequest();
        } catch (Exception $e) {
            Mage::helper('msp')->resetPaymentFeeInvoicedValues($order, $invoice);
            $invoice->cancel()->save();
            
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::throwException($e->getMessage());
        }
        
        if ($result === false) {
            Mage::helper('msp')->resetPaymentFeeInvoicedValues($order, $invoice);
            $invoice->cancel()->save();
            
            Mage::throwException('Unable to capture this invoice');
        }
        
        return $this;
    }

    /**
     * @param $order   Mage_Sales_Model_Quote
     * @param $invoice Mage_Sales_Model_Order_Invoice
     * @return bool
     */
    protected function _captureIsAllowed($order, $invoice)
    {
        if (!Mage::getStoreConfigFlag('msp/msp_capture/capture_mode', $order->getStoreId())) {
            return false;
        }
        
        if ($invoice->getBaseGrandTotal() - $order->getBaseGrandTotal() > 0.01 ||
            $invoice->getBaseGrandTotal() - $order->getBaseGrandTotal() < -0.01) {

            Mage::throwException('Can only capture full invoices. Partial invoices cannot be captured by MultiSafepay.');
            return false;
        }
        
        if ((isset($_POST['invoice']) && isset($_POST['invoice']['capture_case'])) &&
            $_POST['invoice']['capture_case'] != 'online') {

            return false;
        }
        
        return true;
    }
}