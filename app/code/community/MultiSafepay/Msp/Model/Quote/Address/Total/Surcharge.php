<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Model_Quote_Address_Total_Surcharge extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    /**
     *
     */
    public function __construct()
    {
        $this->setCode('msp');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {        
        $amount = $address->getShippingAmount();
        if ($amount != 0 || $address->getShippingDescription()) {
                        
            $address->setSurchargeAmount($this->getSurchargeAmount());
            $address->setSurchargeTaxAmount($this->getSurchargeTaxAmount());
            
            $address->setSurcharge($this->getSurchargeAmount());
            $address->getQuote()->setData('surcharge', $this->getSurchargeAmount());
            $address->getQuote()->setData('surcharge_tax', $this->getSurchargeTaxAmount());
            
            $address->setTaxAmount($address->getTaxAmount() + $address->getSurchargeTaxAmount());
            $address->setBaseTaxAmount(
                $address->getBaseTaxAmount() + $address->getSurchargeTaxAmount()
            );
            $address->setSubtotal($address->getSubtotal() + $this->getSurchargeAmount(true));
            $address->setBaseSubtotal(
                $address->getBaseSubtotal() + $this->getSurchargeAmount(true)
            );
            $address->setGrandTotal($address->getGrandTotal() + $address->getSurchargeAmount());
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getSurchargeAmount());
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this|array
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getShippingAmount();
        if ($amount != 0 || $address->getShippingDescription()) {
            if ($address->getSurchargeAmount()) {
                $address->addTotal(array(
                    'code'  => $this->getCode(),
                    'title' => $this->getSurchargeTitle(),
                    'value' => $address->getSurchargeAmount()
                ));
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getSurchargeTitle()
    {
        $title = "Payment Fee";
        return $title;
    }

    /**
     * @return float
     */
    public function getSurchargeAmount()
    {
        $amount = 0.0;

        /** @var $quote Mage_Sales_Model_Quote */
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $code  = $quote->getPayment()->getMethod();

        if (!empty($code)) {
            if ($code == 'msp_payafter') {
                if (Mage::getStoreConfig('msp/msp_payafter/fee', $quote->getStoreId())) {
                    $amount = floatval(Mage::getStoreConfig('msp/msp_payafter/fee_amount', $quote->getStoreId()));
                }
            }
        }

        return $amount;
    }

    /**
     * @return float
     */
    public function getSurchargeTaxAmount()
    {
        $taxpercent = (float)21;
        $tax = ($this->getSurchargeAmount()/100)*$taxpercent;

        return $tax;
    }
}