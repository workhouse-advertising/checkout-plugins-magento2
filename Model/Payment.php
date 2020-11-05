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

    public function refund($payment, $amount)
    {
        $this->_getLogger()->info(__METHOD__ .
            " Refund requested for refund for Amount: {$amount}");

        throw new LocalizedException(
            __('Could not process refund')
        );
    }

    private function _getLogger()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->get("\Latitude\Checkout\Logger\Logger");
    }
}
