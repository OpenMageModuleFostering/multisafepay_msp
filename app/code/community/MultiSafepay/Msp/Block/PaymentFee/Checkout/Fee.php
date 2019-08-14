<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Block_PaymentFee_Checkout_Fee extends Mage_Checkout_Block_Total_Default
{
    protected $_template = 'msp/paymentFee/checkout/fee.phtml';

    /**
     * Get COD fee exclude tax
     *
     * @return float
     */
    public function getPaymentFee()
    {
        $paymentFee = 0;
        foreach ($this->getTotal()->getAddress()->getQuote()->getAllShippingAddresses() as $address) {
            $paymentFee += $address->getPaymentFee();
        }

        return $paymentFee;
    }
}
