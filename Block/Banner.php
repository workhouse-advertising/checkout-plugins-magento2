<?php

namespace Latitude\Checkout\Block;

use Magento\Framework\View\Element\Template;
use \Latitude\Checkout\Model\Util\Constants as LatitudeConstants;

class Banner extends Template
{
    protected $registry;
    protected $latitudeHelper;

    public function __construct(
        Template\Context $context,
        array $data = [],
        \Magento\Framework\Registry $registry,
        \Latitude\Checkout\Model\Util\Helper $latitudeHelper
    ) {
        $this->registry = $registry;
        $this->latitudeHelper = $latitudeHelper;
        parent::__construct($context, $data);
    }

    public function getOptions()
    {
        $layout = $this->latitudeHelper->getProductBannerLayout();

        if (empty($layout)) {
            return Latitude\Checkout\Model\Adminhtml\Source\BannerLayout::DEFAULT;
        }

        $product = $this->getCurrentProduct();
        
        return json_encode([
            "merchantId" => $this->latitudeHelper->getMerchantId(),
            "currency" => $this->latitudeHelper->getBaseCurrency(),
            "page" => "product",
            "layout" => $layout,
            "product" => [
                "id" => $product->getId(),
                "name" =>  $product->getName(),
                "category" =>  $product->getCategory(),
                "price" => $this->getPrice(),
                "sku" =>  $product->getSku(),
            ]
        ]);
    }

    public function getScriptURL()
    {
        return $this->latitudeHelper->getScriptURL();
    }

    protected function getPrice()
    {
        $product = $this->getCurrentProduct();

        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return round((float)$product->getFinalPrice(), 2);
        }

        return round((float)$product->getPrice(), 2);
    }

    protected function getCurrentProduct()
    {
        return $this->registry->registry('product');
    }
}
