<?php
/**
 *
 * Class Maxpay_Maxpay_Block_Checkout_Form_Method_Abstract
 */
class Maxpay_Maxpay_Block_Checkout_Form_Method_Abstract extends Mage_Payment_Block_Form
{
    private $modelName;
    private $paymentModel;

    /**
     * Get total amount of current order
     * @return mixed|null
     */
    public function getTotal()
    {
        return $this->getOrder() ? $this->getOrder()->getGrandTotal() : null;
    }

    /**
     * Get currency code of current order
     * @return string|null
     */
    public function getOrderCurrencyCode()
    {
        return $this->getOrder() ? $this->getOrder()->getOrderCurrencyCode() : null;
    }

    /**
     * Set payment model name
     * @param $name
     */
    public function setPaymentModelName($name)
    {
        $this->modelName = $name;
    }

    /**
     * Get Payment Model
     * @return false|Mage_Core_Model_Abstract
     */
    public function getPaymentModel()
    {
        if (!$this->paymentModel) {
            $this->paymentModel = Mage::getModel('maxpay/method_' . $this->modelName);
        }

        return $this->paymentModel;
    }
}