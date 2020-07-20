<?php
namespace Lmerchant\Checkout\Model\Quote;

use Magento\Quote\Api\CartRepositoryInterface as CartRepository;
use Magento\Quote\Model\QuoteValidator;
use Magento\Quote\Model\QuoteManagement;
use Magento\Framework\Exception\LocalizedException;

use Lmerchant\Checkout\Logger\Logger;
use Lmerchant\Checkout\Api\QuoteInterface;
use Lmerchant\Checkout\Api\Data\QuoteRequestInterface;
use Lmerchant\Checkout\Model\Quote\QuoteResponse;

class Quote
{
    protected $logger;
    protected $cartRepository;
    protected $quoteValidator;
    protected $quoteManagement;
    protected $transactionBuilder;

    public function __construct(
        Logger $logger,
        CartRepository $cartRepository,
        QuoteValidator $quoteValidator,
        QuoteManagement $quoteManagement
    ) {
        $this->logger = $logger;
        $this->cartRepository = $cartRepository;
        $this->quoteValidator = $quoteValidator;
        $this->quoteManagement = $quoteManagement;
    }
    
    public function update(QuoteRequestInterface $request)
    {
        $this->logger->info(__METHOD__. " Begin update for Merchant reference: {$request->getMerchantReference()}");

        $response = new QuoteResponse();

        try {
            // TODO: make sure signature is valid and all fields are there
            $response->setOrderReference($request->getMerchantReference());

            if ($request->getResult() !== 'completed') {
                throw new LocalizedException(__('Invalid request'));
            }

            // TODO: validate order amount
            $quote = $this->cartRepository->get($request->getMerchantReference());

            if (!$quote->getId()) {
                $this->logger->info(__METHOD__. " Error loading quote: {$request->getMerchantReference()}");
                throw new LocalizedException(__("Internal Error"));
            }

            // TODO: check for payment flow
            $this->_createOrder($request, $quote);
            
            $this->logger->info(__METHOD__. " Order Created");

            // TODO: send email
            $response->setSuccess(true);
            return $response;
        } catch (LocalizedException $e) {
            if (preg_match('/Invalid state change requested/i', $e->getMessage())) {
                $this->logger->info(__METHOD__. " Ignored: Invalid state change requested ");
                $response->setSuccess(true);

                return $response;
            }

            $this->logger->error(__METHOD__. " Caught LocalizedException ". $e->getMessage());
            $response->setSuccess(false);
            $response->setMessage($e->getMessage());

            return $response;
        } catch (Exception $e) {
            $this->logger->error(__METHOD__. " Caught Exception");
            $response->setSuccess(false);
            $response->setMessage('Internal error');

            return $response;
        }
    }

    private function _createOrder($request, $quote)
    {
        $this->logger->info(__METHOD__. " Begin create order");
        $quoteId = $quote->getId();
        $quote->getPayment()->setMethod(\Lmerchant\Checkout\Model\Util\Constants::METHOD_CODE);
        $payment = $quote->getPayment();

        $payment->setAdditionalInformation(\Lmerchant\Checkout\Model\Util\Constants::CART_ID, $request->getMerchantReference());
        $payment->setAdditionalInformation(\Lmerchant\Checkout\Model\Util\Constants::GATEWAY_REFERENCE, $request->getGatewayReference());
        $payment->setAdditionalInformation(\Lmerchant\Checkout\Model\Util\Constants::PAYMENT_STATUS, $request->getResult());
        $payment->setAdditionalInformation(\Lmerchant\Checkout\Model\Util\Constants::PROMOTION_REFERENCE, $request->getPromotionReference());
        
        $info = $payment->getAdditionalInformation();

        $quote->setPayment($payment);
        $this->quoteValidator->validateBeforeSubmit($quote);

        $payment->save();
        $quote->save();

        $this->logger->info(__METHOD__. " Converting Quote -> Order");

        $orderId = $this->quoteManagement->placeOrder($quoteId);

        return $orderId;
    }
}
