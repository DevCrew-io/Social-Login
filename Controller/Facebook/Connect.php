<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 22/02/2023
 * Time: 3:01 PM
 */

namespace Devcrew\SocialLogin\Controller\Facebook;

use Devcrew\SocialLogin\Helper\Social as Helper;
use Devcrew\SocialLogin\Model\SocialLoginHandler;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller;

/**
 * Facebook connect class
 *
 * Class Connect
 */
class Connect extends Action
{
    /**#@+
     * Constants for URLs
     */
    private const OAUTH2_AUTH_URI = 'https://www.facebook.com/v16.0/dialog/oauth';
    /**#@-*/

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var SocialLoginHandler
     */
    private $socialLoginHandler;

    /**
     * @param Context $context
     * @param Helper $helper
     * @param SocialLoginHandler $socialLoginHandler
     */
    public function __construct(
        Context $context,
        Helper $helper,
        SocialLoginHandler $socialLoginHandler
    ) {
        $this->helper = $helper;
        $this->socialLoginHandler = $socialLoginHandler;
        parent::__construct($context);
    }

    /**
     * Execute function to connect with Facebook
     *
     * @return ResponseInterface|Controller\ResultInterface
     */
    public function execute()
    {
        $state = hash('sha256', uniqid(rand(), true));
        $this->socialLoginHandler->getCoreSession()->setFacebookLoginState($state);
        $fbAppId = $this->helper->getAppId(Callback::SOCIAL_TYPE);
        $fbRedirectUrl = $this->helper->getAuthUrl(Callback::SOCIAL_TYPE);
        $url = self::OAUTH2_AUTH_URI . '?' . http_build_query([
                'client_id' => $fbAppId,
                'redirect_uri' => $fbRedirectUrl,
                'state' => $state
            ]);
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath($url);
    }
}
