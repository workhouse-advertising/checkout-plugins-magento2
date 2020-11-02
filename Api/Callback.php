<?php
namespace Latitude\Checkout\Api;

use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;
use \Latitude\Checkout\Model\Util\Helper as LatitudeHelper;

class Callback
{
    protected $request;
    protected $jsonHelper;
    protected $jsonResultFactory;
    protected $eventManager;

    protected $logger;
    protected $orderAdapter;
    protected $latitudeHelper;

    const ORDER_ID = 'order_id';
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
    const TIMESTAMP = 'timestamp';
    const SIGNATURE = 'signature';

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Latitude\Checkout\Logger\Logger $logger,
        \Latitude\Checkout\Model\Adapter\Order $orderAdapter,
        LatitudeHelper $latitudeHelper
    ) {
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->eventManager = $eventManager;

        $this->logger = $logger;
        $this->orderAdapter = $orderAdapter;
        $this->latitudeHelper = $latitudeHelper;
    }

    /**
     * {@inheritdoc}
     */

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
        string $timestamp,
        string $signature
    ) {
        $post[self::MERCHANT_ID] = $merchantId;
        $post[self::AMOUNT] = $amount;
        $post[self::CURRENCY] = $currency;
        $post[self::MERCHANT_REFERENCE] = $merchantReference;
        $post[self::GATEWAY_REFERENCE] = $gatewayReference;
        $post[self::PROMOTION_REFERENCE] = $promotionReference;
        $post[self::RESULT] = $result;
        $post[self::TRANSACTION_TYPE] = $transactionType;
        $post[self::TEST] = $test;
        $post[self::MESSAGE] = $message;
        $post[self::TIMESTAMP] = $timestamp;

        $this->logger->debug(__METHOD__. " Begin callback with request: " . $this->jsonHelper->jsonEncode($post));

        try {
            if (!$this->_validateRequest($post, $signature)) {
                throw new \Exception(__("Could not Validate Request"));
            }

            if ($post[self::RESULT] == LatitudeConstants::TRANSACTION_RESULT_FAILED) {
                $this->orderAdapter->addError($post[self::MERCHANT_REFERENCE], "Order failed with message ". $post[self::MESSAGE]);
                $this->_dispatch($post, false);
                return $gatewayReference;
            }

            $orderId = $this->orderAdapter->create(
                $post[self::MERCHANT_REFERENCE],
                $post[self::GATEWAY_REFERENCE],
                $post[self::PROMOTION_REFERENCE]
            );

            $this->logger->info(__METHOD__. " Order Created");
            $this->_dispatch($post, true);

            return $orderId;
        } catch (\Exception $e) {
            if (preg_match('/Invalid state change requested/i', $e->getMessage())) {
                $this->logger->debug(__METHOD__. " Ignored: Invalid state change requested ");
                return $gatewayReference;
            }

            $this->logger->error(__METHOD__. " ". $e->getMessage());
            throw new \Magento\Framework\Webapi\Exception(__('Bad Request'), 400);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__. " ". $e->getMessage());
            throw new \Magento\Framework\Webapi\Exception(__('Bad Request'), 400);
        }
    }

    private function _validateRequest($post, $signature)
    {
        if (empty($signature) || $signature != $this->latitudeHelper->getHMAC($post)) {
            $this->logger->debug(__METHOD__. " actual: ". $signature);
            $this->logger->debug(__METHOD__. " expected: ". $this->latitudeHelper->getHMAC($post));
            $this->logger->error(__METHOD__. " Could not verify HMAC");
            return false;
        }

        $paymentGatewayConfig = $this->latitudeHelper->getConfig();
        if ($post[self::MERCHANT_ID] != $paymentGatewayConfig[LatitudeHelper::MERCHANT_ID]) {
            $this->logger->error(__METHOD__. " Unexpected merchant id ". $post[self::MERCHANT_ID]);
            return false;
        }

        if (!in_array($post[self::CURRENCY], LatitudeConstants::ALLOWED_CURRENCY)) {
            $this->logger->error(__METHOD__. " Unsupported currency");
            return false;
        }
        
        if (!in_array($post[self::RESULT], array(
                LatitudeConstants::TRANSACTION_RESULT_COMPLETED,
                LatitudeConstants::TRANSACTION_RESULT_FAILED
            ))) {
            $this->logger->error(__METHOD__. " Unsupported result");
            return false;
        }

        if (!in_array($post[self::TRANSACTION_TYPE], array(
                LatitudeConstants::TRANSACTION_TYPE_SALE
            ))) {
            $this->logger->error(__METHOD__. " Unsupported transaction type");
            return false;
        }

        return true;
    }

    private function _dispatch($post, $isCompleted)
    {
        $this->eventManager->dispatch(
            $isCompleted ? LatitudeConstants::EVENT_COMPLETED : LatitudeConstants::EVENT_FAILED,
            [
                'quote' => $post[self::MERCHANT_REFERENCE],
                'merchant_reference' => $post[self::GATEWAY_REFERENCE],
                'transaction_type' => $post[self::TRANSACTION_TYPE],
                'result' => $post[self::RESULT]
            ]
        );
    }
}
