<?php

namespace Lmerchant\Checkout\Controller\Payment;

use \Lmerchant\Checkout\Model\Util\Constants as LmerchantConstants;

/**
 * Class Complete
 * @package Lmerchant\Checkout\Controller\Complete
 */
class Complete extends \Magento\Framework\App\Action\Action
{
    protected $request;
    protected $checkoutSession;
    protected $cartRepository;

    protected $logger;
    /**
     * Complete constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Lmerchant\Checkout\Logger\Logger $logger
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->debug(__METHOD__. " Begin");

        try {
            $quoteId = $this->request->getParam('reference');

            if (empty($quoteId)) {
                $this->_processError(new \Exception(__("Invalid quoteId: ". $quoteId)));
                return;
            }

            $quote = $this->cartRepository->get($quoteId);
            $orderId = $quote->getReservedOrderId();

            if (empty($orderId)) {
                $this->_processError(new \Exception(__("Invalid orderId". $orderId)));
                return;
            }

            if (boolval($quote->getIsActive())) {
                $this->_processError(new \Exception(__("Could not show success for active quote")));
                return;
            }

            $this->logger->debug(__METHOD__. " Redirecting to success page. Order Id: {$orderId}");

            $this->checkoutSession
            ->setLastQuoteId($quoteId)
            ->setLastSuccessQuoteId($quoteId)
            ->setLastOrderId($orderId)
            ->setLastRealOrderId($orderId);

            $this->checkoutSession->setLoadInactive(false);
            $this->checkoutSession->replaceQuote($this->checkoutSession->getQuote()->save());

            $this->logger->debug(__METHOD__ .
            " order complete ".
            " lastSuccessQuoteId: ".  $this->checkoutSession->getLastSuccessQuoteId().
            " lastQuoteId:".$this->checkoutSession->getLastQuoteId().
            " lastOrderId:".$this->checkoutSession->getLastOrderId().
            " lastRealOrderId:" . $this->checkoutSession->getLastRealOrderId());
        
            $this->_redirect('checkout/onepage/success', [
                '_secure' => true,
                '_nosid' => true,
                'mage_order_id' => $orderId
            ]);
            return;
        } catch (\LocalizedException $e) {
            $this->logger->error(__METHOD__. $e->getRawMessage());
            $this->_processError($e);
        }
        catch (\Exception $e) {
            $this->logger->error(__METHOD__. $e->getRawMessage());
            $this->_processError($e);
        }
    }

    private function _processError(\Exception $exception)
    {
        $this->_redirect('checkout?cancel', ['_fragment' => 'payment']);
    }
}
