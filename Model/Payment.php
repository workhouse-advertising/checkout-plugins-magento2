<?php
namespace Lmerchant\Checkout\Model;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE = 'lmerchant';
    const MINUTE_DELAYED_ORDER = 75;

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    protected $_isGateway = true;
    protected $_isInitializeNeeded = false;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal = false;
    protected $_canFetchTransactionInfo = true;

    protected $_infoBlockType = 'Lmerchant\Checkout\Block\Info';

    public function capture($payment, $amount)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->logger = $objectManager->get("\Lmerchant\Checkout\Logger\Logger");
        $this->logger->info(__METHOD__ . " Amount: {$amount}");
        $this->_createTransaction($payment, $amount);
        $this->logger->info(__METHOD__ . " Transaction created");
        return $this;
    }

    public function refund($payment, $amount)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->logger = $objectManager->get("\Lmerchant\Checkout\Logger\Logger");
        $this->logger->info(__METHOD__ . " Amount: {$amount}");
        $this->logger->info(__METHOD__ . " Send request to lmerchant refund endpoint: /refund.");
        return $this;
    }

    private function _createTransaction($payment, $amount)
    {
        $orderId = "";
        $order = $payment->getOrder();

        if ($order) {
            $orderId = $order->getIncrementId();
        }

        if (!$payment->hasAdditionalInformation()) {
            $this->logger->info(__METHOD__. " No additional information found for orderId: {$orderId}");
        }

        $info = $payment->getAdditionalInformation();

        $transactionId = $info[\Lmerchant\Checkout\Model\Util\Constants::GATEWAY_REFERENCE];

        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(true);

        unset($info['items']);
        unset($info['billing']);
        unset($info['shipping']);
        unset($info['merchant']);

        $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $info);
    }
}
