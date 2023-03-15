<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 25/01/2023
 * Time: 6:11 PM
 */
namespace Devcrew\SocialLogin\Block\System;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Devcrew\SocialLogin\Helper\Social as SocialHelper;

/**
 * Block class redirect url
 *
 * Class RedirectUrl
 */
class RedirectUrl extends Field
{
    /**
     * @var SocialHelper
     */
    protected $socialHelper;

    /**
     * @param Context $context
     * @param SocialHelper $socialHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        SocialHelper $socialHelper,
        array $data = []
    ) {
        $this->socialHelper = $socialHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $elementId = explode('_', $element->getHtmlId());
        if (isset($elementId[1])) {
            $redirectUrl = $this->socialHelper->getAuthUrl($elementId[3]);
            $html = '<input style="opacity:1;" readonly id="' . $element->getHtmlId()
                . '" class="input-text admin__control-text" value="' . $redirectUrl
                . '" onclick="this.select()" type="text">';
        }
        return $html ?? parent::_getElementHtml($element);
    }
}
