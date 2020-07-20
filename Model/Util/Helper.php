<?php
namespace Lmerchant\Checkout\Model\Util;

use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfigInterface;
use \Magento\Framework\App\State as State;
use \Magento\Framework\App\Request\Http as Request;
use \Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;

/**
 * Class Helper
 * @package Lmerchant\Checkout\Model\Util
 */
class Helper
{
    const ACTIVE = 'active';
    const MERCHANT_ID = 'merchant_id';
    const MERCHANT_SECRET = 'merchant_secret';
    const SANDBOX_MODE = 'sandbox_mode';

    protected $scopeConfig;
    protected $state;
    protected $request;
    protected $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        State $state,
        Request $request,
        StoreManagerInterface $storeManager
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
        $config['isActive'] = boolval($this->_readConfig(self::ACTIVE));
        $config['merchantId'] = $this->_readConfig(self::MERCHANT_ID);
        $config['isSandboxMode'] = boolval($this->_readConfig(self::SANDBOX_MODE));

        return $config;
    }

    public function getHMAC($payload)
    {
        if (!is_array($payload)) {
            return '';
        }

        $secret = $this->_readConfig(self::MERCHANT_SECRET, true);

        if (!isset($secret)) {
            return "";
        }

        ksort($payload);

        $message = "";

        foreach ($payload as $key => $value) {
            $message .= $key . $value;
        }

        return hash_hmac("sha256", $message, $secret);
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
