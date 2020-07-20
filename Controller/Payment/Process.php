<?php

namespace Lmerchant\Checkout\Controller\Payment;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Quote\Api\CartRepositoryInterface as CartRepository;
use \Magento\Quote\Model\QuoteIdMaskFactory;
use \Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use \Magento\Quote\Model\QuoteValidator as QuoteValidator;

use \Lmerchant\Checkout\Model\Adapter\PaymentRequest as PaymentRequestAdapter;
use \Lmerchant\Checkout\Logger\Logger;

/**
 * Class Process
 * @package Lmerchant\Checkout\Controller\Payment
 */
class Process extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_cartRepository;
    protected $_quoteIdMaskFactory;
    protected $_jsonResultFactory;
    protected $_quoteValidator;

    protected $_paymentRequestAdaptor;
    protected $_logger;
    /**
     * Process constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CheckoutSession $checkoutSession,
        CartRepository $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        JsonResultFactory $jsonResultFactory,
        QuoteValidator $quoteValidator,
        PaymentRequestAdapter $paymentRequestAdaptor,
        Logger $logger
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_cartRepository = $cartRepository;
        $this->_quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->_jsonResultFactory = $jsonResultFactory;
        $this->_quoteValidator = $quoteValidator;
        $this->_paymentRequestAdaptor = $paymentRequestAdaptor;
        $this->_logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        // TODO: get payment flow from config
        $paymentMethod = 'authorize_capture';

        $result = $this->_processCapture();
        return $result;
    }

    public function _processCapture()
    {
        $this->_logger->info(__METHOD__. " Processing capture");
        
        $BASE_URL = 'api.dev.latitudefinancial.com/v1/applybuy-checkout-service'; //TODO: get from config based on sandbox mode

        $post = $this->getRequest()->getPostValue();
        $cartId = htmlspecialchars($post['cartId'], ENT_QUOTES);

        if (empty($cartId)) {
            $result = $this->_jsonResultFactory->create()->setData(
                ['error' => 1, 'message' => 'Invalid request']
            );

            return $result;
        }

        $data = $this->_checkoutSession->getData();
        $quote = $this->_checkoutSession->getQuote();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');

        if ($customerSession->isLoggedIn()) {
            $this->_logger->info(__METHOD__. " Customer checkout");
            $quoteId = $quote->getId();

            $this->_logger->info(__METHOD__. " cartId:{$cartId}  quoteId:{$quoteId}");

            $customerId = $customerSession->getCustomer()->getId();
            $customer = $customerRepository->getById($customerId);

            // logged in customer
            $quote->setCustomer($customer);

            $billingAddress  = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();

            // validate shipping and billing address
            if ((empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))) && (empty($billingAddress) || empty($billingAddress->getStreetLine(1)))) {

              // virtual products
                if ($quote->isVirtual()) {
                    try {
                        $billingID =  $customerSession->getCustomer()->getDefaultBilling();
                        $this->_logger->debug("No billing address for the virtual product. Adding the Customer's default billing address.");
                        $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingID);
                        $billingAddress->addData($address->getData());
                    } catch (\Exception $e) {
                        $this->_logger->debug($e->getMessage());
                        $result = $this->_jsonResultFactory->create()->setData(
                            ['success' => false, 'message' => 'Invalid billing address']
                        );

                        return $result;
                    }
                } else {
                    $result = $this->_jsonResultFactory->create()->setData(
                        ['success' => false, 'message' => 'Invalid billing address']
                    );

                    return $result;
                }
            } elseif (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {
                $billingAddress = $quote->getShippingAddress();
                $quote->setBillingAddress($quote->getShippingAddress());
                $this->_logger->debug("Invalid billing address. Using shipping address instead");

                $billingAddress->addData(array('address_type'=>'billing'));
            }
        } else {
            $this->_logger->info(__METHOD__. " Guest checkout");

            $quoteIdMask = $this->_quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $quoteId = $quoteIdMask->getQuoteId();

            $this->_logger->info(__METHOD__. " cartId:{$cartId}  quoteId:{$quoteId}");

            $quote = $this->_cartRepository->get($quoteId);
            $quote->setCheckoutMethod(\Lmerchant\Checkout\Model\Util\Constants::METHOD_GUEST);

            if (!empty($post['email'])) {
                $email = htmlspecialchars($post['email'], ENT_QUOTES);
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
                try {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $quote->setCustomerEmail($email)
                            ->setCustomerIsGuest(true)
                            ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                    }
                } catch (\Exception $e) {
                    $result = $this->_jsonResultFactory->create()->setData(
                        ['error' => 1, 'message' => $e->getMessage()]
                    );
                    return $result;
                }
            }
        }

        $quote->reserveOrderId();

        $quote->getPayment()->setMethod(\Lmerchant\Checkout\Model\Util\Constants::METHOD_CODE);

        try {
            $this->_quoteValidator->validateBeforeSubmit($quote);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $result = $this->_jsonResultFactory->create()->setData(
                ['success' => false, 'message' => $e->getMessage()]
            );
            return $result;
        }

        $this->_cartRepository->save($quote);
        $this->_checkoutSession->replaceQuote($quote);

        $this->_logger->info(__METHOD__. " Quote saved. Quote id: {$quoteId}");

        try {
            $paymentRequest = $this->_paymentRequestAdaptor->get($quote, $quoteId);
        } catch (\Exception $e) {
            $result = $this->_jsonResultFactory->create()->setData(
                ['error' => 1, 'message' => $e->getMessage()]
            );

            return $result;
        }
        
        $paymentRequest['success'] = true;
        $paymentRequest['url'] = "https://{$BASE_URL}/purchase";

        $result = $this->_jsonResultFactory->create()->setData($paymentRequest);

        return $result;
    }
}
