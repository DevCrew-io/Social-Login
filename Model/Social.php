<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 27/02/2023
 * Time: 12:22 PM
 */

namespace Devcrew\SocialLogin\Model;

use Devcrew\SocialLogin\Model\ResourceModel\Social\CollectionFactory;
use Exception;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Math\Random;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Devcrew Social Login Model class
 *
 * Class Social
 */
class Social extends AbstractModel
{
    /**
     * @var Random
     */
    private $random;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerDataFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var EmailNotificationInterface
     */
    private $emailNotification;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param Random $random
     * @param Context $context
     * @param Registry $registry
     * @param CustomerFactory $customerFactory
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param EmailNotificationInterface $emailNotification
     * @param AccountManagementInterface $accountManagement
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Random                      $random,
        Context                     $context,
        Registry                    $registry,
        CustomerFactory             $customerFactory,
        CollectionFactory           $collectionFactory,
        StoreManagerInterface       $storeManager,
        CustomerInterfaceFactory    $customerDataFactory,
        CustomerRepositoryInterface $customerRepository,
        EmailNotificationInterface  $emailNotification,
        AccountManagementInterface  $accountManagement,
        AbstractResource            $resource = null,
        AbstractDb                  $resourceCollection = null,
        array                       $data = []
    ) {
        $this->random = $random;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->collectionFactory = $collectionFactory;
        $this->emailNotification = $emailNotification;
        $this->customerRepository = $customerRepository;
        $this->customerDataFactory = $customerDataFactory;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Social::class);
    }

    /**
     * Set author customer
     *
     * @param string $identifier
     * @param int $customerId
     * @param string $type
     * @return $this
     * @throws Exception
     */
    public function setAuthorCustomer($identifier, $customerId, $type)
    {
        $this->setData([
            'social_id' => $identifier,
            'customer_id' => $customerId,
            'type' => $type,
            'website_id' => $this->storeManager->getWebsite()->getId()
        ])->setId(null)->save();

        return $this;
    }

    /**
     * Get customer by social
     *
     * @param string $identify
     * @param string $type
     * @return Customer
     * @throws LocalizedException
     */
    public function getCustomerBySocial($identify, $type)
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        $customer = $this->customerFactory->create();

        $socialCustomer = $this->collectionFactory->create()
            ->addFieldToFilter('social_id', $identify)
            ->addFieldToFilter('type', $type)
            ->addFieldToFilter('website_id', $websiteId)
            ->getFirstItem();

        if ($socialCustomer && $socialCustomer->getId()) {
            $customer->load($socialCustomer->getCustomerId());
        }

        return $customer;
    }

    /**
     * Get customer by email
     *
     * @param string $email
     * @param int|null $websiteId
     * @return Customer
     * @throws LocalizedException
     */
    public function getCustomerByEmail($email, $websiteId = null)
    {
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId ?: $this->storeManager->getWebsite()->getId());
        $customer->loadByEmail($email);

        return $customer;
    }

    /**
     * Create customer social
     *
     * @param array $data
     * @param StoreInterface $store
     * @return Customer
     * @throws InputMismatchException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createCustomerSocial($data, $store)
    {
        $customer = $this->customerDataFactory->create();
        $customer->setFirstname($data['firstname'])
            ->setLastname($data['lastname'])
            ->setEmail($data['email'])
            ->setStoreId($store->getId())
            ->setWebsiteId($store->getWebsiteId())
            ->setCreatedIn($store->getName());

        $newAccount = false;
        $noPassword = false;
        try {
            if ($data['password'] !== null) {
                $customer = $this->customerRepository->save($customer, $data['password']);
                $newAccount = true;
            } else {
                // If customer exists existing hash will be used by Repository
                $customer = $this->customerRepository->save($customer);
                $noPassword = true;

                $newPasswordToken = $this->random->getUniqueHash();
                $this->accountManagement->changeResetPasswordLinkToken($customer, $newPasswordToken);
            }

            $this->setAuthorCustomer($data['social_id'], $customer->getId(), $data['type']);
        } catch (AlreadyExistsException $e) {
            throw new InputMismatchException(
                __('A customer with the same email already exists in an associated website.')
            );
        } catch (Exception $e) {
            if ($customer->getId()) {
                $this->_registry->register('isSecureArea', true, true);
                $this->customerRepository->deleteById($customer->getId());
            }
            throw $e;
        }

        // Send email notification to customer
        try {
            if ($newAccount) {
                $this->emailNotification->newAccount(
                    $customer,
                    EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED,
                    '',
                    $store->getId()
                );
            } elseif ($noPassword) {
                $this->emailNotification->newAccount(
                    $customer,
                    EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD,
                    '',
                    $store->getId()
                );
            }
        } catch (Exception $exception) {
            $this->_logger->error($exception->getMessage());
        }

        /**
         * @var Customer $customer
         */
        $customer = $this->customerFactory->create()->load($customer->getId());
        return $customer;
    }
}
