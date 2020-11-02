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

    protected $logger;
    /**
     * Complete constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Latitude\Checkout\Logger\Logger $logger
    ) {
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->quoteValidator = $quoteValidator;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->debug(__METHOD__. " Begin");

        try {
            $quoteId = $this->getRequest()->getParam('reference');

            if (!isset($quoteId) || empty($quoteId)) {
                $this->_redirect("checkout/cart");
                return;
            }

            $quote = $this->cartRepository->get($quoteId);
            $orderId = $quote->getReservedOrderId();

            $this->quoteValidator->validateBeforeSubmit($quote);

            if (!isset($orderId) || empty($orderId)) {
                throw new LocalizedException(__("Could not get order id for". $quoteId));
                return;
            }

            if (boolval($quote->getIsActive())) {
                throw new LocalizedException(__("Could not show success for active quote"));
                return;
            }

            $this->logger->debug(__METHOD__. " Processing quote and redirecting to success page. Order Id: {$orderId}");

            $this->checkoutSession
            ->setLastQuoteId($quoteId)
            ->setLastSuccessQuoteId($quoteId)
            ->setLastOrderId($orderId)
            ->setLastRealOrderId($orderId);

            $this->checkoutSession->setLoadInactive(false);
            $this->checkoutSession->replaceQuote($this->checkoutSession->getQuote()->save());

            $this->logger->debug(__METHOD__.
            " order complete ".
            " lastSuccessQuoteId: ". $this->checkoutSession->getLastSuccessQuoteId().
            " lastQuoteId:". $this->checkoutSession->getLastQuoteId().
            " lastOrderId:". $this->checkoutSession->getLastOrderId().
            " lastRealOrderId:". $this->checkoutSession->getLastRealOrderId());
        
            $this->_redirect('checkout/onepage/success', [
                '_secure' => true,
                '_nosid' => true,
                'mage_order_id' => $orderId
            ]);
            return;
        } catch (\Magento\Framework\Exception\LocalizedException $locallizedException) {
            return $this->_processError($locallizedException);
        } catch (\Exception $exception) {
            return $this->_processError($exception);
        }
    }

    private function _processError(\Exception $exception)
    {
        $this->logger->error(__METHOD__. " ". $exception->getRawMessage());
        $this->messageManager->addErrorMessage("Your payment was not successful, please try again or select other payment method");
        $this->_redirect("checkout/cart");
    }
}
