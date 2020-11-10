<?php

namespace Latitude\Checkout\Block;

use Magento\Framework\View\Element\Template;

class StockLeft extends Template
{
    protected $latitudeHelper;

    public function __construct(
        Template\Context $context,
        array $data = [],
        \Latitude\Checkout\Model\Util\Helper $latitudeHelper
    ) {
        $this->latitudeHelper = $latitudeHelper;
        parent::__construct($context, $data);
    }

    public function getContent()
    {
        if ($this->latitudeHelper->isNZMerchant()) {
            return $this->_NZContent();
        }

        return $this->_AUContent();
    }

    public function _AUContent()
    {
        return [
            "title" => "Or flexible Latitude Interest Free plans to suit your needs",
            "logoURL" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/latitude-interest-free.svg",
            "applyURL" => "https://checkout.latitudefinancial.com/about/au?merchantId=". $this->latitudeHelper->getMerchantId()
        ];
    }

    public function _NZContent()
    {
        return [
            "title" => "Or flexible Gem Interest Free plans to suit your needs",
            "logoURL" => "https://assets.latitudefinancial.com/merchant-services/latitude/icon/gem-interest-free.svg",
            "applyURL" => "https://checkout.latitudefinancial.com/about/nz?merchantId=". $this->latitudeHelper->getMerchantId()
        ];
    }
}
