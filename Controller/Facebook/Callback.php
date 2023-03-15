<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 22/02/2023
 * Time: 2:11 PM
 */

namespace Devcrew\SocialLogin\Controller\Facebook;

use Devcrew\SocialLogin\Controller\Login\ValidateLogin;
use Devcrew\SocialLogin\Helper\Social as Helper;
use Devcrew\SocialLogin\Model\SocialLoginHandler;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Controller class for login
 *
 * Class Callback
 */
class Callback extends Action
{
    /**#@+
     * Constants for URLs
     */
    public const SOCIAL_TYPE = 'facebook';
    private const FB_ACCESS_TOKEN_URL = 'https://graph.facebook.com/v16.0/oauth/access_token';
    private const FB_VERIFY_ACCESS_TOKEN_URL = 'https://graph.facebook.com/me';
    private const FB_USER_PROFILE_URL = 'https://graph.facebook.com/v16.0/me?access_token=';
    /**#@-*/

    /**
     * @var Curl
     */
    private $curlClient;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var SocialLoginHandler
     */
    private $socialLoginHandler;

    /**
     * @param Curl $curl
     * @param Json $json
     * @param Helper $helper
     * @param Context $context
     * @param SocialLoginHandler $socialLoginHandler
     */
    public function __construct(
        Curl $curl,
        Json $json,
        Helper $helper,
        Context $context,
        SocialLoginHandler $socialLoginHandler
    ) {
        $this->json = $json;
        $this->helper = $helper;
        $this->curlClient = $curl;
        $this->socialLoginHandler = $socialLoginHandler;
        parent::__construct($context);
    }

    /**
     * Facebook callback function
     *
     * @return ResponseInterface|Controller\ResultInterface|void
     */
    public function execute()
    {
        $code = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');
        $clientId = $this->helper->getAppId(self::SOCIAL_TYPE);
        $clientSecret = $this->helper->getAppSecret(self::SOCIAL_TYPE);
        $redirectUri = $this->helper->getAuthUrl(self::SOCIAL_TYPE);
        if ($this->socialLoginHandler->getCoreSession()->getFacebookLoginState() != $state) {
            $this->getResponse()->setBody(
                __('Warning! State mismatch. Authentication attempt may have been compromised.')
            );
            return;
        }
        $this->socialLoginHandler->getCoreSession()->unsFacebookLoginState();
        $response = $this->getAccessToken($clientId, $clientSecret, $redirectUri, $code);
        try {
            if (isset($response['errorMessage']) || !isset($response['access_token'])) {
                $this->getResponse()->setBody($response['errorMessage']);
                return;
            }
            $accessToken = $response['access_token'];
            $response = $this->verifyAccessToken($accessToken);
            if ($response['success'] != 1) {
                $this->getResponse()->setBody(__('Unspecified OAuth error occurred.'));
                return;
            }
            $response = $this->getUserProfileInfo($accessToken);
            $userData['email'] = $response['email'] ?? '';
            $userData['social_id'] = $response['id'] ?? '';
            $userData['last_name'] = $response['last_name'] ?? '';
            $userData['first_name'] = $response['first_name'] ?? '';
            $userData['social_type'] = self::SOCIAL_TYPE;
        } catch (\Exception $exception) {
            $this->getResponse()->setBody($exception->getMessage());
            return;
        }

        $this->socialLoginHandler->redirectCustomer($userData, self::SOCIAL_TYPE);
        if ($this->helper->isMobile()) {
            $url = $this->_url->getUrl(ValidateLogin::VALIDATE_LOGIN_URL);
            $this->_redirect($url);
        } else {
            $this->helper->closePopUpWindow($this);
        }
    }

    /**
     * Get facebook user access token
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param string $code
     * @return array|bool|float|int|mixed|string|null
     */
    private function getAccessToken($clientId, $clientSecret, $redirectUri, $code)
    {
        $response = [];
        try {
            $request = 'client_id=' . $clientId . '&client_secret=' . $clientSecret . '&redirect_uri=' .
                $redirectUri . '&code=' . $code;
            $this->curlClient->setOptions([
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POSTFIELDS => $request,
                CURLOPT_HEADER => false,
                CURLOPT_VERBOSE => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ]
            ]);
            $this->curlClient->get(self::FB_ACCESS_TOKEN_URL);
            $status = $this->curlClient->getStatus();
            if (($status == 400 || $status == 401)) {
                $response['errorMessage'] = __('Unspecified OAuth error occurred. Please check app id and secret.');
                return $response;
            }
            $response = $this->json->unserialize($this->curlClient->getBody());
        } catch (\Exception $exception) {
            $response['errorMessage'] = $exception->getMessage();
        }
        return $response;
    }

    /**
     * Verify user access token
     *
     * @param string $accessToken
     * @return array|bool|float|int|mixed|string|null
     */
    private function verifyAccessToken($accessToken)
    {
        $response = [];
        try {
            $request = 'access_token=' . $accessToken;
            $this->curlClient->setOptions([
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POSTFIELDS => $request,
                CURLOPT_HEADER => false,
                CURLOPT_VERBOSE => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ]
            ]);
            $this->curlClient->get(self::FB_VERIFY_ACCESS_TOKEN_URL);
            $status = $this->curlClient->getStatus();
            if (($status == 400 || $status == 401)) {
                $response['success'] = false;
            } else {
                $response = $this->json->unserialize($this->curlClient->getBody());
            }
        } catch (\Exception $exception) {
            $response['success'] = false;
            $this->getResponse()->setBody($exception->getMessage());
        }
        return $response;
    }

    /**
     * Get facebook user profile
     *
     * @param string $accessToken
     * @return array|bool|float|int|mixed|string|null
     */
    private function getUserProfileInfo($accessToken)
    {
        $response = [];
        try {
            $apiUrl = self::FB_USER_PROFILE_URL . $accessToken . "&fields=id,first_name,last_name,email";
            $this->curlClient->get($apiUrl);
            $status = $this->curlClient->getStatus();
            if (($status == 400 || $status == 401)) {
                return $response;
            }
            $response = $this->json->unserialize($this->curlClient->getBody());
        } catch (\Exception $exception) {
            $this->getResponse()->setBody($exception->getMessage());
        }
        return $response;
    }
}
