<?php
/**
 *
 * @category MultiSafepay
 * @package  MultiSafepay_Msp
 * @license  http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class MultiSafepay_Msp_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{   
    protected $_helper = '';
    protected $_order = '';
    protected $_debugEmail = '';
    protected $_billingInfo = '';
    protected $_shippingInfo = '';
    protected $_session = '';


    /**
     * Retrieves instance of the last used order
     */
    protected function _loadLastOrder()
    {
        if (!empty($this->_order)) {
            return;
        }

        $session = Mage::getSingleton('checkout/session');
        $orderId = $session->getLastRealOrderId();
        if (!empty($orderId)) {
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        }
    }

    public function setHelper($helper)
    {
        $this->_helper = $helper;
        return $this;
    }

    public function getHelper()
    {
        return $this->_helper;
    }

    public function setOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    public function getOrder()
    {
        return $this->_order;
    }

    public function setLastOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    public function getLastOrder()
    {
        return $this->_order;
    }

    public function setDebugEmail($debugEmail)
    {
        $this->_debugEmail = $debugEmail;
        return $this;
    }

    public function getDebugEmail()
    {
        return $this->_debugEmail;
    }

    public function setBillingInfo($billingInfo)
    {
        $this->_billingInfo = $billingInfo;
        return $this;
    }

    public function getBillingInfo()
    {
        return $this->_billingInfo;
    }

    public function setShippingInfo($shippingInfo)
    {
        $this->_shippingInfo = $shippingInfo;
        return $this;
    }

    public function getShippingInfo()
    {
        return $this->_shippingInfo;
    }

    public function setSession($session)
    {
        $this->_session = $session;
        return $this;
    }

    public function getSession()
    {
        return $this->_session;
    }

    public function __construct()
    {
        return Varien_Object::__construct(func_get_args());
    }

    protected function _construct()
    {
        $this->setHelper(Mage::helper('msp'));
        $this->_loadLastOrder();
        $this->setSession(Mage::getSingleton('core/session'));
        $this->_setOrderBillingInfo();
        $this->_setOrderShippingInfo();

        $this->_checkExpired();
    }

    public function setOrderBillingInfo()
    {
        return $this->_setOrderBillingInfo();
    }

    /**
     * retrieve billing information from order
     *
     */
    protected function _setOrderBillingInfo()
    {
        if (empty($this->_order)) {
            return $this;
        }
        $billingAddress = $this->_order->getBillingAddress();
                
        $billingInfo = array(
            'firstname'   => $billingAddress->getFirstname(),
            'lastname'    => $billingAddress->getLastname(),
            'city'        => $billingAddress->getCity(),
            'state'       => $billingAddress->getState(),
            'address'     => $billingAddress->getStreetFull(),
            'zip'         => $billingAddress->getPostcode(),
            'email'       => $this->_order->getCustomerEmail(),
            'telephone'   => $billingAddress->getTelephone(),
            'fax'         => $billingAddress->getFax(),
            'countryCode' => $billingAddress->getCountry()
        );
        
        return $this->setBillingInfo($billingInfo);
    }

    public function setOrderShippingInfo()
    {
        return $this->_setOrderShippingInfo();
    }

    /**
     * retrieve shipping information from order
     *
     */
    protected function _setOrderShippingInfo()
    {
        if (empty($this->_order)) {
            return $this;
        }

        $shippingAddress = $this->_order->getShippingAddress();


        $firstname   = $shippingAddress->getFirstname();
        $lastname    = $shippingAddress->getLastname();
        $city        = $shippingAddress->getCity();
        $state       = $shippingAddress->getState();
        $address     = $shippingAddress->getStreetFull();
        $zip         = $shippingAddress->getPostcode();
        $email       = $this->_order->getCustomerEmail();
        $telephone   = $shippingAddress->getTelephone();
        $fax         = $shippingAddress->getFax();
        $countrycode = $shippingAddress->getCountry();

        $method = strtolower($this->_order->getShippingMethod());

        // COMPATIBLE WITH PAAZL
        if (substr($method, 0, 16) == 'paazl_pakjegemak') {
            $rate      = Mage::getModel('sales/quote_address_rate')->load($this->_order->getShippingMethod(), 'code');
            $street    = explode(" ", $rate->getServicePointAddress());
            $firstname = 'P';
            $lastname  = 'Paazl Pakjegemak';

            if (count($street) > 0) {
                $street_last = $street[count($street)-1];
                $street_name = str_replace($street[count($street)-1], '', $rate->getServicePointAddress());
                $street_name = str_replace($street[count($street)-2], '', $street_name);
                $street_add  = $street_last;
                $street_number = $street[count($street)-2];
                $street_name = preg_replace("/[\n\r]/","|",$street_name);
                $address     = $street_name . ' ' . $street_number . ' ' . $street_add;
            }

        }

        $shippingInfo = array(
            'firstname'   => $firstname,
            'lastname'    => $lastname,
            'city'        => $city,
            'state'       => $state,
            'address'     => $address,
            'zip'         => $zip,
            'email'       => $email,
            'telephone'   => $telephone,
            'fax'         => $fax,
            'countryCode' => $countrycode
        );

        return $this->setShippingInfo($shippingInfo);
    }

}