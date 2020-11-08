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

    protected $orderAdapter;
    protected $logger;
   
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Latitude\Checkout\Model\Adapter\Order $orderAdapter,
        \Latitude\Checkout\Logger\Logger $logger
    ) {
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->quoteValidator = $quoteValidator;

        $this->orderAdapter = $orderAdapter;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->debug(__METHOD__. " Begin");

        try {
            $quoteId = $this->getRequest()->getParam('reference');

            if (!isset($quoteId) || empty($quoteId)) {
                $this->_processError("Validaion failed, quote id is mandatory", null);
                return;
            }

            $quote = $this->cartRepository->get($quoteId);
            $orderId = $quote->getReservedOrderId();
            $paymentMethod = $quote->getPayment()->getMethod();

            if ($paymentMethod != LatitudeConstants::METHOD_CODE) {
                $this->_processError("Validaion failed, Invalid payment method ". $paymentMethod, null);
                return;
            }

            if (!isset($orderId) || empty($orderId)) {
                throw new LocalizedException(
                    __('Could not get order id for '. $quoteId)
                );
            }

            if (!boolval($quote->getIsActive())) {
                throw new LocalizedException(
                    __('Could not process inactive quote')
                );
            }

            $createdOrderId = $this->orderAdapter->placeOrder($quoteId);

            $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId)->setLastOrderId($orderId)->setLastRealOrderId($orderId);

            $this->logger->debug(__METHOD__.
                " Order created with Quote Id: {$quoteId}".
                " Order Id: {$orderId} and {$createdOrderId}");

            $this->_redirect('checkout/onepage/success');
        } catch (LocalizedException $le) {
            $this->_processError($le->getRawMessage(), "Your payment was not successful, please try again or select other payment method");
        } catch (\Exception $e) {
            $this->_processError($e->getMessage(), "Your payment was not successful, please try again or select other payment method");
        }
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
