<?php
namespace Latitude\Checkout\Model\Adapter;

class Content
{
    protected $latitudeHelper;

    public function __construct(
        \Latitude\Checkout\Model\Util\Helper $latitudeHelper
    ) {
        $this->latitudeHelper = $latitudeHelper;
    }

    public function getLogoURL()
    {
        if ($this->latitudeHelper->isNZMerchant()) {
            return "https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg";
        }

        return "https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg";
    }

    public function getContent()
    {
        if ($this->latitudeHelper->isNZMerchant()) {
            return $this->_NZContent();
        }

        return $this->_AUContent();
    }

    private function _AUContent()
    {
        $termsUrl = $this->latitudeHelper->getTermsUrl();
        $applyURL = "https://checkout.latitudefinancial.com/about/au?merchantId=". $this->latitudeHelper->getMerchantId();

        return [
            "label" => "Latitude Interest Free",
            "title" => "Enjoy Now. Pay Later.",
            "image1" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/interest-free-badge.svg",
            "image2" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/shopping.svg",
            "heading" => "Flexible Latitude Interest Free plans to suit your needs",
            "description1" => "We’re here to help you get what you need. With plans from 6-24 months, Latitude Gem Visa has a range of Interest Free offers that work for you.",
            "description2" => "You will be redirected to Latitude checkout to complete your order",
            "applyText" => "Not a Latitude customer",
            "learnMoreURL" => $applyURL,
            "applyURL" => $applyURL,
            "termsURL" => empty($termsUrl) ? $applyURL : $termsUrl
        ];
    }

    private function _NZContent()
    {
        $termsUrl = $this->latitudeHelper->getTermsUrl();
        $applyURL = "https://checkout.latitudefinancial.com/about/nz?merchantId=". $this->latitudeHelper->getMerchantId();

        return [
            "label" => "Gem Interest Free",
            "title" => "Enjoy Now. Pay Later.",
            "image1" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/shopping.svg",
            "image2" => "",
            "heading" => "Flexible Gem Interest Free plans to suit your needs",
            "description1" => "We’re here to help you get what you need. With plans from 6-24 months, Gem Visa has a range of Interest Free offers that work for you.",
            "description2" => "You will be redirected to Gem Visa checkout to complete your order",
            "applyText" => "Not a Gem Visa customer",
            "learnMoreURL" => $applyURL,
            "applyURL" => $applyURL,
            "termsURL" => empty($termsUrl) ? $applyURL : $termsUrl
        ];
    }
}
