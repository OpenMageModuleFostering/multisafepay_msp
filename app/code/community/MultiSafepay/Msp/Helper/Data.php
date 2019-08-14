<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_MSP_FEE_DESCRIPTION = 'msp/msp_payafter/fee_description';

    const CONVERT_TO_CURRENCY_CODE     = 'EUR';

    /**
     * Get payment icon and title
     *
     * @param $method Mage_Payment_Model_Method_Abstract
     * @return string
     */
    public function getPaymentTitle($method)
    {
        $return = '';

        $paymentCode  = strtolower($method->getCode());
        $paymentTitle = $method->getTitle();

        $isShowImg         = Mage::getStoreConfig('msp/settings/show_gateway_images');
        $isShowImgWithName = Mage::getStoreConfig('msp/settings/show_gateway_title_combi');

        if ($isShowImg || $isShowImgWithName) {
            $fileWithPath = 'msp' . DS . $this->_getLangISO2() . DS . $paymentCode . '.' . 'png';
            $iconFileDir = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . $fileWithPath;
            if (file_exists($iconFileDir)) {
                $iconFileUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $fileWithPath;
                $return = '<img src="' . $iconFileUrl . '" id="'. 'msp_' . $paymentCode .'" title="' . $paymentTitle . '" />&nbsp;';
                if ($isShowImgWithName) {
                    //$return .= '<span class="gateway-title"> - ' . $paymentTitle . '</span>';
                }
            }
        }
		echo $return;
       // return  $return;
    }
	
	
	
	public function getPadIncExcTax(){

		if(Mage::getStoreConfig('msp/msp_payafter/fee_incexc'))
		{
			$including = true;
		}else{
			$including = false;
		}
		return $including;
	}

	public function getCheckoutFee()
	{
		$fee = Mage::getStoreConfig('msp/msp_payafter/fee_amount', Mage::app()->getStore()->getId());
        
      		$fee = str_replace(',', '.', $fee);
		return $fee;
	}
	

    /**
     * Get Fee Description
     *
     * @return string
     */
    public function getFeeLabel()
    {
        $feeDescription = Mage::getStoreConfig(self::XML_PATH_MSP_FEE_DESCRIPTION);

        return $feeDescription ? $feeDescription : $this->__('MultiSafepay servicekosten');
    }

    /**
     * @param Mage_Sales_Model_Order         $order
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return void
     */
    public function resetPaymentFeeInvoicedValues($order, $invoice)
    {
        $basePaymentFee    = $invoice->getBasePaymentFee();
        $paymentFee        = $invoice->getPaymentFee();
        $basePaymentFeeTax = $invoice->getBasePaymentFeeTax();
        $paymentFeeTax     = $invoice->getPaymentFeeTax();

        $basePaymentFeeInvoiced    = $order->getBasePaymentFeeInvoiced();
        $paymentFeeInvoiced        = $order->getPaymentFeeInvoiced();
        $basePaymentFeeTaxInvoiced = $order->getBasePaymentFeeTaxInvoiced();
        $paymentFeeTaxInvoiced     = $order->getPaymentFeeTaxInvoiced();

        if ($basePaymentFeeInvoiced && $basePaymentFee && $basePaymentFeeInvoiced >= $basePaymentFee) {
            $order->setBasePaymentFeeInvoiced($basePaymentFeeInvoiced - $basePaymentFee)
                ->setPaymentFeeInvoiced($paymentFeeInvoiced - $paymentFee)
                ->setBasePaymentFeeTaxInvoiced($basePaymentFeeTaxInvoiced - $basePaymentFeeTax)
                ->setBasePaymentFeeInvoiced($paymentFeeTaxInvoiced - $paymentFeeTax);
            $order->save();
        }
    }


    /**
     * Check are you in the Admin area
     *
     * @return bool
     */
    public function isAdmin()
    {
        if (Mage::app()->getStore()->isAdmin() || Mage::getDesign()->getArea() == 'adminhtml') {
            return true;
        }

        return false;
    }


    /**
     * Check is Magento Enterprise Edition
     *
     * @return bool
     */
    public function isEnterprise()
    {
        return (bool) Mage::getConfig()->getModuleConfig('Enterprise_Enterprise')->version;
    }

    /**
     * Restore
     *
     * @param $quote  Mage_Sales_Model_Quote
     * @param $status string
     * @return bool
     */
    public function restoreCart(Mage_Sales_Model_Quote $quote, $status)
    {
        $storeId       = $quote->getStoreId();
        $gatewayMethod = $quote->getPayment()->getMethod();

        $needRestore   = false;
        $statuses      = array('canceled', 'expired', 'declined', 'void');

        if (Mage::getStoreConfig('payment/msp/keep_cart', $storeId) ||
            Mage::getStoreConfig('msp/settings/keep_cart', $storeId) ||
            $gatewayMethod == 'msp_payafter') {

            $needRestore = true;
        }

        if ($needRestore && in_array($status, $statuses)) {
            $quote->setIsActive(true)
                ->setReservedOrderId(null)
                ->save();

            return true;
        }

        return false;
    }

    /**
     * Get Locale code
     *
     * @return string
     */
    protected function _getLangISO2()
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocale());
        if (is_array($locale) && isset($locale[0])) {
            return strtolower($locale[0]);
        }

        return 'en';
    }

}