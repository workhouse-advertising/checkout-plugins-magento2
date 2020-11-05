<?php
namespace Latitude\Checkout\Model\Adapter;

use \Magento\Framework\Exception\LocalizedException as LocalizedException;
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
    protected $orderRespository;
    protected $emailSender;
    protected $checkoutSession;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\OrderRepository $orderRespository,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailSender,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteValidator = $quoteValidator;
        $this->quoteManagement = $quoteManagement;
        $this->orderRespository = $orderRespository;
        $this->emailSender = $emailSender;
        $this->checkoutSession = $checkoutSession;
    }

    public function complete($quoteId, $gatewayReference, $promotionReference)
    {
        $quote = $this->_getQuoteById($quoteId);

        // Set payment details
        $payment = $quote->getPayment();
        $payment->importData(['method' => LatitudeConstants::METHOD_CODE]);
        $payment->setMethod(LatitudeConstants::METHOD_CODE);
        $payment->setAdditionalInformation(LatitudeConstants::QUOTE_ID, $quoteId);
        $payment->setAdditionalInformation(LatitudeConstants::GATEWAY_REFERENCE, $gatewayReference);
        $payment->setAdditionalInformation(LatitudeConstants::PROMOTION_REFERENCE, $promotionReference);
        $payment->save();

        // Convert to order
        $quote->collectTotals()->save();
        $this->quoteValidator->validateBeforeSubmit($quote);
        $order = $this->quoteManagement->submit($quote);

        if (!$order) {
            throw new LocalizedException(__("Could not create order for quote ". $quoteId));
        }

        // set additional info
        $payment = $order->getPayment();
        $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $payment->getAdditionalInformation());

        // set parent transaction
        $payment->setTransactionId($gatewayReference)
            ->setCurrencyCode($order->getBaseCurrencyCode())
            ->setParentTransactionId($payment->getTransactionId())
            ->setShouldCloseParentTransaction(true)
            ->setIsTransactionClosed(false)
            ->registerCaptureNotification($order->getBaseGrandTotal());
        
        $order->save();

        switch ($order->getState()) {
            // handle auth/capture exceptions caused by paypal or bank capture
            case \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT:
                break;
            // ok
            case \Magento\Sales\Model\Order::STATE_PROCESSING:
            case \Magento\Sales\Model\Order::STATE_COMPLETE:
            case \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW:
                $this->emailSender->send(($order));
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
            throw new LocalizedException(
                __("Cannot process quote with zero balance.")
            );
        }

        if (!$quote->getId()) {
            throw new LocalizedException(
                __("Error loading quote {$quoteId}.")
            );
        }

        if (!boolval($quote->getIsActive())) {
            throw new LocalizedException(
                __("Could not process inactive quote {$quoteId}.")
            );
        }

        return $quote;
    }
}
