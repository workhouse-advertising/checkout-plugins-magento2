<?php

namespace LMerchant\Checkout\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Lmerchant\Checkout\Model\Util\Constants as LmerchantConstants;

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
        $output = '<div style="background-color:#eee;padding:1em;border:1px solid #ddd;">';
        $output .= __('Module version') . ': ' . LmerchantConstants::PLUGIN_VERSION;
        $output .= "</div>";
        return '<div id="row_' . $element->getHtmlId() . '">' . $output . '</div>';
    }
}