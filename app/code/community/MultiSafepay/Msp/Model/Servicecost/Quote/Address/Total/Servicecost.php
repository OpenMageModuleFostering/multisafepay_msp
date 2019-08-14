<?php

class MultiSafepay_Msp_Model_Servicecost_Quote_Address_Total_Servicecost extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    protected $_method = '';
    protected $_rate = '';
    protected $_collection = '';
    public $availablePaymentMethodCodes = array(
        'msp',
        'mspcheckout',
        'msp_ideal',
        'msp_payafter',
        'msp_mistercash',
        'msp_visa',
        'msp_mastercard',
        'msp_banktransfer',
        'msp_maestro',
        'msp_paypal',
        'msp_amex',
        'msp_webgift',
        'msp_ebon',
        'msp_babygiftcard',
        'msp_boekenbon',
        'msp_erotiekbon',
        'msp_parfumnl',
        'msp_parfumcadeaukaart',
        'msp_degrotespeelgoedwinkel',
        'msp_giropay',
        'msp_multisafepay',
        'msp_directebanking',
        'msp_directdebit',
        'msp_fastcheckout',
    );

    public function __construct() {
        $this->setCode('servicecost');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address) {
        if (Mage::app()->getFrontController()->getRequest()->isSecure())
            $protocol = 'https://';
        else {
            $protocol = 'http://';
        }
        $currentUrl = $protocol . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

        if ($currentUrl != Mage::helper('checkout/cart')->getCartUrl()) {
            $quote = $address->getQuote();
            $quoteData = $quote->getData();
            $grandTotal = $quoteData['grand_total'];
            $code = $quote->getPayment()->getMethod();


            if ($code == '') {
                if (isset($_POST['payment']['method'])) {
                    $code = $_POST['payment']['method'];
                }
            }


            //reset fee when allready processed.
            $address->getQuote()->setData('servicecost_pdf', null);
            $address->setServicecostPdf(null);
            
            $address->getQuote()->setData('servicecost', null);
            $address->setServicecost(null);
            
            $address->getQuote()->setData('base_servicecost', null);
            $address->setBaseServicecost(null);
            
            $address->getQuote()->setData('servicecost_tax', null);
            $address->setServicecostTax(null);
            
            $address->getQuote()->setData('base_servicecost_tax', null);
            $address->setBaseServicecostTax(null);

            if (!empty($code)) {
                if (in_array($code, $this->availablePaymentMethodCodes)) {
                    if (Mage::getStoreConfig('msp/' . $code . '/fee', $quote->getStoreId())) {
                        $amount = $address->getShippingAmount();
                        if ($amount != 0 || $address->getShippingDescription()) {

                            $address->setServicecostAmount($this->getServicecostAmount($code, $address));
                            $address->setServicecostTaxAmount($this->getServicecostTaxAmount($code, $address));
                            $address->setBaseServicecost($this->getServicecostAmount($code, $address));
                            $address->setBaseServicecostTaxAmount($this->getServicecostTaxAmount($code, $address));


                            $address->getQuote()->setData('servicecost', $this->getServicecostAmount($code, $address));
                            $address->getQuote()->setData('base_servicecost', $this->getServicecostAmount($code, $address));
                            $address->getQuote()->setData('servicecost_tax', $this->getServicecostTaxAmount($code, $address));
                            $address->getQuote()->setData('base_servicecost_tax', $this->getServicecostTaxAmount($code, $address));

                            if (!Mage::getStoreConfig('msp/' . $code . '/fee_incexc', $quote->getStoreId())) {

                                $address->getQuote()->setData('servicecost_pdf', $this->getServicecostAmount($code, $address) - $this->getServicecostTaxAmount($code, $address));
                                $address->setServicecostPdf($this->getServicecostAmount($code, $address) - $this->getServicecostTaxAmount($code, $address));
                            } else {
                                $address->getQuote()->setData('servicecost_pdf', $this->getServicecostAmount($code, $address));
                                $address->setServicecostPdf($this->getServicecostAmount($code, $address));
                            }

                            $address->setTaxAmount($address->getTaxAmount() + $address->getServicecostTaxAmount());
                            $address->setBaseTaxAmount($address->getBaseTaxAmount() + $address->getServicecostTaxAmount());
                            $address->setGrandTotal($address->getGrandTotal() + $this->getServicecostAmount($code, $address));
                            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $this->getServicecostAmount($code, $address));
                        }
                    }
                    return $this;
                }
            }
        }
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {
        $this->_method = $address->getQuote()->getPayment()->getMethod();
        $label = Mage::helper('msp')->getFeeLabel($this->_method);
        $quote = $address->getQuote();
        $code = $quote->getPayment()->getMethod();

        if (Mage::getStoreConfig('msp/' . $code . '/fee', $quote->getStoreId())) {
            $amount = $address->getShippingAmount();
            if ($amount != 0 || $address->getShippingDescription()) {
                if ($address->getServicecostAmount()) {
                    $address->addTotal(array(
                        'code' => $this->getCode(),
                        'title' => $label,
                        'value' => $address->getServicecostPdf()
                    ));
                }
            }
        }
        return $this;
    }

    public function getServicecostAmount($code, $address) {
        $total_fee = 0;
        $fee = Mage::getStoreConfig('msp/' . $code . '/fee_amount');
        $fee_array = explode(':', $fee);

        //fee is not configured
        if ($fee_array[0] == '') {
            return;
        }

        $fixed_fee = str_replace(',', '.', $fee_array[0]);

        //check for configured percentage value
        if (!empty($fee_array[1])) {
            $fee_array[1] = str_replace(',', '.', $fee_array[1]);
            $serviceCostPercentage = str_replace('%', '', $fee_array[1]);
            $quote = Mage::getModel('sales/quote');
            $quote->load($address->getQuote()->getId());
            //if the service cost is added, then first remove it before calcualting the fee
            if ($quote->getBaseServicecost()) {
                $fee_percentage = ($quote->getBaseGrandTotal() - $quote->getBaseServicecost()) * ($serviceCostPercentage / 100);
            } else {
                $fee_percentage = $quote->getBaseGrandTotal() * ($serviceCostPercentage / 100);
            }
            $total_fee += $fee_percentage;
        }
        $total_fee += $fixed_fee;

        return (float) $total_fee;
    }

    public function getServicecostTaxAmount($code, $address) {
        $quote = $address->getQuote();
        $taxClass = Mage::getStoreConfig('msp/' . $code . '/fee_tax_class');
        if ($taxClass == 0) {
            $this->_rate = 1;
            return;
        }

        $taxCalculationModel = Mage::getSingleton('tax/calculation');

        $request = $taxCalculationModel->getRateRequest(
                $quote->getShippingAddress(), $quote->getBillingAddress(), $quote->getCustomerTaxClassId(), Mage::app()->getStore()->getId()
        );
        $request->setStore(Mage::app()->getStore())->setProductClassId($taxClass);

        $rate = $taxCalculationModel->getRate($request);

        $bigfee = 100 + $rate;

        $fee = $this->getServicecostAmount($code, $address);

        $tax = ($fee / $bigfee) * $rate;


        return $tax;
    }

}
