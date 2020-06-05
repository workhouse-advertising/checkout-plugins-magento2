<?php
namespace Lmerchant\Checkout\Model\Util;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfigInterface;
use Magento\Framework\App\State as State;
use Magento\Framework\App\Request\Http as Request;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;

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
        $config['isActive'] = $this->_readConfig(self::ACTIVE);
        $config['merchantId'] = $this->_readConfig(self::MERCHANT_ID);
        $config['merchantSecret'] = $this->_readConfig(self::MERCHANT_SECRET);
        $config['isSandboxMode'] = $this->_readConfig(self::SANDBOX_MODE);

        return $config;
    }

    protected function _readConfig($path)
    {
        $websiteId = $this->getWebsiteId();
        $rootNode = 'payment/' . \Lmerchant\Checkout\Mode\Payment::METHOD_CODE;
    
        if (!empty($websiteId) && $websiteId) {
            return $this->_clean($this->scopeConfig->getValue($rootNode . '/' . $path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES, $websiteId));
        } else {
            return $this->_clean($this->scopeConfig->getValue($rootNode . '/' . $path, 'default'));
        }
    }
    
    private function _clean($string)
    {
        $result = preg_replace("/[^a-zA-Z0-9]+/", "", $string);
        return $result;
    }
}
