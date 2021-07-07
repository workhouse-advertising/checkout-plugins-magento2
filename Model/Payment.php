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

    const ERROR = 'error';
    const MESSAGE = 'message';

    const REFUND = 'Refund';

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $refundResponse = $this->_getRefundAdapter()->process($payment, $amount);

            if ($refundResponse[self::ERROR]) {
                $this->_handleError(self::REFUND, $refundResponse[self::MESSAGE]);
            }

            $this->_getLogger->info("refund processed");

            $payment->setAdditionalInformation(LatitudeConstants::TRANSACTION_REFERENCE, $refundResponse[self::MESSAGE][LatitudeConstants::TRANSACTION_REFERENCE]);
            $payment->setAdditionalInformation(LatitudeConstants::GATEWAY_REFERENCE, $refundResponse[self::MESSAGE][LatitudeConstants::GATEWAY_REFERENCE]);
            
            $payment->save();
        } catch (LocalizedException $le) {
            $this->_handleError(self::REFUND, $le->getRawMessage());
        } catch (\Exception $e) {
            $this->_handleError(self::REFUND, $le->getRawMessage());
        }
       
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

    private function _handleError($transactionType, $message)
    {
        throw new LocalizedException(
            __($transactionType. " failed with error [". $message. "]. Please check logs more information.")
        );
    }
}
