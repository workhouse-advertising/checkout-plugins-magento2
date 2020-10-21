<?php
namespace Lmerchant\Checkout\Model\Adapter;

use \Lmerchant\Checkout\Model\Util\Constants as LmerchantConstants;
use \Lmerchant\Checkout\Model\Util\Helper as LmerchantHelper;

/**
 * Class PaymentRequest
 * @package Lmerchant\Checkout\Model\Adapter
 */
class PaymentRequest
{
    protected $_storeManagerInterface;
    protected $_productRepositoryInterface;
    protected $_lmerchantHelper;

    /**
     * PaymentRequest constructor.
     * @param StoreManagerInterface $storeManagerInterface
     * @param ProductRepositoryInterface $productRepositoryInterface
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        LmerchantHelper $lmerchantHelper
    ) {
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_productRepositoryInterface = $productRepositoryInterface;
        $this->_lmerchantHelper = $lmerchantHelper;
    }

    /**
     * @param $quote
     * @return object PaymentRequest
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($quote, $quoteId)
    {
        $paymentRequest = $this->_createRequest($quote, $quoteId);
        $this->validateAddress($paymentRequest);
        return $paymentRequest;
    }

    public function validateAddress($paymentRequest)
    {
        $errors = [];
       
        if (empty($paymentRequest['x_billing_line1'])) {
            $errors[] = 'Invalid billing line 1';
        }
        if (empty($paymentRequest['x_billing_area1'])) {
            $errors[] = 'Invalid area 1';
        }
        if (!empty($paymentRequest['x_billing_region']) && strlen(trim($paymentRequest['x_billing_region'])) < 2) {
            $errors[] = "Invalid billing region";
        }
        if (empty($paymentRequest['x_billing_postcode']) || strlen(trim($paymentRequest['x_billing_postcode'])) < 3) {
            $errors[] = 'Invalid billing postcode';
        }
        if (empty($paymentRequest['x_billing_country_code'])) {
            $errors[] = 'Invalid country code';
        }
        
        if (count($errors)) {
            throw new \Exception(__(implode($errors, ' ; ')));
        } else {
            return true;
        }
    }

    /**
     * Create payment request
     *
     * @param $quote
     * @param array $quoteId
     * @return array
     */
    protected function _createRequest($quote, $quoteId)
    {
        $precision = 2;

        $baseUrl = $this->_storeManagerInterface->getStore($quote->getStore()->getId())->getBaseUrl();

        $additionalData = $quote->getData();

        $email = $quote->getCustomerEmail();

        $billingAddress  = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        $paymentRequest['x_amount'] = round((float)$quote->getGrandTotal(), $precision);
        $paymentRequest['x_currency'] = (string)$additionalData['store_currency_code'];

        $paymentRequest['x_customer_first_name'] = $quote->getCustomerFirstname() ? (string)$quote->getCustomerFirstname() : $billingAddress->getFirstname();
        $paymentRequest['x_customer_last_name'] = $quote->getCustomerLastname() ? (string)$quote->getCustomerLastname() : $billingAddress->getLastname();
        $paymentRequest['x_customer_phone'] = (string)$billingAddress->getTelephone();
        $paymentRequest['x_customer_email'] = (string)$email;
        
        $paymentRequest['x_merchant_reference'] = isset($quoteId) ? $quoteId : $quote->getIncrementId();

        $paymentRequest['x_url_cancel'] = $baseUrl . LmerchantConstants::CANCEL_ROUTE;
        $paymentRequest['x_url_callback'] = $baseUrl . LmerchantConstants::CALLBACK_ROUTE;
        $paymentRequest['x_url_complete'] = $baseUrl . LmerchantConstants::COMPLETE_ROUTE . "?reference={$quoteId}";

        if (!empty($shippingAddress) && !empty($shippingAddress->getStreetLine(1))) {
            $paymentRequest['x_shipping_name'] = (string)$shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname();
            $paymentRequest['x_shipping_line1'] = (string)$shippingAddress->getStreetLine(1);
            $paymentRequest['x_shipping_line2'] = (string)$shippingAddress->getStreetLine(2);
            $paymentRequest['x_shipping_area1'] = (string)$shippingAddress->getCity();
            $paymentRequest['x_shipping_postcode'] = (string)$shippingAddress->getPostcode();
            $paymentRequest['x_shipping_region'] = (string)$shippingAddress->getRegion();
            $paymentRequest['x_shipping_country_code'] = (string)$shippingAddress->getCountryId();
            $paymentRequest['x_shipping_phone'] = (string)$shippingAddress->getTelephone();
        }

        $paymentRequest['x_billing_name'] = (string)$billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $paymentRequest['x_billing_line1'] = (string)$billingAddress->getStreetLine(1);
        $paymentRequest['x_billing_line2'] = (string)$billingAddress->getStreetLine(2);
        $paymentRequest['x_billing_area1'] = (string)$billingAddress->getCity();
        $paymentRequest['x_billing_postcode'] = (string)$billingAddress->getPostcode();
        $paymentRequest['x_billing_region'] = (string)$billingAddress->getRegion();
        $paymentRequest['x_billing_country_code'] = (string)$billingAddress->getCountryId();
        $paymentRequest['x_billing_phone'] = (string)$billingAddress->getTelephone();

        if (is_array($quote->getAllVisibleItems())) {
            $paymentRequest['x_line_item_count'] = count($quote->getAllVisibleItems());

            foreach ($quote->getAllVisibleItems() as $key => $item) {
                if (!$item->getParentItem()) {
                    $product = $this->_productRepositoryInterface->getById($item->getProductId());
                    
                    $paymentRequest['x_lineitem_' . $key . '_name'] = (string)$item->getName();
                    $paymentRequest['x_lineitem_' . $key . '_sku'] = (string)$item->getSku();
                    $paymentRequest['x_lineitem_' . $key . '_quantity'] = (string)$item->getQty();
                    $paymentRequest['x_lineitem_' . $key . '_amount'] = round((float)$item->getQty() * (float)$item->getPriceInclTax(), $precision);
                    $paymentRequest['x_lineitem_' . $key . '_image_url'] = (string)$item->getProductUrl();
                    $paymentRequest['x_lineitem_' . $key . '_tax'] = round((float)$item->getPrice(), $precision) * round((float)($item->getTaxPercent() / 100), $precision);
                    $paymentRequest['x_lineitem_' . $key . '_unit_price'] = round((float)$item->getPrice(), $precision);
                    $paymentRequest['x_lineitem_' . $key . '_requires_shipping'] = $quote->isVirtual() ? "false" : "true";
                    $paymentRequest['x_lineitem_' . $key . '_gift_card'] = $quote->isVirtual() ? "true" : "false";
                }
            }
        }

        if ($quote->getShippingAddress()->getShippingAmount()) {
            $paymentRequest['x_shipping_amount'] = round((float)$quote->getShippingAddress()->getShippingAmount(), $precision);
        }

        if (isset($additionalData['discount_amount'])) {
            $paymentRequest['x_discount_amount'] = round((float)$additionalData['discount_amount'], $precision);
        }

        $taxAmount = array_key_exists('tax_amount', $additionalData) ? $additionalData['tax_amount'] : $shippingAddress->getTaxAmount();
        $paymentRequest['x_tax_amount'] = isset($taxAmount) ? round((float)$taxAmount, $precision) : 0;

        $paymentGatewayConfig =  $this->_lmerchantHelper->getConfig();

        $paymentRequest['x_merchant_id'] = $paymentGatewayConfig[LmerchantHelper::MERCHANT_ID];
        $paymentRequest['x_test'] = $paymentGatewayConfig[LmerchantHelper::TEST_MODE] ? "true" : "false";

        $paymentRequest['x_platform_type'] = LmerchantConstants::PLATFORM_TYPE;
        $paymentRequest['x_platform_version'] =$this->_lmerchantHelper->getPlatformVersion();
        $paymentRequest['x_plugin_version'] = LmerchantConstants::PLUGIN_VERSION;

        $paymentRequest['x_signature'] = $this->_lmerchantHelper->getHMAC($paymentRequest);

        return $paymentRequest;
    }
}
