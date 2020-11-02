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
    protected $emailSender;
    protected $checkoutSession;
    protected $quote;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailSender,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteValidator = $quoteValidator;
        $this->quoteManagement = $quoteManagement;
        $this->emailSender = $emailSender;
        $this->checkoutSession = $checkoutSession;
    }

    public function complete($quoteId, $gatewayReference, $promotionReference)
    {
        $this->quote = $this->_getQuoteById(quoteId);

        $this->quote->getPayment()->setMethod(LatitudeConstants::METHOD_CODE);
        $this->quote->collectTotals();
        $this->_prepareQuote();
        $this->_setAddtInfo();

        $this->quoteValidator->validateBeforeSubmit($this->quote);
        $this->quote->save();
        $order = $this->quoteManagement->submit($this->quote);

        switch ($order->getState()) {
            // handle auth/capture exceptions caused by paypal or bank capture
            case \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT:
                break;
            // ok
            case \Magento\Sales\Model\Order::STATE_PROCESSING:
            case \Magento\Sales\Model\Order::STATE_COMPLETE:
            case \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW:
                $this->emailSender->send(($order));
                $this->checkoutSession->start();
                break;
            default:
                break;
        }

        return $order->getId();
    }

    public function addError($quoteId, $message)
    {
        $this->_getQuoteById($quoteId)->addErrorInfo('error', 'Latitude_Checkout', 1, $message, null)->save();
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

    private function _prepareQuote()
    {
        $quote = $this->quote;

        $this->quote->getBillingAddress()->setShouldIgnoreValidation(true);

        if ($quote->getCheckoutMethod() != \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {
            return;
        }

        $this->quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
    }

    private function _setAdditionalInfo()
    {
        $payment = $this->quote->getPayment();

        $payment->setAdditionalInformation(LatitudeConstants::QUOTE_ID, $quoteId);
        $payment->setAdditionalInformation(LatitudeConstants::GATEWAY_REFERENCE, $gatewayReference);
        $payment->setAdditionalInformation(LatitudeConstants::PROMOTION_REFERENCE, $promotionReference);
        
        $this->quote->setPayment($payment);

        $payment->save();
    }
}
