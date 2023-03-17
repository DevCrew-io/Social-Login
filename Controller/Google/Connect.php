<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 22/02/2023
 * Time: 3:43 PM
 */

namespace Devcrew\SocialLogin\Controller\Google;

use Devcrew\SocialLogin\Helper\Social as Helper;
use Devcrew\SocialLogin\Model\SocialLoginHandler;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Controller class connect for Google login
 *
 * Class Connect
 */
class Connect extends Action
{
    /**#@+
     * Constants for URLs
     */
    private const OAUTH2_AUTH_URI = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const OAUTH2_SCOPE_1  = 'https://www.googleapis.com/auth/userinfo.profile';
    private const OAUTH2_SCOPE_2  = 'https://www.googleapis.com/auth/userinfo.email';
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
     * Execute function to connect with Google
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $state = hash('sha256', uniqid(rand(), true));
        $this->socialLoginHandler->getCoreSession()->setGoogleLoginState($state);
        $googleClientId = $this->helper->getAppId(Callback::SOCIAL_TYPE);
        $googleCallback = $this->helper->getAuthUrl(Callback::SOCIAL_TYPE);
        $url = self::OAUTH2_AUTH_URI . '?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $googleClientId,
                'redirect_uri' => $googleCallback,
                'state' => $state,
                'scope' => self::OAUTH2_SCOPE_1 . ' ' . self::OAUTH2_SCOPE_2,
                'access_type' => 'online'
            ]);
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath($url);
    }
}
