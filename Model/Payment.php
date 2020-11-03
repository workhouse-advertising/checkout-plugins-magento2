<?php
namespace Latitude\Checkout\Model;

use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const MINUTE_DELAYED_ORDER = 75;

    /**
     * @var string
     */
    protected $_code = LatitudeConstants::METHOD_CODE;

    protected $_isGateway = true;
    protected $_isInitializeNeeded = false;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal = false;
    protected $_canFetchTransactionInfo = true;

    protected $_infoBlockType = 'Latitude\Checkout\Block\Info';

    public function capture($payment, $amount)
    {
        $this->_getLogger()->info(__METHOD__ . " Begin capture for amount: {$amount}");
        $this->_createTransaction($payment, $amount);
        $this->_getLogger()->debug(__METHOD__ . " Transaction capture completed");
        return $this;
    }

    public function refund($payment, $amount)
    {
        $this->_getLogger()->info(__METHOD__ .
            " Begin refund for Amount: {$amount}".
            " Sending request to latitude refund endpoint: /refund.");

        // TODO: send request to refund endpoint
        throw new \Magento\Framework\Exception\LocalizedException(
            __('Could not process refund')
        );
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

        $transactionId = $info[LatitudeConstants::GATEWAY_REFERENCE];

        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(true);

        unset($info['items']);
        unset($info['billing']);
        unset($info['shipping']);
        unset($info['merchant']);

        $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $info);
    }

    private function _getLogger()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->get("\Latitude\Checkout\Logger\Logger");
    }
}
