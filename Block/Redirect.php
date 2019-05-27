<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_RequiredLogin
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\BetterMaintenance\Block;

use Magento\Cms\Model\Page as CmsPage;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Element\Template;
use Mageplaza\BetterMaintenance\Helper\Data as HelperData;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

/**
 * Class Deal
 * @package Mageplaza\RequiredLogin\Block
 */
class Redirect extends Template
{
    protected $_template = 'Mageplaza_BetterMaintenance::redirect.phtml';

    /**
     * @var HelperData
     */
    protected $_helperData;

    /**
     * @var CmsPage
     */
    protected $_cmsPage;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var Http
     */
    protected $_response;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var HttpContext
     */
    protected $_context;

    protected $_remoteAddress;

    /**
     * Action constructor.
     *
     * @param Template\Context $context
     * @param HelperData $helperData
     * @param CmsPage $cmsPage
     * @param Session $session
     * @param Http $response
     * @param ManagerInterface $messageManager
     * @param HttpContext $httpContext
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        HelperData $helperData,
        CmsPage $cmsPage,
        Session $session,
        Http $response,
        ManagerInterface $messageManager,
        HttpContext $httpContext,
        RemoteAddress $remoteAddress,
        array $data = []
    ) {
        $this->_helperData     = $helperData;
        $this->_cmsPage        = $cmsPage;
        $this->_session        = $session;
        $this->_response       = $response;
        $this->_messageManager = $messageManager;
        $this->_context        = $httpContext;
        $this->_remoteAddress = $remoteAddress;

        parent::__construct($context, $data);
    }

    /**
     * Get full action name
     *
     * @return mixed
     */
    public function getFullActionName()
    {
        return $this->_request->getFullActionName();
    }

    public function getWhiteListPage()
    {
        $links = preg_split("/(\r\n|\n|\r)/", $this->_helperData->getConfigGeneral('whitelist_page'));

        return $links;
    }

    public function getWhiteListIp()
    {
        return explode(',', $this->_helperData->getConfigGeneral('whitelist_ip'));
    }
    /**
     * @return bool|Http
     */
    public function redirectToUrl()
    {
        $redirectTo = $this->_helperData->getConfigGeneral('redirect_to');
        $currentUrl = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        $currentIp = $this->_remoteAddress->getRemoteAddress();

        foreach ($this->getWhiteListIp() as $value) {
            if ($currentIp === $value) {
                return false;
            }
        }

        foreach ($this->getWhiteListPage() as $value) {
            if ($currentUrl === $value) {
                return false;
            }
        }
        switch ($redirectTo) {
            case 'maintenance_page':
                $route = $this->_helperData->getMaintenanceRoute();
                $route = isset($route) ? $route : HelperData::MAINTENANCE_ROUTE;
                break;
            case 'coming_soon_page':
                $route = $this->_helperData->getComingSoonRoute();
                $route = isset($route) ? $route : HelperData::COMINGSOON_ROUTE;
                break;
            case 'home_page':
                $route = $this->getBaseUrl();
                if ($this->getFullActionName() === 'cms_index_index') {
                    return false;
                }
                break;
            default:
                $route = 'noroute';
                if ($this->getFullActionName() === 'cms_noroute_index') {
                    return false;
                }
                break;
        }

        $url = $this->getUrl($route);

        return $this->_response->setRedirect($url)->setHttpResponseCode(503);
    }
}
