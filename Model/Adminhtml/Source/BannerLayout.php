<?php
namespace Latitude\Checkout\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class BannerLayout implements ArrayInterface
{
    const DEFAULT = "fullwidth";

    public function toOptionArray()
    {
        return [
            [
                'value' => "fullwidth",
                'label' => __('Full width'),
            ],
            [
                'value' => "inline",
                'label' => __('Inline'),
            ],
            [
                'value' => "disabled",
                'label' => __('Disabled'),
            ],
        ];
    }
}
