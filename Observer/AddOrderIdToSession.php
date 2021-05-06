<?php
/**
 * Add Order Id To Session Observer
 *
 * PHP Version 7
 *
 * @category  OtpSimple
 * @package   Iconocoders
 * @author    Iconocoders <support@icoders.co>
 * @copyright 2017-2020 Iconocoders
 * @license   GNU GENERAL PUBLIC LICENSE  - https://github.com/IconoCoders/OTPSimplePay-Magento2/blob/master/LICENSE
 * @version   GIT: Release: 2.3.4
 * @link      http://iconocoders.com
 */
namespace Iconocoders\OtpSimple\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Module\Manager;
use Magento\Customer\Model\Session\Storage as CustomerSession;

/**
 * AddOrderIdToSession
 *
 */
class AddOrderIdToSession implements ObserverInterface
{
    /**
     * Customer Session
     *
     * @var CustomerSession
     */
    private $customerSession;
    
    /**
     * Module Manager
     *
     * @var CustomerSession
     */
    private $moduleManager;

    public function __construct(
        CustomerSession $customerSession,
        Manager $moduleManager
    ) {
        $this->customerSession = $customerSession;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Execute
     *
     * @param Observer $observer Observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->moduleManager->isOutputEnabled('Iconocoders_OtpSimple')) {
            $customerSession = $this->customerSession;
            $order = $observer->getEvent()->getOrder();
            $customerSession->setSimpleOrderIncrementId($order->getIncrementId());
        }
    }
}
