<?php
namespace Latitude\Checkout\Model\Adapter;

use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;

/**
 * Class PaymentRequest
 * @package Latitude\Checkout\Model\Adapter
 */
class Order
{
    protected $cartRepository;
    protected $quoteValidator;
    protected $quoteManagement;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteValidator = $quoteValidator;
        $this->quoteManagement = $quoteManagement;
    }

    public function create($quoteId, $gatewayReference, $promotionReference)
    {
        $quote = $this->_getQuoteById(quoteId);

        $quote->getPayment()->setMethod(LatitudeConstants::METHOD_CODE);
        $payment = $quote->getPayment();

        $payment->setAdditionalInformation(LatitudeConstants::QUOTE_ID, $quoteId);
        $payment->setAdditionalInformation(LatitudeConstants::GATEWAY_REFERENCE, $gatewayReference);
        $payment->setAdditionalInformation(LatitudeConstants::PROMOTION_REFERENCE, $promotionReference);
        
        $info = $payment->getAdditionalInformation();
        $quote->setPayment($payment);

        $this->quoteValidator->validateBeforeSubmit($quote);
        $payment->save();
        $quote->save();

        return $this->quoteManagement->placeOrder($quoteId);
    }

    public function addError($quoteId, $message)
    {
        $quote = $this->_getQuoteById(quoteId)->addErrorInfo('error', 'Latitude_Checkout', 1, $message, null);
    }

    private function _getQuoteById($quoteId)
    {
        $quote = $this->cartRepository->get($quoteId);

        if (!$quote->getGrandTotal()) {
            throw new \Exception(__METHOD__. " Cannot process quote with zero balance.");
        }

        if (!$quote->getId()) {
            throw new \Exception(__METHOD__. " Error loading quote {$quoteId}.");
        }

        return $quote;
    }
}
