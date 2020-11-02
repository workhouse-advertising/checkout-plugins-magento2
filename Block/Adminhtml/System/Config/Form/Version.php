<?php

namespace Latitude\Checkout\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Latitude\Checkout\Model\Util\Constants as LatitudeConstants;

class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Version constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $output = '<div style="background-color:#eee; margin:20px 0;padding:10px 15px; border:1px solid #ddd;">';
        $output .= __('Module version') . ': ' . LatitudeConstants::PLUGIN_VERSION;
        $output .= "</div>";
        return '<div id="row_' . $element->getHtmlId() . '">' . $output . '</div>';
    }
}