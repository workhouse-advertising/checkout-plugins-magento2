<?php

namespace Lmerchant\Checkout\Controller\Payment;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Quote\Api\CartRepositoryInterface as CartRepository;

use \Lmerchant\Checkout\Logger\Logger;
use \Lmerchant\Checkout\Model\Util\Constants as LmerchantConstants;

/**
 * Class Complete
 * @package Lmerchant\Checkout\Controller\Complete
 */
class Complete extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_cartRepository;

    protected $_logger;
    /**
     * Complete constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CheckoutSession $checkoutSession,
        CartRepository $cartRepository,
        Logger $logger
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_cartRepository = $cartRepository;
        $this->_logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->_logger->debug(__METHOD__. " Begin");
        
        $quoteId = $this->getRequest()->getParam('reference');

        $quote = $this->_cartRepository->get($quoteId);
        $orderId = $quote->getReservedOrderId();

        if (empty($quoteId) || empty($orderId)) {
            $this->_logger->debug(__METHOD__. " Redirecting to cart");
            $this->_redirect(LmerchantConstants::CANCEL_ROUTE);
            return;
        }

        $this->_logger->debug(__METHOD__. " Redirecting to success page. Order Id: {$orderId}");

        $this->_checkoutSession
            ->setLastQuoteId($quoteId)
            ->setLastSuccessQuoteId($quoteId)
            ->setLastOrderId($orderId)
            ->setLastRealOrderId($orderId);

        $this->_checkoutSession->setLoadInactive(false);
        $this->_checkoutSession->replaceQuote($this->_checkoutSession->getQuote()->save());

        $this->_logger->info(__METHOD__ .
            " order complete ".
            " lastSuccessQuoteId: ".  $this->_checkoutSession->getLastSuccessQuoteId().
            " lastQuoteId:".$this->_checkoutSession->getLastQuoteId().
            " lastOrderId:".$this->_checkoutSession->getLastOrderId().
            " lastRealOrderId:" . $this->_checkoutSession->getLastRealOrderId());
        
        $this->_redirect(LmerchantConstants::SUCCESS_ROUTE, [
            '_secure' => true,
            '_nosid' => true,
            'mage_order_id' => $orderId
        ]);

        return;
    }
}
