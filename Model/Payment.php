<?php
namespace Latitude\Checkout\Model;

use \Magento\Framework\Exception\LocalizedException as LocalizedException;
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

    const TRANSACTION_REFERENCE = 'transaction_reference';
    const GATEWAY_REFERENCE = 'gateway_reference';

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $refundResponse = $this->_getRefundAdapter()->process($payment, $amount);

        if ($refundResponse["error"]) {
            throw new LocalizedException(
                __("Could not process refund, ". $refundResponse["message"].". Please check the logs for more information.")
            );

            return;
        }

        $payment->setAdditionalInformation(LatitudeConstants::TRANSACTION_REFERENCE, $refundResponse[self::TRANSACTION_REFERENCE]);
        $payment->setAdditionalInformation(LatitudeConstants::GATEWAY_REFERENCE, $refundResponse[self::GATEWAY_REFERENCE]);
        $payment->save();
        
        return $this;
    }

    private function _getLogger()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->get("\Latitude\Checkout\Logger\Logger");
    }

    private function _getRefundAdapter()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->get("Latitude\Checkout\Model\Adapter\Refund");
    }
}
