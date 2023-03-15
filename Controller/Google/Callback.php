<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 22/02/2023
 * Time: 3:31 PM
 */

namespace Devcrew\SocialLogin\Controller\Google;

use Devcrew\SocialLogin\Controller\Login\ValidateLogin;
use Devcrew\SocialLogin\Helper\Social as Helper;
use Devcrew\SocialLogin\Model\SocialLoginHandler;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface as ResultInterfaceAlias;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Callback
 *
 * Callback class for Google login
 */
class Callback extends Action
{
    /**#@+
     * Constants for URLs
     */
    public const SOCIAL_TYPE = 'google';
    private const GOOGLE_ACCESS_TOKEN_URL = 'https://www.googleapis.com/oauth2/v4/token';
    private const GOOGLE_USER_PROFILE_URL = 'https://www.googleapis.com/oauth2/v2/userinfo?fields=';
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
     * Google login callback execute
     *
     * @return ResponseInterface|ResultInterfaceAlias|void
     */
    public function execute()
    {
        $state = $this->getRequest()->getParam('state');
        $params = $this->getRequest()->getParams();
        $clientId = $this->helper->getAppId(self::SOCIAL_TYPE);
        $clientSecret = $this->helper->getAppSecret(self::SOCIAL_TYPE);
        $clientRedirectUrl = $this->helper->getAuthUrl(self::SOCIAL_TYPE);
        if ($this->socialLoginHandler->getCoreSession()->getGoogleLoginState() != $state) {
            $this->getResponse()->setBody(
                __('Warning! State mismatch. Authentication attempt may have been compromised.')
            );
            return;
        }
        $this->socialLoginHandler->getCoreSession()->unsGoogleLoginState();
        // Google passes a parameter 'code' in the Redirect Url
        if (isset($params['code'])) {
            try {
                $response = $this->getAccessToken($clientId, $clientRedirectUrl, $clientSecret, $params['code']);
                if (isset($response['errorMessage']) || !isset($response['access_token'])) {
                    $this->getResponse()->setBody($response['errorMessage']);
                    return;
                }
                $accessToken = $response['access_token'];
                $userInfo = $this->getUserProfileInfo($accessToken);
                $userData['name'] = $userInfo['name'] ?? '';
                $userData['email'] = $userInfo['email'] ?? '';
                $userData['social_id'] = $userInfo['id'] ?? '';
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
    }

    /**
     * Get access token for connection
     *
     * @param string $client_id
     * @param string $redirect_uri
     * @param string $client_secret
     * @param string $code
     * @return array|bool|float|int|mixed|string|null
     */
    private function getAccessToken($client_id, $redirect_uri, $client_secret, $code)
    {
        $response = [];
        try {
            $curlPost = 'client_id=' . $client_id . '&redirect_uri=' . $redirect_uri . '&client_secret=' .
                $client_secret . '&code=' . $code . '&grant_type=authorization_code';
            $this->curlClient->setOptions([
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POSTFIELDS => $curlPost
            ]);
            $this->curlClient->post(self::GOOGLE_ACCESS_TOKEN_URL, []);
            $status = $this->curlClient->getStatus();
            if ($status != 200) {
                $response['errorMessage'] = __('Unspecified OAuth error occurred. Please check client id and secret.');
                return $response;
            }
            $response = $this->json->unserialize($this->curlClient->getBody());
        } catch (\Exception $exception) {
            $response['errorMessage'] = $exception->getMessage();
        }
        return $response;
    }

    /**
     * Get user profile info
     *
     * @param string $accessToken
     * @return array|bool|float|int|mixed|string|null
     */
    private function getUserProfileInfo($accessToken)
    {
        $response = [];
        try {
            $this->curlClient->setOptions([
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                ]
            ]);
            $fields = 'name,email,gender,id,picture,verified_email';
            $this->curlClient->get(self::GOOGLE_USER_PROFILE_URL. $fields);
            $status = $this->curlClient->getStatus();
            if ($status != 200) {
                return $response;
            }
            $response = $this->json->unserialize($this->curlClient->getBody());
        } catch (\Exception $exception) {
            $response['errorMessage'] = $exception->getMessage();
        }
        return $response;
    }
}
