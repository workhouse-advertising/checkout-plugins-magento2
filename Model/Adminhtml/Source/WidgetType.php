<?php
namespace Latitude\Checkout\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class WidgetType implements ArrayInterface
{
    const DEFAULT = "full_width";

    public function toOptionArray()
    {
        return [
            [
                'value' => "full_width",
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
