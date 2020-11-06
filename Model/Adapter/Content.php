<?php
namespace Latitude\Checkout\Model\Adapter;

class Content
{
    protected $storeManager;
    protected $latitudeHelper;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Latitude\Checkout\Model\Util\Helper $latitudeHelper
    ) {
        $this->storeManager = $storeManager;
        $this->latitudeHelper = $latitudeHelper;
    }

    public function getLogoURL()
    {
        return $this->_isNZ() ?
        "https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg" :
        "https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg";
    }

    public function getTermsURL()
    {
        $termsUrl = $this->latitudeHelper->getTermsUrl();
        return !empty($termsUrl) ? $termsUrl : "https://checkout.latitudefinancial.com";
    }

    public function getContent()
    {
        return $this->_isNZ() ? $this->_getNZContent() : $this->_getAUContent();
    }

    private function _getAUContent()
    {
        $resp['title'] = "Enjoy Now. Pay Later.";

        $resp['image1'] = "https://assets.latitudefinancial.com/merchant-services/latitude/icon/interest-free-badge.svg";
        $resp['image2'] = "https://assets.latitudefinancial.com/merchant-services/latitude/icon/shopping.svg";

        $resp['heading'] = "Flexible Latitude Interest Free plans to suit your needs";
        $resp['description1'] = "We’re here to help you get what you need. With plans from 6-24 months,
        Latitude Gem Visa has a range of Interest Free offers that work for you.";
        $resp['description2'] = "You will be redirected to Latitude checkout to complete your order";
        $resp['applyText'] = "Not a Latitude customer";
        $resp['applyURL'] = "https://checkout.latitudefinancial.com/about/au?merchantId=". $this->latitudeHelper->getMerchantId();

        return $resp;
    }

    private function _getNZContent()
    {
        $resp['title'] = "Enjoy Now. Pay Later.";

        $resp['image1'] = "https://assets.latitudefinancial.com/merchant-services/latitude/icon/shopping.svg";
        $resp['image2'] = "";

        $resp['heading'] = "Flexible Gem Interest Free plans to suit your needs";
        $resp['description1'] = "We’re here to help you get what you need. With plans from 6-24 months,
        Latitude Gem Visa has a range of Interest Free offers that work for you.";
        $resp['description2'] = "You will be redirected to Gem Visa checkout to complete your order";
        $resp['applyText'] = "Not a Gem Visa customer";
        $resp['applyURL'] = "https://checkout.latitudefinancial.com/about/nz?merchantId=". $this->latitudeHelper->getMerchantId();

        return $resp;
    }

    private function _isNZ()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode() == "NZD";
    }
}
