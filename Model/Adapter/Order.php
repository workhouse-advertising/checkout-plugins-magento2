<?php
namespace Latitude\Checkout\Model\Adapter;

use \Magento\Framework\Exception\LocalizedException as LocalizedException;
use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;

class Order
{
    protected $cartRepository;
    protected $quoteManagement;
    protected $jsonHelper;
    protected $quoteValidator;
    protected $emailSender;
    protected $checkoutSession;

    protected $latitudeHelper;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $emailSender,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Latitude\Checkout\Model\Util\Helper $latitudeHelper
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteManagement = $quoteManagement;
        $this->jsonHelper = $jsonHelper;
        $this->quoteValidator = $quoteValidator;
        $this->emailSender = $emailSender;
        $this->checkoutSession = $checkoutSession;

        $this->latitudeHelper = $latitudeHelper;
    }

    public function addTransactionInfo($quoteId, $amount, $transactionReference, $gatewayReference, $promotionReference)
    {
        $quote = $this->_getQuoteById($quoteId);

        if (round((float)$quote->getGrandTotal(), 2) != round((float)$amount, 2)) {
            throw new LocalizedException(__("Amount mismatch, quote total ". $quote->getGrandTotal(). " payment total: ". $amount));
        }

        // Set payment details
        $payment = $quote->getPayment();
        $payment->importData(['method' => LatitudeConstants::METHOD_CODE]);
        $payment->setMethod(LatitudeConstants::METHOD_CODE);
        $payment->setAdditionalInformation(LatitudeConstants::GATEWAY_REFERENCE, $gatewayReference);
        $payment->setAdditionalInformation(LatitudeConstants::TRANSACTION_REFERENCE, $transactionReference);
        $payment->setAdditionalInformation(LatitudeConstants::PROMOTION_REFERENCE, $promotionReference);
        $payment->save();
        $quote->save();

        return $quote->getId();
    }

    public function addError($quoteId, $message)
    {
        $this->_getQuoteById($quoteId)->addErrorInfo('error', 'Latitude_Checkout', 1, $message, null)->save();
    }

    public function placeOrder($quoteId)
    {
        $quote = $this->_getQuoteById($quoteId);

        $quotePayment = $quote->getPayment();

        $gatewayRef = $quotePayment->getAdditionalInformation(LatitudeConstants::GATEWAY_REFERENCE);
        $transactionRef = $quotePayment->getAdditionalInformation(LatitudeConstants::TRANSACTION_REFERENCE);
        $promotionRef = $quotePayment->getAdditionalInformation(LatitudeConstants::PROMOTION_REFERENCE);


        if (!$this->latitudeHelper->isInsecureMode() && (
            empty($gatewayRef) || empty($transactionRef) || empty($promotionRef)
        )) {
            $paymentDetails = $this->jsonHelper->jsonEncode($quotePayment->getAdditionalInformation());
            throw new LocalizedException(__("Could not find payment details for for quote ". $quoteId. ", payment details: ". $paymentDetails));
        }

        $this->quoteValidator->validateBeforeSubmit($quote);
        $order = $this->quoteManagement->submit($quote);

        if (!$order) {
            throw new LocalizedException(__("Could not create order for quote ". $quoteId));
        }

        $orderPayment = $order->getPayment();
        $orderPayment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $quotePayment->getAdditionalInformation());
        
        $orderPayment->setTransactionId($gatewayRef)->setParentTransactionId($quotePayment->getTransactionId());
        $orderPayment->setIsTransactionClosed(false)->registerCaptureNotification($order->getBaseGrandTotal());

        $orderPayment->save();
        $order->save();

        switch ($order->getState()) {
            // handle auth/capture exceptions caused by paypal or bank capture
            case \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT:
                break;
            // ok
            case \Magento\Sales\Model\Order::STATE_PROCESSING:
            case \Magento\Sales\Model\Order::STATE_COMPLETE:
            case \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW:
                $this->emailSender->send($order);
                $this->checkoutSession->start();
                break;
            default:
                break;
        }

        return $order;
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
