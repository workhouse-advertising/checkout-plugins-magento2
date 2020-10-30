<?php
namespace Lmerchant\Checkout\Model\Adapter;

use \Lmerchant\Checkout\Model\Util\Constants as LmerchantConstants;

/**
 * Class PaymentRequest
 * @package Lmerchant\Checkout\Model\Adapter
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
        $quote = $this->cartRepository->get($quoteId);

        if (!$quote->getGrandTotal()) {
            throw new \Exception(__METHOD__. " Cannot process order with zero balance.");
        }

        if (!$quote->getId()) {
            throw new \Exception(__METHOD__. " Error loading quote {$quoteId}.");
        }

        $quote->getPayment()->setMethod(LmerchantConstants::METHOD_CODE);
        $payment = $quote->getPayment();

        $payment->setAdditionalInformation(LmerchantConstants::QUOTE_ID, $quoteId);
        $payment->setAdditionalInformation(LmerchantConstants::GATEWAY_REFERENCE, $gatewayReference);
        $payment->setAdditionalInformation(LmerchantConstants::PROMOTION_REFERENCE, $promotionReference);
        
        $info = $payment->getAdditionalInformation();
        $quote->setPayment($payment);

        $this->quoteValidator->validateBeforeSubmit($quote);
        $payment->save();
        $quote->save();

        return $this->quoteManagement->placeOrder($quoteId);
    }
}
