<?php
/**
 * Class Maxpay_Maxpay_Model_Method_Abstract
 */
class Maxpay_Maxpay_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract {

    protected $_code;
    protected $_scriney;
    protected $_logFile = 'maxpay.log';

    /**
     * @param string $code
     */
    public function __construct($code = '') {
        if ($code) {
            $this->_code = 'maxpay_' . $code;
        }

        $this->_formBlockType = 'maxpay/checkout_form_method_' . $code;
        $this->_infoBlockType = 'maxpay/checkout_info_method_' . $code;
        $this->setData('original_code', $code);
    }

    /**
     * Init maxpay configs
     */
    public function initMaxpayConfig() {
		$this->_scriney = new \Maxpay\Scriney($this->getConfigData('maxpay_public_key'), $this->getConfigData('maxpay_private_key'));
    }

    public function getMethodCode() {
        return $this->_code;
    }

    public function getScriney() {
        return $this->_scriney;
    }

	protected function prepareProducts(Mage_Sales_Model_Order $order) {
		return [
			new \Maxpay\Lib\Model\FixedProduct(
				$order->getIncrementId(),
				'Order id #' . $order->getIncrementId(),
				floatval($order->getGrandTotal()),
				$order->getOrderCurrencyCode()
			)
		];
	}

    /**
     * @param Mage_Sales_Model_Order $order
     * @return \Maxpay\Lib\Model\UserInfo
     */
    protected function prepareUserProfile(Mage_Sales_Model_Order $order) {
		$email = $firstName = $lastName = $ISO3Country = $city = $postalCode = $address = $phone = null;
		$billing = $order->getBillingAddress();
		$city = $billing->getCity();
		$address = $billing->getStreetFull();
		$ISO3Country = $this->getIso3Code($billing);
		$postalCode = $billing->getPostcode();
		$phone = $billing->getTelephone();

		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			// Load the customer's data
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			$firstName = $customer->getFirstname();
			$lastName = $customer->getLastname();
			$email = $customer->getEmail();
		} else {
			$email = $billing->getEmail();
			$firstName = $billing->getFirstname();
			$lastName = $billing->getLastname();
		}
		return new \Maxpay\Lib\Model\UserInfo(
			$email,
			$firstName,
			$lastName,
			$ISO3Country,
			$city,
			$postalCode,
			$address,
			$phone
		);
    }

    /**
     * @param Mage_Sales_Model_Order_Address $billing
     * @return string
     */
	protected function getIso3Code(Mage_Sales_Model_Order_Address $billing) {
		return Mage::getModel('directory/country')->loadByCode($billing->getCountry_id())->getIso3Code();
	}

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
	public function getCustomerIdentifier(Mage_Sales_Model_Order $order) {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            return $customer->getId();
        }
		else {
			$billing = $order->getBillingAddress();
			return $billing->getEmail();
		}
	}

    /**
     * Make invoice for paid order
     * @param $transactionId
     * @throws Exception
     * @throws bool
     */
    public function makeInvoice($transactionId) {
        $order = $this->getCurrentOrder();
        if ($order) {

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId)
                ->setPreparedMessage('Invoice created by Maxpay module')
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionClosed(0)
                ->capture(null);
            $order->save();

            // notify customer
            $invoice = $payment->getCreatedInvoice();
            if ($invoice && !$order->getEmailSent() && !Mage::getStoreConfig('system/smtp/disable')) {
                $order->sendNewOrderEmail()
                    ->addStatusHistoryComment(Mage::helper('maxpay')->__('Notified customer about invoice #%s.', $invoice->getIncrementId()))
                    ->setIsCustomerNotified(true)
                    ->save();
            }
        }
    }

    /**
     * @param $transactionId
     * @param $invoice
     */
    public function payInvoice($transactionId, Mage_Sales_Model_Order_Invoice $invoice) {
        $order = $this->getCurrentOrder();

        if ($order) {
            $payment = $order->getPayment();
            $message = Mage::helper('sales')->__('Captured amount of %s online.', $order->getBaseCurrency()->formatTxt($invoice->getBaseGrandTotal()));

            $invoice->setTransactionId($transactionId)
                ->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->getOrder()->setIsInProcess(true);
            $invoice->getOrder()->addStatusHistoryComment($message)->setIsCustomerNotified(true);

            $payment->setTransactionId($transactionId)
                ->setLastTransId($transactionId)
                ->setCurrencyCode($order->getOrderCurrencyCode())
                ->setPreparedMessage('Payment approved by Maxpay')
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionClosed(0)
                ->registerCaptureNotification($invoice->getBaseGrandTotal());
            $invoice->pay();
            $order->setState('processing', true, "Payment has been received", false)->save();

            // notify customer
            if ($invoice && !$order->getEmailSent() && !Mage::getStoreConfig('system/smtp/disable')) {
                $order->sendNewOrderEmail()
                    ->addStatusHistoryComment(Mage::helper('maxpay')->__('Notified customer about invoice #%s.', $invoice->getIncrementId()))
                    ->setIsCustomerNotified(true)
                    ->save();
            }
        }
    }

    /**
     * Log Function
     * @param $message
     */
    public function log($message, $section = '') {
        if ($this->getConfigData('debug_mode')) {
            if (!is_string($message)) {
                $message = var_export($message, true);
            }
            $message = "\n/********** " . $this->getCode() . ($section ? " " . $section : "") . " **********/\n" . $message;
            Mage::log($message, null, $this->_logFile);
        }
    }

}