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
 * @category  Mageplaza
 * @package   Mageplaza_BetterMaintenance
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\BetterMaintenance\Block;

use Magento\Cms\Model\Page as CmsPage;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\View\Element\Template;
use Mageplaza\BetterMaintenance\Helper\Data as HelperData;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class Redirect
 *
 * @package Mageplaza\BetterMaintenance\Block
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
     * @var Http
     */
    protected $_response;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * Redirect constructor.
     *
     * @param Template\Context $context
     * @param HelperData $helperData
     * @param CmsPage $cmsPage
     * @param Http $response
     * @param DateTime $date
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        HelperData $helperData,
        CmsPage $cmsPage,
        Http $response,
        DateTime $date,
        array $data = []
    ) {
        $this->_helperData     = $helperData;
        $this->_cmsPage        = $cmsPage;
        $this->_response       = $response;
        $this->_date           = $date;

        parent::__construct($context, $data);
    }

    /**
     * @return array[]|false|string[]
     */
    public function getWhiteListPage()
    {
        $links = preg_split("/(\r\n|\n|\r)/", $this->_helperData->getConfigGeneral('whitelist_page'));

        return $links;
    }

    /**
     * @return array
     */
    public function getWhiteListIp()
    {
        return explode(',', $this->_helperData->getConfigGeneral('whitelist_ip'));
    }

    /**
     * @return bool|Http|HttpInterface
     */
    public function redirectToUrl()
    {
        $this->_response->setNoCacheHeaders();
        $redirectTo = $this->_helperData->getConfigGeneral('redirect_to');
        $currentUrl = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        $currentIp  = $this->_request->getClientIp();

        if (!$this->_helperData->isEnabled()) {
            return false;
        }

        foreach ($this->getWhiteListIp() as $value) {
            if ($this->_helperData->checkIp($currentIp, $value)) {
                return false;
            }
        }

        foreach ($this->getWhiteListPage() as $value) {
            if ($currentUrl === $value) {
                return false;
            }
        }

        if (strtotime($this->_localeDate->date()->format('m/d/Y H:i:s'))
            >= strtotime($this->_helperData->getConfigGeneral('end_time'))) {
            return false;
        }

        if ($redirectTo === 'maintenance_page' || $redirectTo === 'coming_soon_page') {
            return false;
        }

        $route = $redirectTo;

        if ($this->_cmsPage->getIdentifier() === $redirectTo) {
            return false;
        }

        $url = $this->getUrl($route);

        return $this->_response->setRedirect($url);
    }
}
