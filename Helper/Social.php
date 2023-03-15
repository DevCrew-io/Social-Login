<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 25/01/2023
 * Time: 6:51 PM
 */
declare(strict_types=1);

namespace Devcrew\SocialLogin\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Header;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Data helper for system configurations
 *
 * Class Social
 */
class Social extends AbstractHelper
{
    /**#@+
     * Constants for template path
     */
    public const CLOSE_POPUP_TEMPLATE_PATH = 'Devcrew_SocialLogin::social/close-popup.phtml';
    /**#@-*/

    /**#@+
     * Constants for template path
     */
    public const XML_PATH_MODULE_ENABLE            = 'devcrew_social_login/general/enabled';
    public const XML_PATH_SHOW_BUTTONS_ON_PAGES    = 'devcrew_social_login/general/show_button_on';
    /**#@-*/

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @param Context $context
     * @param Header $httpHeader
     * @param PageFactory $resultPageFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Header $httpHeader,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->httpHeader = $httpHeader;
        $this->storeManager = $storeManager;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Is feature enabled
     *
     * @param string|null $storeId
     * @return bool
     */
    public function isFeatureEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self:: XML_PATH_MODULE_ENABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check is social type enabled
     *
     * @param string $socialType
     * @param string|null $storeId
     * @return bool|int
     */
    public function isSocialTypeEnabled($socialType = '', $storeId = null)
    {
        $isEnabled = 0;
        if (!$this->isFeatureEnabled()) {
            return $isEnabled;
        }
        if ($socialType) {
            $isEnabled = $this->scopeConfig->isSetFlag(
                'devcrew_social_login/' . $socialType . '/is_enabled',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return $isEnabled;
    }

    /**
     * Show button on customer login or create page
     *
     * @param string|null $storeId
     * @return mixed|string
     */
    public function showButtonOn($storeId = null)
    {
        if (!$this->isFeatureEnabled()) {
            return '';
        }
        return $this->scopeConfig->getValue(
            self::XML_PATH_SHOW_BUTTONS_ON_PAGES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get auth url
     *
     * @param string $socialType
     * @return string
     */
    public function getAuthUrl($socialType)
    {
        $authUrl = '';
        if ($socialType) {
            $scope = $this->getScope();
            $authUrl = $this->_getUrl(
                'devcrewsociallogin/' . $socialType . '/callback',
                [
                    '_nosid' => true,
                    '_secure' => true,
                    '_scope' => $scope
                ]
            );
        }
        return $authUrl;
    }

    /**
     * Get social app connect url
     *
     * @param string $socialType
     * @return string
     */
    public function getConnectUrl($socialType)
    {
        $connectUrl = '';
        if ($socialType) {
            $scope = $this->getScope();
            $connectUrl = $this->_getUrl(
                'devcrewsociallogin/' . $socialType . '/connect',
                [
                    '_nosid' => true,
                    '_secure' => true,
                    '_scope' => $scope
                ]
            );
        }
        return $connectUrl;
    }

    /**
     * Get scope
     *
     * @return int
     */
    protected function getScope()
    {
        $scope = '';
        try {
            $scope = $this->_request->getParam(ScopeInterface::SCOPE_STORE) ?:
                $this->storeManager->getStore()->getId();
            if ($website = $this->_request->getParam(ScopeInterface::SCOPE_WEBSITE)) {
                $scope = $this->storeManager->getWebsite($website)->getDefaultStore()->getId();
            }
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getMessage());
        }
        return $scope;
    }

    /**
     * Get social types
     *
     * @return array
     */
    public function getSocialTypes(): array
    {
        return [
            'facebook' => 'Facebook',
            'google' => 'Google'
        ];
    }

    /**
     * Get social type app id
     *
     * @param string $socialType
     * @param string|null $storeId
     * @return string
     */
    public function getAppId(string $socialType = '', string $storeId = null)
    {
        $appId = '';
        if (!$this->isFeatureEnabled()) {
            return $appId;
        }
        if ($socialType) {
            $appId = $this->scopeConfig->getValue(
                'devcrew_social_login/' . $socialType . '/app_id',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return $appId;
    }

    /**
     * Get social type app secret
     *
     * @param string $socialType
     * @param string|null $storeId
     * @return string
     */
    public function getAppSecret(string $socialType = '', string $storeId = null)
    {
        $appSecret = '';
        if (!$this->isFeatureEnabled()) {
            return $appSecret;
        }
        if ($socialType) {
            $appSecret = $this->scopeConfig->getValue(
                'devcrew_social_login/' . $socialType . '/app_secret',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return $appSecret;
    }

    // phpcs:disable
    /**
     * Check if user is from mobile device
     *
     * @return string|null
     */
    public function isMobile()
    {
        $browserStatus = null;
        $userAgent = $this->httpHeader->getHttpUserAgent();
        //Identifying if user is on mobile browser or not
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $userAgent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($userAgent, 0, 4))) {
            $browserStatus = 'Mobile';
        }
        return $browserStatus;
    }

    /**
     * Close login pop up window
     *
     * @param $objectAction
     */
    public function closePopUpWindow($objectAction)
    {
        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()
            ->createBlock(Template::class)
            ->setTemplate(self::CLOSE_POPUP_TEMPLATE_PATH)
            ->toHtml();
        $objectAction->getResponse()->setBody($block);
    }
    // phpcs:enable
}
