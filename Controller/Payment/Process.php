<?php

namespace Lmerchant\Checkout\Controller\Payment;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Sales\Model\OrderFactory as OrderFactory;
use \Magento\Quote\Model\QuoteFactory as QuoteFactory;
use \Magento\Payment\Model\Method\AbstractMethod;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Magento\Checkout\Model\Cart as Cart;
use \Magento\Store\Model\StoreResolver as StoreResolver;
use \Magento\Quote\Model\ResourceModel\Quote as QuoteRepository;
use \Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use \Magento\Quote\Model\QuoteValidator as QuoteValidator;
use \Lmerchant\Checkout\Model\Adapter\PaymentRequest as PaymentRequestAdapter;
/**
 * Class Process
 * @package Lmerchant\Checkout\Controller\Payment
 */
class Process extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_quoteFactory;
    protected $_jsonHelper;
    protected $_cart;
    protected $_storeResolver;
    protected $_quoteRepository;
    protected $_jsonResultFactory;
    protected $_quoteValidator;
    protected $_paymentRequestAdaptor;

    /**
     * Process constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        JsonHelper $jsonHelper,
        Cart $cart,
        StoreResolver $storeResolver,
        QuoteRepository $quoteRepository,
        JsonResultFactory $jsonResultFactory,
        QuoteValidator $quoteValidator,
        PaymentRequestAdapter $paymentRequestAdaptor
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_jsonHelper = $jsonHelper;
        $this->_cart = $cart;
        $this->_storeResolver = $storeResolver;
        $this->_quoteRepository = $quoteRepository;
        $this->_jsonResultFactory = $jsonResultFactory;
        $this->_quoteValidator = $quoteValidator;
        $this->_paymentRequestAdaptor = $paymentRequestAdaptor;

        parent::__construct($context);
    }

    public function execute()
    {
        // TODO: get from config
        $paymentMethod = 'authorize_capture'; 

        // if ($paymentMethod == AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
        //     $result = $this->_processAuthorizeCapture();
        // }
        
        $result = $this->_processAuthorizeCapture();
        return $result;
    }

    public function _processAuthorizeCapture()
    {
        $API_BASE_URL = 'merchant-api.lmerchant.com'; //TODO: get from config based on sandbox mode

        $data = $this->_checkoutSession->getData();
        $quote = $this->_checkoutSession->getQuote();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');

        if ($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
            $customer = $customerRepository->getById($customerId);

            // logged in customer
            $quote->setCustomer($customer);

            $billingAddress  = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();

            // validate shipping and billing address
            if ((empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))) && (empty($billingAddress) || empty($billingAddress->getStreetLine(1)))) {

              // virtual products
              if($quote->isVirtual()){
	            try{
		           $billingID =  $customerSession->getCustomer()->getDefaultBilling();
		           $this->_helper->debug("No billing address for the virtual product. Adding the Customer's default billing address.");
		           $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingID);
		           $billingAddress->addData($address->getData());

	            }catch(\Exception $e){
		            $this->_helper->debug($e->getMessage());
		            $result = $this->_jsonResultFactory->create()->setData(
		              ['success' => false, 'message' => 'Invalid billing address']
		            );

		          return $result;
	            }
              }else{
	              $result = $this->_jsonResultFactory->create()->setData(
		            ['success' => false, 'message' => 'Invalid billing address']
	              );

	              return $result;
                }

            }
            elseif (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {

                $billingAddress = $quote->getShippingAddress();
                $quote->setBillingAddress($quote->getShippingAddress());
                $this->_helper->debug("Invalid billing address. Using shipping address instead");

                $billingAddress->addData(array('address_type'=>'billing'));
            }
        } else {
            $post = $this->getRequest()->getPostValue();

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

        $payment = $quote->getPayment();

        // TODO: get from config
        $payment->setMethod('LMERCHANT_INTEREST_FREE_PAYMENT');
        $quote->reserveOrderId();

        try {
            $paymentRequest = $this->_paymentRequestAdaptor->get($quote, $quote->getReservedOrderId());
        } catch (\Exception $e) {
            $result = $this->_jsonResultFactory->create()->setData(
                ['error' => 1, 'message' => $e->getMessage()]
            );

            return $result;
        }

		try{
			$this->_quoteValidator->validateBeforeSubmit($quote);
		}
		catch(\Magento\Framework\Exception\LocalizedException $e){
			 $result = $this->_jsonResultFactory->create()->setData(
				['success' => false, 'message' => $e->getMessage()]
			  );
			return $result;
        }
        
		$this->_quoteRepository->save($quote);
        $this->_checkoutSession->replaceQuote($quote);
        
        $paymentRequest['success'] = true;
        $paymentRequest['url'] = 'https://' . $API_BASE_URL . '/magento/order';

        $result = $this->_jsonResultFactory->create()->setData($paymentRequest);

        return $result;
    }
}