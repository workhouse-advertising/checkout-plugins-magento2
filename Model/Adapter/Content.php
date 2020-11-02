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
        return $this->_isNZ() ?
        "https://checkout.latitudefinancial.com/about/nz" :
        "https://checkout.latitudefinancial.com/about/au";
    }

    public function getContent()
    {
        return $this->_isNZ() ? $this->_getNZContent() : $this->_getAUContent();
    }

    private function _getAUContent()
    {
        $resp['title'] = "Enjoy Now. Pay Later.";

        // TODO: use assets.latitudefinancial.com
        $resp['image1'] = "https://cdn.zeplin.io/5f9610a994acbe213a1756f2/assets/5E59F306-6E43-473B-B8AB-E8B0E27A1230.png";
        // TODO: use assets.latitudefinancial.com
        $resp['image2'] = "https://cdn.zeplin.io/5f9610a994acbe213a1756f2/assets/5E59F306-6E43-473B-B8AB-E8B0E27A1230.png";

        $resp['heading'] = "Flexible Latitude Interest Free plans to suit your needs";
        $resp['description1'] = "We’re here to help you get what you need. With plans from 6-24 months,
        Latitude Gem Visa has a range of Interest Free offers that work for you.";
        $resp['description2'] = "You will be redirected to Latitude checkout to complete your order";
        $resp['applyText'] = "Not a Latitude customer";
        $resp['applyURL'] = "https://checkout.latitudefinancial.com/about/au";

        return $resp;
    }

    private function _getNZContent()
    {
        $resp['title'] = "Enjoy Now. Pay Later.";

        // TODO: use assets.latitudefinancial.com
        $resp['image1'] = "https://cdn.zeplin.io/5f9610a994acbe213a1756f2/assets/5E59F306-6E43-473B-B8AB-E8B0E27A1230.png";
        // TODO: use assets.latitudefinancial.com
        $resp['image2'] = "https://cdn.zeplin.io/5f9610a994acbe213a1756f2/assets/5E59F306-6E43-473B-B8AB-E8B0E27A1230.png";

        $resp['heading'] = "Flexible Gem Interest Free plans to suit your needs";
        $resp['description1'] = "We’re here to help you get what you need. With plans from 6-24 months,
        Latitude Gem Visa has a range of Interest Free offers that work for you.";
        $resp['description2'] = "You will be redirected to Gem Visa checkout to complete your order";
        $resp['applyText'] = "Not a Gem Visa customer";
        $resp['applyURL'] = "https://checkout.latitudefinancial.com/about/nz";

        return $resp;
    }

    private function _isNZ()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode() == "NZD";
    }
}
