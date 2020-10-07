<?php
namespace Lmerchant\Checkout\Api;

use Magento\Quote\Api\CartRepositoryInterface as CartRepository;
use Magento\Quote\Model\QuoteValidator;
use Magento\Quote\Model\QuoteManagement;
use Magento\Framework\Exception\LocalizedException;

use Lmerchant\Checkout\Logger\Logger;
use Lmerchant\Checkout\Model\Util\Helper as LmerchantHelper;
use Lmerchant\Checkout\Model\Util\Constants as LmerchantConstants;

class Callback
{
    protected $cartRepository;
    protected $quoteValidator;
    protected $quoteManagement;

    protected $logger;
    protected $lmerchantHelper;

    const MERCHANT_ID = 'merchant_id';
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const MERCHANT_REFERENCE = 'merchant_reference';
    const GATEWAY_REFERENCE = 'gateway_reference';
    const PROMOTION_REFERENCE = 'promotion_reference';
    const RESULT = 'result';
    const TRANSACTION_TYPE = 'transaction_type';
    const TEST = 'test';
    const MESSAGE = 'message';

    public function __construct(
        CartRepository $cartRepository,
        QuoteValidator $quoteValidator,
        QuoteManagement $quoteManagement,
        Logger $logger,
        LmerchantHelper $lmerchantHelper
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteValidator = $quoteValidator;
        $this->quoteManagement = $quoteManagement;

        $this->logger = $logger;
        $this->lmerchantHelper = $lmerchantHelper;
    }

    public function handle(
        string $merchantId,
        string $amount,
        string $currency,
        string $merchantReference,
        string $gatewayReference,
        string $promotionReference,
        string $result,
        string $transactionType,
        string $test,
        string $message,
        string $signature
    ) {
        $this->logger->debug(__METHOD__. " Begin update for Merchant reference: {$merchantReference}");

        $callbackRequest[self::MERCHANT_ID] = isset($merchantId) ? $merchantId : "";
        $callbackRequest[self::AMOUNT] = isset($amount) ? $amount : "";
        $callbackRequest[self::CURRENCY] = isset($currency) ? $currency : "";
        $callbackRequest[self::MERCHANT_REFERENCE] = isset($merchantReference) ? $merchantReference: "";
        $callbackRequest[self::GATEWAY_REFERENCE] = isset($gatewayReference) ? $gatewayReference : "";
        $callbackRequest[self::PROMOTION_REFERENCE] = isset($promotionReference) ? $promotionReference : "";
        $callbackRequest[self::RESULT] = isset($result) ? $result : "";
        $callbackRequest[self::TRANSACTION_TYPE] = isset($transactionType) ? $transactionType : "";
        $callbackRequest[self::TEST] = isset($test) ? $test : "false";
        $callbackRequest[self::MESSAGE] = isset($message) ? $message : "";


        if (!$this->_validateRequest($callbackRequest, $signature)) {
            throw new \Magento\Framework\Webapi\Exception(__("Bad request"), 400);
        }

        $response["merchant_reference" -> $merchantReference];
        $response["gateway_reference" -> $gatewayReference];

        if ($result == LmerchantHelper::TRANSACTION_RESULT_FAILED) {
            $this->messageManager->addError($message);
            $response["success" -> true];
            
            $this->logger->error(__METHOD__. " Order Failed, merchant reference: ". merchantReference. ", gateway reference: ". $gatewayReference);
            return $response;
        }

        try {
            $quote = $this->cartRepository->get($merchantReference);
            if (!$quote->getId()) {
                $this->logger->error(__METHOD__. " Error loading quote: {$merchantReference}");
                throw new LocalizedException(__("Internal Error"));
            }

            $orderId = $this->_createOrder($request, $quote);

            $response["order_id" -> $orderId];
            $response["success" -> true];
            
            $this->logger->debug(__METHOD__. " Order Created");
            return $response;
        } catch (LocalizedException $e) {
            if (preg_match('/Invalid state change requested/i', $e->getMessage())) {
                $this->logger->debug(__METHOD__. " Ignored: Invalid state change requested ");
                return $response;
            }

            $this->logger->error(__METHOD__. " Caught LocalizedException ". $e->getMessage());
            throw new \Magento\Framework\Webapi\Exception(__("Could not update order, Bad request"), 400);
        } catch (Exception $e) {
            $this->logger->error(__METHOD__. " Caught Exception");
            throw new \Magento\Framework\Webapi\Exception(__("Unhandled Error"), 500);
        }
    }

    private function _validateRequest(array $req, string $signature)
    {
        $allowedTransactionResults = array(
            LmerchantConstants::TRANSACTION_RESULT_COMPLETED,
            LmerchantConstants::TRANSACTION_RESULT_FAILED,
        );

        $paymentGatewayConfig = $this->lmerchantHelper->getConfig();

        if (isset($signature) || empty($signature)) {
            $this->logger->error(__METHOD__. " signature is mandatory ");
            return false;
        }

        if ($req[self::MERCHANT_ID] != $paymentGatewayConfig[LmerchantHelper::MERCHANT_ID]) {
            $this->logger->error(__METHOD__. " invalid merchant id ". $merchantId);
            return false;
        }

        if ($signature != $this->lmerchantHelper->getHMAC($req)) {
            $this->logger->error(__METHOD__. " could not verify HMAC");
            return false;
        }

        if (
            !in_array($req[self::CURRENCY], LmerchantConstants::ALLOWED_CURRENCY) ||
            !in_array($req[self::RESULT], $allowedTransactionResults) ||
            $req[self::TRANSACTION_TYPE] !=  LmerchantConstants::TRANSACTION_TYPE_SALE
            ) {
            $this->logger->error(__METHOD__. " unsupported currency, result or transaction type");
            return false;
        }

        return true;
    }

    private function _createOrder(string $merchantReference, string $gatewayReference, string $promotionReference, string $result, $quote)
    {
        $this->logger->debug(__METHOD__. " Begin create order merchant ref: {$merchantReference}, gateway ref: {$gatewayReference}");
        $quoteId = $quote->getId();
        $quote->getPayment()->setMethod(LmerchantConstants::METHOD_CODE);
        $payment = $quote->getPayment();

        $payment->setAdditionalInformation(LmerchantConstants::CART_ID, $merchantReference);
        $payment->setAdditionalInformation(LmerchantConstants::GATEWAY_REFERENCE, $gatewayReference);
        $payment->setAdditionalInformation(LmerchantConstants::PROMOTION_REFERENCE, $promotionReference);
        $payment->setAdditionalInformation(LmerchantConstants::PAYMENT_RESULT, $result);
        
        $info = $payment->getAdditionalInformation();
        $quote->setPayment($payment);

        $this->quoteValidator->validateBeforeSubmit($quote);
        $payment->save();
        $quote->save();

        $this->logger->debug(__METHOD__. " Converting Quote -> Order");

        $orderId = $this->quoteManagement->placeOrder($quoteId);

        return $orderId;
    }
}
