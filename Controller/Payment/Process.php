<?php

namespace Latitude\Checkout\Controller\Payment;

use \Magento\Framework\Exception as Exception;
use \Magento\Framework\Exception\LocalizedException as LocalizedException;

use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;
use \Latitude\Checkout\Model\Util\Helper as LatitudeHelper;

/**
 * Class Process
 * @package Latitude\Checkout\Controller\Payment
 */
class Process extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_cartRepository;
    protected $_quoteIdMaskFactory;
    protected $_jsonResultFactory;
    protected $_quoteValidator;

    protected $_latitudeHelper;
    protected $_paymentRequestAdaptor;
    protected $_logger;

    /**
     * Process constructor.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        LatitudeHelper $latitudeHelper,
        \Latitude\Checkout\Model\Adapter\PaymentRequest $paymentRequestAdaptor,
        \Latitude\Checkout\Logger\Logger $logger
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_cartRepository = $cartRepository;
        $this->_quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->_jsonResultFactory = $jsonResultFactory;
        $this->_quoteValidator = $quoteValidator;

        $this->_latitudeHelper = $latitudeHelper;
        $this->_paymentRequestAdaptor = $paymentRequestAdaptor;
        $this->_logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $paymentMethod = 'authorize_capture';

        try {
            $paymentRequest = $this->_processCapture();

            $paymentRequest['success'] = true;
            $paymentRequest['url'] = $this->_latitudeHelper->getApiUrl(). "/purchase";

            $result = $this->_jsonResultFactory->create()->setData($paymentRequest);

            return $result;
        } catch (LocalizedException $locallizedException) {
            return $this->_processError($locallizedException);
        } catch (Exception $exception) {
            return $this->_processError($exception);
        }
    }

    private function _errorResponse(Exception $e)
    {
        $this->logger->error(__METHOD__. $e->getMessage());

        $result = $this->jsonResultFactory->create();
        $result->setData(['error' => true, 'message' => __("Could not process request. Check logs for more details")]);

        return $result;
    }

    public function _processCapture()
    {
        $this->_logger->debug(__METHOD__. " Processing capture");
        
        $post = $this->getRequest()->getPostValue();
        $cartId = htmlspecialchars($post['cartId'], ENT_QUOTES);

        if (empty($cartId)) {
            throw new Exception(__("Invalid request"));
        }

        $data = $this->_checkoutSession->getData();
        $quote = $this->_checkoutSession->getQuote();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');

        if ($customerSession->isLoggedIn()) {
            $this->_logger->debug(__METHOD__. " Customer checkout");
            $quoteId = $quote->getId();

            $this->_logger->debug(__METHOD__. " cartId:{$cartId}  quoteId:{$quoteId}");

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
                    $billingID =  $customerSession->getCustomer()->getDefaultBilling();
                    $this->_logger->debug("No billing address for the virtual product. Adding the Customer's default billing address.");
                    $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingID);
                    $billingAddress->addData($address->getData());
                } else {
                    throw new Exception(__("Invalid billing address"));
                }
            } elseif (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {
                $billingAddress = $quote->getShippingAddress();
                $quote->setBillingAddress($quote->getShippingAddress());
                $this->_logger->debug("Invalid billing address. Using shipping address instead");

                $billingAddress->addData(array('address_type'=>'billing'));
            }
        } else {
            $this->_logger->debug(__METHOD__. " Guest checkout");

            $quoteIdMask = $this->_quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $quoteId = $quoteIdMask->getQuoteId();

            $this->_logger->debug(__METHOD__. " cartId:{$cartId}  quoteId:{$quoteId}");

            $quote = $this->_cartRepository->get($quoteId);
            $quote->setCheckoutMethod(LatitudeConstants::METHOD_GUEST);

            if (!empty($post['email'])) {
                $email = htmlspecialchars($post['email'], ENT_QUOTES);
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);

                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $quote->setCustomerEmail($email)
                        ->setCustomerIsGuest(true)
                        ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                }
            }
        }

        $quote->reserveOrderId();

        $quote->getPayment()->setMethod(LatitudeConstants::METHOD_CODE);

        $this->_quoteValidator->validateBeforeSubmit($quote);
        $this->_cartRepository->save($quote);
        $this->_checkoutSession->replaceQuote($quote);

        $this->_logger->info(__METHOD__. " Quote saved. Quote id: {$quoteId}");

        $paymentRequest = $this->_paymentRequestAdaptor->get($quote, $quoteId);

        return $paymentRequest;
    }
}
