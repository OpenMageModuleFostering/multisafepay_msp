<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Block_PaymentFee_Order_Totals_Tax extends Mage_Adminhtml_Block_Sales_Order_Totals_Tax
{
    /**
     * Get full information about taxes applied to order
     *
     * @return array
     */
    public function getFullTaxInfo()
    {
        /** @var $source Mage_Sales_Model_Order */
        $source = $this->getOrder();

        $taxClassAmount = array();
        if ($source instanceof Mage_Sales_Model_Order) {
             //check from what version of Magento this functin exists and add a check for it! This will give an empty order page in backend on error
            $taxClassAmount = Mage::helper('tax')->getCalculatedTaxes($source);

            if (empty($taxClassAmount)) {
                $rates          = Mage::getModel('sales/order_tax')->getCollection()->loadByOrder($source)->toArray();
                $taxClassAmount =  Mage::getSingleton('tax/calculation')->reproduceProcess($rates['items']);
            } else {
                $shippingTax    = Mage::helper('tax')->getShippingTax($source);
                if ($source->getBasePaymentFeeTax()) {
                    $paymentFeeTax = array(array(
                        'tax_amount'      => $source->getPaymentFeeTax(),
                        'base_tax_amount' => $source->getBasePaymentFeeTax(),
                        'title'           => Mage::helper('msp')->getfeeLabel($this->_order->getPayment()->getMethod()). 'Tax',
                        'percent'         => NULL,
                    ));
                    $taxClassAmount = array_merge($shippingTax, $paymentFeeTax, $taxClassAmount);
                } else {
                    $taxClassAmount = array_merge($shippingTax, $taxClassAmount);
                }
            }
        }

        return $taxClassAmount;
    }
}
