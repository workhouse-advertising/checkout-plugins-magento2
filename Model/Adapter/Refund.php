<?php
namespace Latitude\Checkout\Model\Adapter;

use \Magento\Framework\Exception\LocalizedException as LocalizedException;
use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;
use \Latitude\Checkout\Model\Util\Helper as LatitudeHelper;
use \Latitude\Checkout\Model\Util\Convert as LatitudeConvert;

class Refund
{
    protected $logger;
    protected $latitudeHelper;
    protected $latitudeConvert;
    protected $latitudeCheckoutService;

    const ERROR = "error";
    const MESSAGE = "message";
    const BODY = "body";
    const RESULT = "result";

    public function __construct(
        \Latitude\Checkout\Logger\Logger $logger,
        LatitudeHelper $latitudeHelper,
        LatitudeConvert $latitudeConvert,
        \Latitude\Checkout\Model\CheckoutService $latitudeCheckoutService
    ) {
        $this->logger = $logger;
        $this->latitudeHelper = $latitudeHelper;
        $this->latitudeConvert = $latitudeConvert;
        $this->latitudeCheckoutService = $latitudeCheckoutService;
    }

    public function process(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->logger->info(__METHOD__ .
            " initiating refund");

        try {
            $order = $payment->getOrder();
            $creditMemo = $payment->getCreditmemo();

            $parentTransactionId = $payment->getParentTransactionId();

            $gatewayReference = $payment->getAdditionalInformation(LatitudeConstants::GATEWAY_REFERENCE);
            $transactionReference = $payment->getAdditionalInformation(LatitudeConstants::TRANSACTION_REFERENCE);
            $promotionReference = $payment->getAdditionalInformation(LatitudeConstants::PROMOTION_REFERENCE);

            if (empty($parentTransactionId)) {
                return $this->_handleError("failed to parent transaction id.");
            }

            if (empty($order)) {
                return $this->_handleError("failed to get order.");
            }

            if (empty($creditMemo)) {
                return $this->_handleError("failed to get credit memo.");
            }

            if (empty($gatewayReference) || empty($transactionReference) || empty($promotionReference)) {
                $encodedAdditionalInfo = json_encode($payment->getAdditionalInformation());
                return $this->_handleError("failed to get additional info ". $encodedAdditionalInfo);
            }

            if (empty($payment->getParentTransactionId())) {
                return $this->_handleError("parent transaction id is empty");
            }

            if ($order->getPayment()->getMethod() != LatitudeConstants::METHOD_CODE) {
                return $this->_handleError("invalid method code");
            }

            $this->logger->info(__METHOD__ . " preparing request");
        
            $refundRequest = $this->_prepareRequest($amount, $order, $creditMemo, $parentTransactionId, $gatewayReference);
            $refundResponse = $this->latitudeCheckoutService->post("/refund", $refundRequest);

            if ($refundResponse[self::REFUND]) {
                return $this->_handleError($refundResponse[self::MESSAGE]);
            }

            if ($refundResponse[self::BODY][self::RESULT] != LatitudeConstants::TRANSACTION_RESULT_COMPLETED) {
                return $this->_handleError($refundResponse[self::BODY][self::REFUND]);
            }

            return $this->_handleSuccess($refundResponse[self::BODY]);
        } catch (LocalizedException $le) {
            return $this->_handleError($le->getRawMessage());
        } catch (\Exception $e) {
            return $this->_handleError($e->getMessage());
        }
    }

    private function _handleSuccess($body)
    {
        return [
            self::REFUND => false,
            self::BODY => $body
        ];
    }

    private function _handleError($message)
    {
        $this->logger->error(__METHOD__. " ". $message);

        return [
            self::REFUND => true,
            self::MESSAGE => $message
        ];
    }

    private function _prepareRequest($amount, $order, $creditMemo, $parentTransactionId, $gatewayReference)
    {
        $paymentGatewayConfig = $this->latitudeHelper->getConfig();

        $request = [
            "merchantId" => $paymentGatewayConfig[LatitudeHelper::MERCHANT_ID],
            "isTest" =>  $paymentGatewayConfig[LatitudeHelper::TEST_MODE],
            "gatewayReference" => $gatewayReference,
            "merchantReference" => $parentTransactionId,
            "amount" => $this->latitudeConvert->toPrice($amount),
            "currency" => $order->getOrderCurrencyCode(),
            "type" => LatitudeConstants::TRANSACTION_TYPE_REFUND,
            "description" => "",
            "platformType" => LatitudeConstants::PLATFORM_TYPE,
            "platformVersion" => $this->latitudeHelper->getPlatformVersion(),
            "pluginVersion" => $this->latitudeHelper->getVersion(),
        ];

        return $request;
    }
}
