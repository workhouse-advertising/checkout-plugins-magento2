<?php
namespace Lmerchant\Checkout\Model\Util;

/**
 * Class Helper
 * @package Lmerchant\Checkout\Model\Util
 */
class Helper
{
    const ACTIVE = 'active';
    const MERCHANT_ID = 'merchant_id';
    const MERCHANT_SECRET = 'merchant_secret';
    const TEST_MODE = 'test_mode';

    const API_URL_TEST = 'https://api.dev.latitudefinancial.com/v1/applybuy-checkout-service';
    const API_URL_PROD = 'https://api.latitudefinancial.com/v1/applybuy-checkout-service';

    const CONTENT_API_URL_TEST = 'https://api.checkout.dev.merchant-services-np.lfscnp.com/content/checkout';
    const CONTENT_API_URL_PROD = 'https://api.checkout.test.merchant-services-np.lfscnp.com/content/checkout';

    protected $scopeConfig;
    protected $state;
    protected $request;
    protected $storeManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->state = $state;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }
    
    public function getWebsiteId()
    {
        if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $websiteId = (int) $this->request->getParam('website', 0);
            return $websiteId;
        }
        
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        return $websiteId;
    }

    public function getConfig()
    {
        $config[self::ACTIVE] = boolval($this->_readConfig(self::ACTIVE));
        $config[self::MERCHANT_ID] = $this->_readConfig(self::MERCHANT_ID);
        $config[self::TEST_MODE] = boolval($this->_readConfig(self::TEST_MODE));

        return $config;
    }

    public function isTestMode()
    {
        return boolval($this->_readConfig(self::TEST_MODE));
    }

    public function getApiUrl()
    {
        return $this->isTestMode() ? self::API_URL_TEST : self::API_URL_PROD;
    }

    public function getContentApiUrl()
    {
        return $this->isTestMode() ? self::CONTENT_API_URL_TEST : self::CONTENT_API_URL_PROD;
    }

    public function getHMAC($payload)
    {
        $message = "";

        if (!is_array($payload)) {
            return "";
        }

        $secret = $this->_readConfig(self::MERCHANT_SECRET, true);

        if (!isset($secret)) {
            return "";
        }

        ksort($payload);

        foreach ($payload as $key => $value) {
            $message .= $key . $value;
        }

        return hash_hmac("sha256", $message, $secret);
    }

    public function getPlatformVersion()
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata = $objectManager->get('\Magento\Framework\App\ProductMetadataInterface');
            return $version = $productMetadata->getVersion();
        } catch (\Exception $e) {
            return "";
        }
    }

    private function _readConfig($path, $returnRaw = false)
    {
        $websiteId = $this->getWebsiteId();
        $rootNode = 'payment/' . \Lmerchant\Checkout\Model\Payment::METHOD_CODE;
    
        if (!empty($websiteId) && $websiteId) {
            $val = $this->scopeConfig->getValue($rootNode . '/' . $path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES, $websiteId);
        } else {
            $val = $this->scopeConfig->getValue($rootNode . '/' . $path, 'default');
        }

        return $returnRaw ? $val : $this->_clean($val);
    }
    
    private function _clean($string)
    {
        $result = preg_replace("/[^a-zA-Z0-9]+/", "", $string);
        return $result;
    }
}
