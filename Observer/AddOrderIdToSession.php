<?php
/**
 * Add Order Id To Session Observer
 *
 * PHP Version 7
 *
 * @category  OtpSimple
 * @package   Iconocoders
 * @author    Iconocoders <support@icoders.co>
 * @copyright 2017-2021 Iconocoders
 * @license   MIT License - https://iconocoders.com/license/
 * @version   GIT: Release: 2.3.51
 * @link      http://iconocoders.com
 */
namespace Iconocoders\OtpSimple\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
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

    public function __construct(CustomerSession $customerSession)
    {
        $this->customerSession = $customerSession;
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
        $customerSession = $this->customerSession;
        $order = $observer->getEvent()->getOrder();
        $customerSession->setSimpleOrderIncrementId($order->getIncrementId());
    }
}
