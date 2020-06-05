<?php
namespace Lmerchant\Checkout\Model\Adapter;

use \Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use \Magento\Catalog\Api\ProductRepositoryInterface as ProductRepositoryInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Magento\Directory\Model\CountryFactory as CountryFactory;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

use \Lmerchant\Checkout\Model\Util\Helper as LmerchantHelper;

/**
 * Class PaymentRequest
 * @package Lmerchant\Checkout\Model\Adapter
 */
class PaymentRequest
{
    protected $_storeManagerInterface;
    protected $_productRepositoryInterface;
    protected $_jsonHelper;
    protected $_countryFactory;
    protected $_scopeConfig;
    protected $_lmerchantHelper;

    /**
     * PaymentRequest constructor.
     * @param StoreManagerInterface $storeManagerInterface
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param JsonHelper $jsonHelper
     * @param CountryFactory $countryFactory
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManagerInterface,
        ProductRepositoryInterface $productRepositoryInterface,
        JsonHelper $jsonHelper,
        CountryFactory $countryFactory,
        ScopeConfig $scopeConfig,
        LmerchantHelper $lmerchantHelper
    ) {
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_productRepositoryInterface = $productRepositoryInterface;
        $this->_jsonHelper = $jsonHelper;
        $this->_countryFactory = $countryFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_lmerchantHelper = $lmerchantHelper;
    }

    /**
     * @param $quote
     * @return object PaymentRequest
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($quote, $merchantOrderId)
    {
        $paymentRequest = $this->_createRequest($quote, $merchantOrderId);
        $this->validateAddress($paymentRequest);
        return $paymentRequest;
    }

    public function validateAddress($paymentRequest)
    {
        $errors = [];
       
	    if (empty($paymentRequest['billing_line1'])) {
            $errors[] = 'Invalid billing line 1';
        } 
		if (empty($paymentRequest['billing_area1'])) {
            $errors[] = 'Invalid area 1';
        }
        if(!empty($paymentRequest['billing_region']) && strlen(trim($paymentRequest['billing_region'])) < 2){
            $errors[] = "Invalid billing region";
        }
		if (empty($paymentRequest['billing_postcode']) || strlen(trim($paymentRequest['billing_postcode'])) < 3) {
            $errors[] = 'Invalid billing postcode';
        }
		if (empty($paymentRequest['billing_country_code'])) {
            $errors[] = 'Invalid country code';
        } 
		
        if (count($errors)) {
            throw new \Magento\Framework\Exception\LocalizedException(__(implode($errors, ' ; ')));
        } else {
            return true;
        }
    }

    /**
     * Create payment request
     *
     * @param $quote
     * @param array $merchantOrderId
     * @return array
     */
    protected function _createRequest($quote, $merchantOrderId)
    {
        $precision = 2;
        $urlCallback = $this->_storeManagerInterface->getStore($quote->getStore()->getId())->getBaseUrl() . 'lmerchant/payment/response'; ;

        $additionalData = $quote->getData();

        $email = $quote->getCustomerEmail();

        $billingAddress  = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        $paymentRequest['amount'] = round((float)$quote->getGrandTotal(), $precision);
        $paymentRequest['currency'] = (string)$additionalData['store_currency_code'];

        $paymentRequest['customer_first_name'] = $quote->getCustomerFirstname() ? (string)$quote->getCustomerFirstname() : $billingAddress->getFirstname();
        $paymentRequest['customer_last_name'] = $quote->getCustomerLastname() ? (string)$quote->getCustomerLastname() : $billingAddress->getLastname();
        $paymentRequest['customer_phone'] = (string)$billingAddress->getTelephone();
        $paymentRequest['customer_email'] = (string)$email;
        
        $paymentRequest['merchant_reference'] = isset($merchantOrderId) ? $merchantOrderId : $quote->getIncrementId();

        $paymentRequest['url_callback'] = $urlCallback;
        $paymentRequest['url_confirm'] = $urlCallback; 
        $paymentRequest['url_cancel'] = $urlCallback;

        if(!empty($shippingAddress) && !empty($shippingAddress->getStreetLine(1)))
		{
            $paymentRequest['shipping_name'] = (string)$shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname();
            $paymentRequest['shipping_line1'] = (string)$shippingAddress->getStreetLine(1);
            $paymentRequest['shipping_line2'] = (string)$shippingAddress->getStreetLine(2);
            $paymentRequest['shipping_area1'] = (string)$shippingAddress->getCity();
            $paymentRequest['shipping_postcode'] = (string)$shippingAddress->getPostcode();
            $paymentRequest['shipping_region'] = (string)$shippingAddress->getRegion();
            $paymentRequest['shipping_country_code'] = (string)$shippingAddress->getCountryId();
            $paymentRequest['shipping_phone'] = (string)$shippingAddress->getTelephone();
        }

        $paymentRequest['billing_name'] = (string)$billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $paymentRequest['billing_line1'] = (string)$billingAddress->getStreetLine(1);
        $paymentRequest['billing_line2'] = (string)$billingAddress->getStreetLine(2);
        $paymentRequest['billing_area1'] = (string)$billingAddress->getCity();
        $paymentRequest['billing_postcode'] = (string)$billingAddress->getPostcode();
        $paymentRequest['billing_region'] = (string)$billingAddress->getRegion();
        $paymentRequest['billing_country_code'] = (string)$billingAddress->getCountryId();
        $paymentRequest['billing_phone'] = (string)$billingAddress->getTelephone();

        if(is_array($quote->getAllVisibleItems())) {
            $paymentRequest['line_item_count'] = count($quote->getAllVisibleItems());

            foreach ($quote->getAllVisibleItems() as $key => $item) {
                if (!$item->getParentItem()) {
                    $product = $this->_productRepositoryInterface->getById($item->getProductId());
                    
                    $paymentRequest['lineitem_' . $key . '_name'] = (string)$item->getName();
                    $paymentRequest['lineitem_' . $key . '_sku'] = (string)$item->getSku();
                    $paymentRequest['lineitem_' . $key . '_quantity'] = (string)$item->getQty();
                    $paymentRequest['lineitem_' . $key . '_amount'] = round((float)$item->getPriceInclTax(), $precision);
                }
            }
        }

        if ($quote->getShippingInclTax()) {
            $paymentRequest['shipping_amount'] = round((float)$quote->getShippingInclTax(), $precision);
        }

        if (isset($additionalData['discount_amount'])) {
            $paymentRequest['discount_amount'] = round((float)$additionalData['discount_amount'], $precision);
        }

        $taxAmount = array_key_exists('tax_amount', $additionalData) ? $additionalData['tax_amount'] : $shippingAddress->getTaxAmount();
        $paymentRequest['tax_amount'] = isset($taxAmount) ? round((float)$taxAmount, $precision) : 0;

        $paymentGatewayConfig = $lmerchantHelper->getConfig();

        $paymentRequest['merchant_id'] = $paymentGatewayConfig['merchantId'];
        $paymentRequest['test'] = $paymentGatewayConfig['isSandboxMode'];

        return $paymentRequest;
    }
}