<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 22/02/2023
 * Time: 12:44 PM
 */

namespace Devcrew\SocialLogin\Block\SocialLogin;

use Devcrew\SocialLogin\Helper\Social;
use Devcrew\SocialLogin\Model\SocialLoginHandler;
use Devcrew\SocialLogin\Model\System\Config\Source\Position;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class for showing social logins
 *
 * Class Login
 */
class Login extends Template
{
    /**
     * @var Social
     */
    private $helper;

    /**
     * @var SocialLoginHandler
     */
    private $socialLoginHandler;

    /**
     * @param Social $helper
     * @param Context $context
     * @param SocialLoginHandler $socialLoginHandler
     */
    public function __construct(
        Social $helper,
        Context $context,
        SocialLoginHandler $socialLoginHandler
    ) {
        $this->helper = $helper;
        $this->socialLoginHandler = $socialLoginHandler;
        parent::__construct($context);
    }

    /**
     * Save state to registry
     *
     * @return string
     */
    public function getState()
    {
        $state = hash('sha256', uniqid(rand(), true));
        $this->socialLoginHandler->getCoreSession()->start();
        $this->socialLoginHandler->getCoreSession()->setLineLoginState($state);
        return $state;
    }

    /**
     * Unset Session before login
     */
    public function unSetSession()
    {
        $this->socialLoginHandler->getCoreSession()->start();
        $this->socialLoginHandler->getCoreSession()->unsSocialUserData();
    }

    /**
     * Get data helper
     *
     * @return Social
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Get enabled social types
     *
     * @return array
     */
    public function getEnabledSocialTypes()
    {
        $enabledTypes = [];
        foreach ($this->helper->getSocialTypes() as $key => $label) {
            if ($this->helper->isSocialTypeEnabled($key)) {
                $enabledTypes[$key] = [
                    'label' => $label,
                    'login_url' => $this->helper->getConnectUrl($key),
                ];
            }
        }
        return $enabledTypes;
    }

    /**
     * Can show button
     *
     * @param string|null $position
     * @return bool
     */
    public function canShowButton($position = null)
    {
        $displayOnPage = $this->helper->showButtonOn();
        $displayOnPage = explode(',', (string)$displayOnPage);
        if (!$position) {
            $position = $this->getRequest()->getFullActionName() === 'customer_account_login' ?
                Position::PAGE_LOGIN : Position::PAGE_CREATE;
        }
        return in_array($position, $displayOnPage);
    }
}
