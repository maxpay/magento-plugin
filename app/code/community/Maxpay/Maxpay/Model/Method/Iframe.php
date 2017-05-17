<?php
class Maxpay_Maxpay_Model_Method_Iframe extends Maxpay_Maxpay_Model_Method_Abstract {
    /**
     * Constructor method.
     * Set some internal properties
     */
    public function __construct() {
        parent::__construct('iframe');
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('maxpay/payment/iframe', array('_secure' => true));
    }

    /**
     * Generate Maxpay Button
     * @param $order
     * @return ButtonBuilder
     */
    public function getPaymentWidget(Mage_Sales_Model_Order $order) {
        $this->initMaxpayConfig();

		return $this->getScriney()->
			buildButton($this->getCustomerIdentifier($order))->
			setUserInfo($this->prepareUserProfile($order))->
			setCustomProducts($this->prepareProducts($order));
    }
}