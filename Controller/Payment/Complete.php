<?php

namespace Latitude\Checkout\Controller\Payment;

use \Magento\Framework\Exception\LocalizedException as LocalizedException;
use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;

/**
 * Class Complete
 * @package Latitude\Checkout\Controller\Complete
 */
class Complete extends \Magento\Framework\App\Action\Action
{
    protected $messageManager;
    protected $checkoutSession;
    protected $cartRepository;
    protected $quoteValidator;

    protected $purchaseVerifyAdapter;
    protected $logger;

    const ERROR = "error";
    const MESSAGE = "message";
    const BODY = "body";
    const ORDER = "order";

    const REFERENCE = "reference";
    const MERCHANT_REFERENCE = "merchantReference";
    const GATEWAY_REFERENCE = "gatewayReference";
    const TRANSACTION_REFERENCE = "transactionReference";
   
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Latitude\Checkout\Model\Adapter\PurchaseVerify $purchaseVerifyAdapter,
        \Latitude\Checkout\Logger\Logger $logger
    ) {
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->quoteValidator = $quoteValidator;

        $this->purchaseVerifyAdapter = $purchaseVerifyAdapter;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->debug(__METHOD__. " Begin");

        try {
            $parsed = $this->_parseRequest();

            $verifyResponse = $this->purchaseVerifyAdapter->verifyAndCreateOrder($parsed);

            if ($verifyResponse[self::ERROR]) {
                throw new LocalizedException(__($verifyResponse[self::MESSAGE]));
            }

            $order = $verifyResponse[self::ORDER];
            if (!isset($order)) {
                throw new LocalizedException("Invalid order");
            }

            $this->checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());

            $this->logger->debug(
                __METHOD__.
                " Order created with Quote Id: {$merchantReference}".
                " Order Id: {$order->getId()}".
                " Order Increment Id: {$order->getIncrementId()}".
                " Order Status: {$order->getStatus()}"
            );

            $this->_redirect('checkout/onepage/success');
        } catch (LocalizedException $le) {
            $this->_processError($le->getRawMessage(), "Your payment was not successful, please try again or select other payment method");
        } catch (\Exception $e) {
            $this->_processError($e->getMessage(), "Your payment was not successful, please try again or select other payment method");
        }
    }

    private function _parseRequest()
    {
        $parsed = [
            self::REFERENCE => $this->getRequest()->getParam(self::REFERENCE),
            self::MERCHANT_REFERENCE => $this->getRequest()->getParam(self::MERCHANT_REFERENCE),
            self::GATEWAY_REFERENCE => $this->getRequest()->getParam(self::GATEWAY_REFERENCE),
            self::TRANSACTION_REFERENCE => $this->getRequest()->getParam(self::TRANSACTION_REFERENCE),
        ];

        if (
            empty($parsed[self::REFERENCE]) ||
            empty($parsed[self::MERCHANT_REFERENCE]) ||
            empty($parsed[self::GATEWAY_REFERENCE]) ||
            empty($parsed[self::TRANSACTION_REFERENCE])
         ) {
            throw new LocalizedException(
                __("Validation failed, reference, merchantReference, gatewayReference or transactionReference are mandatory")
            );
        }

        return $parsed;
    }

    private function _processError($message, $displayMessage)
    {
        $this->logger->error(__METHOD__. " ". $message);

        if (!empty($displayMessage)) {
            $this->messageManager->addErrorMessage($displayMessage);
        }

        $this->_redirect("checkout/cart");
    }
}
