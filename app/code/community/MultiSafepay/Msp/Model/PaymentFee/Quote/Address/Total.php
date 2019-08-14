<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Model_PaymentFee_Quote_Address_Total extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code        = 'paymentFee';
    protected $_tempAddress = '';
    protected $_method      = '';
    protected $_rate        = '';
    protected $_collection  = '';

    /**
     *
     */
    protected function _construct()
    {
        $this->setCode('paymentFee');
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this|Mage_Sales_Model_Quote_Address_Total_Abstract
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
		if(Mage::app()->getFrontController()->getRequest()->isSecure())
			$protocol ='https://';
		else{
			$protocol ='http://';
		}

        //$protocol   = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === FALSE ? 'http://' : 'https://';
        $currentUrl = $protocol . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

        if ($currentUrl != Mage::helper('checkout/cart')->getCartUrl()) {
            // $gateway_data = Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getData();
            // $gateway_data = $address->getQuote()->getPayment()->getData();

            /** @var $quote Mage_Sales_Model_Quote */
            $quote = $address->getQuote();
            $code  = $quote->getPayment()->getMethod();
			
			
			if($code == '')
			{
				if(isset($_POST['payment']['method']))
				{
					$code = $_POST['payment']['method'];
				}
			}

            if (!empty($code)) {
                if ($code == 'msp_payafter') {
                    if (Mage::getStoreConfig('msp/msp_payafter/fee', $quote->getStoreId())) {
                        if ($address->getBasePaymentFee() == 0) 
						{
                            $this->_tempAddress = $address;
                            $this->_method = $this->_tempAddress->getQuote()->getPayment()->getMethod();
                            $this->_tempAddress->setBasePaymentFee(0);
                            $this->_tempAddress->setPaymentFee(0);
                            $this->_tempAddress->setBasePaymentFeeTax(0);
                            $this->_tempAddress->setPaymentFeeTax(0);

                            $items = $address->getAllItems();
                            if (!count($items)) {
                                return $this;
                            }

                            $baseFee = $this->_getBaseFee();
                            $fee = $this->_getFee();

                            if ($baseFee == 0) {
                                return $this;
                            }

                            $baseFeeTax = $this->_calulateTaxForFee($baseFee, true);
                            $feeTax     = $this->_calulateTaxForFee($baseFee);

                            $this->_tempAddress->setBasePaymentFee($baseFee - $baseFeeTax);
                            $this->_tempAddress->setPaymentFee($fee - $feeTax);
                            $this->_tempAddress->getPaymentFee();
                            $this->_tempAddress->setBasePaymentFeeTax($baseFeeTax);
                            $this->_tempAddress->setPaymentFeeTax($feeTax);

                            /* if (Mage::helper('msp')->isEnterprise()) {
                                $this->_tempAddress->setBaseGrandTotal($this->_tempAddress->getBaseGrandTotal() + $baseFee);
                                $this->_tempAddress->setGrandTotal($this->_tempAddress->getGrandTotal() + $fee);
                            }*/

                            $this->_tempAddress->setTaxAmount($this->_tempAddress->getTaxAmount() + $feeTax);
                            $this->_tempAddress->setBaseTaxAmount($this->_tempAddress->getBaseTaxAmount() + $baseFeeTax);

                            $this->_setAddress($this->_tempAddress);
                            $this->_setBaseAmount($baseFee);
                            $this->_setAmount($fee);

                            //if (Mage::getStoreConfig('msp/msp_payafter/fee_duplicate', $quote->getStoreId())) {
                                $this->_tempAddress->setGrandTotal($this->_tempAddress->getGrandTotal() + $fee);
                                $this->_tempAddress->setBaseGrandTotal($this->_tempAddress->getBaseGrandTotal() + $fee);
                           // }

                            $this->_addFeeTaxToAppliedTaxes(
                                $address,
                                $feeTax,
                                $baseFeeTax,
                                $this->_getRate()
                            );

                            return $this;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this|array
     */
   public function fetch(Mage_Sales_Model_Quote_Address $address)
    {  
		if($address->getBasePaymentFee() >= '0.00')  {       
			$this->_method = $address->getQuote()->getPayment()->getMethod();
			if(Mage::app()->getFrontController()->getRequest()->isSecure())
				$protocol ='https://';
			else{
				$protocol ='http://';
			}
			//$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === FALSE ? 'http://' : 'https://';
        
			$currentUrl = $protocol . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		
        
			if (($address->getShippingAmount() != 0 || $address->getShippingDescription()) && $address->getBasePaymentFee() > 0.01 && $currentUrl != Mage::helper('checkout/cart')->getCartUrl()) 
			{
				$label = Mage::helper('msp')->getFeeLabel($this->_method);
       	      
				//$gateway_data = Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getData();
				if($this->_method == 'msp_payafter')
				{
					$address->addTotal(
							array(
							'code'  => 'paymentFee',
							'title' => $label,
							'value' => $address->getBasePaymentFee(),
						)
					);
				}
			}
			return $this;
		}
    }

    /**
     * @return float
     */
    private function _getBaseFee()
    {
        $fee = Mage::getStoreConfig('msp/msp_payafter/fee_amount');
        $fee = str_replace(',', '.', $fee);

        if (strpos($fee, '%') !== false) {
            $feePercentage = str_replace('%', '', $fee);
            
            $quote = Mage::getModel('sales/quote');
            $quote->load($this->_tempAddress->getQuote()->getId());
            
            //calculate the fee. If the fee has already been added, remove it to prevent it from being taken into account in the calculation
            if ($quote->getBasePaymentFee()) {
                $fee = ($quote->getBaseGrandTotal() - $quote->getBasePaymentFee()) * ($feePercentage / 100);
            } elseif (!$quote->getBaseGrandTotal()) {
                $grandTotal = Mage::registry('msp_quote_basegrandtotal');
                $fee = $grandTotal * ($feePercentage / 100);
            } else {
                $fee = $quote->getBaseGrandTotal() * ($feePercentage / 100);
            }
        }

        return (float) $fee;
    }

    /**
     * @return float
     */
    private function _getFee()
    {
        $baseFee = $this->_getBaseFee();
        $quoteConvertRate = $this->_tempAddress->getQuote()->getBaseToQuoteRate();
        $fee = $baseFee * $quoteConvertRate;

        return (float) $fee;
    }
    
    protected function _getRate()
    {
        $quote = $this->_tempAddress->getQuote();
        $taxClass = Mage::getStoreConfig('msp/msp_payafter/fee_tax_class');
         if ($taxClass == 0) {
            $this->_rate = 1;
            return;
        }
        
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        
        $request = $taxCalculationModel->getRateRequest(
            $quote->getShippingAddress(),
            $quote->getBillingAddress(),
            $quote->getCustomerTaxClassId(),
            Mage::app()->getStore()->getId()
        );
        $request->setStore(Mage::app()->getStore())->setProductClassId($taxClass);
        
        $rate = $taxCalculationModel->getRate($request);

        return $rate;
    }
    
    private function _calulateTaxForFee($fee, $isBase = false)
    {
        $bigfee = 100 + $this->_getRate();
        // $tax = $fee * ($this->_getRate())- $fee;

        $tax = (Mage::getStoreConfig('msp/msp_payafter/fee_amount')/$bigfee)*$this->_getRate();

        if (!$isBase) {
            $quoteconvertRate = $this->_tempAddress->getQuote()->getBaseToQuoteRate();
            $tax *= $quoteconvertRate;
        }

        return $tax;
    }
    
    protected function _addFeeTaxToAppliedTaxes(Mage_Sales_Model_Quote_Address $address, $amount, $baseAmount, $taxRate)
    {
        $previouslyAppliedTaxes = $address->getAppliedTaxes();
        $applied = false;
        
        foreach ($previouslyAppliedTaxes as &$row) {
            foreach ($row['rates'] as $rate) {
                if ($rate['percent'] == $taxRate) {
                    $row['amount'] += $amount;
                    $row['base_amount'] += $baseAmount;
                    $applied = true;
                    break 2;
                } else {
                    continue;
                }
            }
        }
 
        if (false === $applied) {
            $previouslyAppliedTaxes['PaymentFeeTax'] = array(
                'rates' => array(array(
                    'code'     => 'PaymentFeeTax',
                    'title'    => 'MultiSafepay Servicekosten Tax',
                    'percent'  => (float) $taxRate,
                    'position' => '0',
                    'priority' => '1',
                    'rule_id'  => '2',
                )),
                'percent'     => (float) $taxRate,
                'id'          => 'PaymentFeeTax',
                'process'     => 0,
                'amount'      => $amount,
                'base_amount' => $baseAmount,
            );
        }
      
        $address->setAppliedTaxes($previouslyAppliedTaxes); 
    }
}