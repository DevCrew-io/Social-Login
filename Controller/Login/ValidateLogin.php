<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 22/02/2023
 * Time: 3:47 PM
 */

namespace Devcrew\SocialLogin\Controller\Login;

use Devcrew\SocialLogin\Model\Social;
use Devcrew\SocialLogin\Model\SocialLoginHandler;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ValidateLogin
 *
 * Validate customer login
 */
class ValidateLogin extends Action
{
    /**#@+
     * Constants for URLs
     */
    public const VALIDATE_LOGIN_URL = 'devcrewsociallogin/login/validatelogin';
    /**#@-*/

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var SocialLoginHandler
     */
    private $socialLoginHandler;

    /**
     * @var Social
     */
    private $social;

    /**
     * @var PhpCookieManager
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Social $social
     * @param Context $context
     * @param CustomerSession $session
     * @param PhpCookieManager $cookieManager
     * @param SocialLoginHandler $socialLoginHandler
     * @param StoreManagerInterface $storeManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        Social $social,
        Context $context,
        CustomerSession $session,
        PhpCookieManager $cookieManager,
        SocialLoginHandler $socialLoginHandler,
        StoreManagerInterface $storeManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->social = $social;
        $this->storeManager = $storeManager;
        $this->cookieManager = $cookieManager;
        $this->customerSession = $session;
        $this->socialLoginHandler = $socialLoginHandler;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        parent::__construct($context);
    }

    /**
     * Validate login
     * Check if customer exists then login otherwise redirect to login page. If customer is registering for first
     * time in social login then redirect to social login form page.
     *
     * @return $this|ValidateLogin|ResponseInterface|Redirect|(Redirect&ResultInterface)|ResultInterface
     */
    public function execute()
    {
        $this->socialLoginHandler->getCoreSession()->start();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $socialData = $this->socialLoginHandler->getCoreSession()->getData('social_user_data');
        $type = $socialData['social_type'] ?? '';
        $email = $socialData['email'] ?? '';
        $socialId = $socialData['social_id'] ?? '';

        if (!$type || !$socialId) {
            return $resultRedirect->setPath('');
        }
        if (!$email) {
            return $this->errorRedirect();
        }

        try {
            $customer = $this->social->getCustomerBySocial($socialId, $type);

            if (!$customer->getId()) {
                $customer = $this->createCustomerProcess($socialData, $type);
            }

            /** @var Customer $customer */
            $this->refresh($customer);
        } catch (\Exception $exception) {
            return $this->errorRedirect($exception->getMessage(), false);
        }
        return $resultRedirect->setPath('');
    }

    /**
     * Email redirect if profile has no email
     *
     * @param string $message
     * @param bool $needTranslate
     * @return $this
     */
    public function errorRedirect(string $message = '', bool $needTranslate = true): ValidateLogin
    {
        $message = $message ?: 'Email is Null, Please enter email in your profile';
        $message = $needTranslate ? __($message) : $message;
        $this->messageManager->addErrorMessage($message);
        $this->_redirect('customer/account/login');
        return $this;
    }

    /**
     * Create customer process
     *
     * @param array $socialData
     * @param string $type
     * @return $this|ValidateLogin|false
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function createCustomerProcess($socialData, $type)
    {
        if (!isset($socialData['email'])) {
            return $this->errorRedirect();
        }

        if (isset($socialData['name'])) {
            $name = explode(' ', $socialData['name']);
            $firstName = array_shift($name);
            $lastName = array_shift($name);
        } else {
            $firstName = $socialData['first_name'] ?? 'New';
            $lastName = $socialData['last_name'] ?? 'User';
        }

        $user = [
            'type' => $type,
            'email' => $socialData['email'] ?: $type . '@' . strtolower($type) . '.com',
            'password' => $socialData['password'] ?? $this->getRequest()->getParam('password'),
            'lastname' => $lastName,
            'firstname' => $firstName,
            'social_id' => $socialData['social_id']
        ];

        return $this->createCustomer($user, $type);
    }

    /**
     * Create customer
     *
     * @param array $user
     * @param string $type
     * @return false|Customer
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function createCustomer($user, $type)
    {
        $customer = $this->social->getCustomerByEmail($user['email'], $this->getStore()->getWebsiteId());
        if ($customer->getId()) {
            $this->social->setAuthorCustomer($user['social_id'], $customer->getId(), $type);
        } else {
            try {
                $customer = $this->social->createCustomerSocial($user, $this->getStore());
            } catch (\Exception $exception) {
                $this->errorRedirect($exception->getMessage(), false);
                return false;
            }
        }
        return $customer;
    }

    /**
     * Get store
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Refresh customer data in cookies
     *
     * @param Customer $customer
     * @return void
     */
    public function refresh(Customer $customer)
    {
        try {
            if ($customer->getId()) {
                $this->customerSession->setCustomerAsLoggedIn($customer);
                $this->customerSession->regenerateId();

                if ($this->cookieManager->getCookie('mage-cache-sessid')) {
                    $metadata = $this->cookieMetadataFactory->createCookieMetadata();
                    $metadata->setPath('/');
                    $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
                }
            }
        } catch (\Exception $exception) {
            $this->errorRedirect($exception->getMessage(), false);
        }
    }
}
