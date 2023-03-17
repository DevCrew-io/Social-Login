<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 23/02/2023
 * Time: 12:59 PM
 */

namespace Devcrew\SocialLogin\Model;

use Magento\Framework\Session\SessionManagerInterface;

/**
 * Handler class
 *
 * Class SocialLoginHandler
 */
class SocialLoginHandler
{
    /**
     * @var SessionManagerInterface
     */
    private $coreSession;

    /**
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        SessionManagerInterface $coreSession
    ) {
        $this->coreSession = $coreSession;
    }

    /**
     * Get session instance
     *
     * @return SessionManagerInterface
     */
    public function getCoreSession()
    {
        return $this->coreSession;
    }

    /**
     * Set data in session
     *
     * @param array|null $customerData
     */
    private function setDataInSession($customerData)
    {
        $this->getCoreSession()->start();
        $this->getCoreSession()->unsSocialUserData();
        $this->getCoreSession()->setData('social_user_data', $customerData);
    }

    /**
     * Get customer data for session
     *
     * @param array $dataUser
     * @param string $socialApp
     * @return array|null
     */
    private function getCustomerData($dataUser, $socialApp)
    {
        if (is_array($dataUser) && $dataUser) {
            if (array_key_exists('social_id', $dataUser)) {
                $customerData['social_id'] = $dataUser['social_id'];
            }
            if (array_key_exists('name', $dataUser)) {
                $customerData['name'] = $dataUser['name'];
            }
            if (array_key_exists('first_name', $dataUser)) {
                $customerData['first_name'] = $dataUser['first_name'];
            }
            if (array_key_exists('last_name', $dataUser)) {
                $customerData['last_name'] = $dataUser['last_name'];
            }
            if (array_key_exists('email', $dataUser)) {
                $customerData['email'] = $dataUser['email'];
            }
            $customerData['social_type'] = $socialApp;
            return $customerData;
        }
        return null;
    }

    /**
     * Redirect customer to other page.
     *
     * If customer exists then login and close popup else close pop and redirect to social login page.
     *
     * @param array $dataUser
     * @param string $socialType
     */
    public function redirectCustomer($dataUser, $socialType)
    {
        $customerData = $this->getCustomerData($dataUser, $socialType);
        if ($customerData) {
            $this->setDataInSession($customerData);
        }
    }
}
