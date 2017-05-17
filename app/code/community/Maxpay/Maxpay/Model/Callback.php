<?php
class Maxpay_Maxpay_Model_Callback extends Mage_Core_Model_Abstract
{
    const DEFAULT_CALLBACK_RESPONSE = 'OK';
	const SUCCCES_CODE = '0';
    
    /**
     * Handle callback
     * @return string
     */
    public function handleCallback()
    {
		$store = Mage::app()->getStore()->getStoreId(); 
		$scriney = new \Maxpay\Scriney(Mage::getStoreConfig('payment/maxpay_iframe/maxpay_public_key', $store), Mage::getStoreConfig('payment/maxpay_iframe/maxpay_private_key', $store));
		try {
			if ($scriney->validateCallback($_POST)) {
				$orderId = $_POST['productList'][0]['productId'];
				$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
				if (empty($order)) {
					Mage::log("Order invalid with ID " . $orderId);
				}
				else {
					return $this->processCallbackOrder($order, $_POST);
				}
			}
		} catch (Exception $e) {
			Mage::log($e->getMessage());
		}
        return self::DEFAULT_CALLBACK_RESPONSE;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $data
     * @return string
     */
    protected function processCallbackOrder(Mage_Sales_Model_Order $order, $data = array())
    {

		if ($data['code'] === self::SUCCCES_CODE && $order->getGrandTotal() == $data['totalAmount'] &&  $order->getOrderCurrencyCode() === $data['currency']) {
			$payment = $order->getPayment();
			$invoice = $order->getInvoiceCollection()
				->addAttributeToSort('created_at', 'DSC')
				->setPage(1, 1)
				->getFirstItem();

			try {
				if (
					$order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING
					|| $order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE
				) {
					return self::DEFAULT_CALLBACK_RESPONSE;
				}

				$paymentModel = $payment->getMethodInstance();
				$paymentModel->setCurrentOrder($order);
				if ($invoice->getId()) {
					$paymentModel->payInvoice($data['transactionId'], $invoice);
				} else {
					$paymentModel->makeInvoice($data['transactionId']);
					$invoice = $order->getInvoiceCollection()
						->addAttributeToSort('created_at', 'DSC')
						->setPage(1, 1)
						->getFirstItem();
					if ($invoice->getId()) {
						$paymentModel->payInvoice($data['transactionId'], $invoice);
					}
				}

			} catch (Exception $e) {
				Mage::log($e->getMessage());
			}
		}
		return self::DEFAULT_CALLBACK_RESPONSE;
    }
}