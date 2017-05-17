<?php

/**
 * Class Maxpay_Payment_PaymentController
 */
class Maxpay_Maxpay_PaymentController extends Mage_Core_Controller_Front_Action
{
    const ORDER_STATUS_AFTER_PINGBACK_SUCCESS = 'processing';

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Action that handles callback call from maxpay system
     * @return string
     */
    public function callbackAction()
    {
        $result = Mage::getModel('maxpay/callback')->handleCallback();
        $this->getResponse()->setBody($result);
    }

    /**
     * Action to which the customer will be returned when the payment is made.
     */
    public function successAction()
    {
        try {
            $this->_redirect('checkout/onepage/success');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
    }

    public function declineAction()
    {
		$this->_redirect('/');
		return;
    }


    /**
     * Show Maxpay widget
     * For Iframe
     */
    public function iframeAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}