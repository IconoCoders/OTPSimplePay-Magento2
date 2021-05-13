<?php
/**
 * Payment Redirect Controller
 *
 * PHP Version 7
 *
 * @category  OtpSimple
 * @package   Iconocoders
 * @author    Iconocoders <support@icoders.co>
 * @copyright 2017-2021 Iconocoders
 * @license   MIT License - https://iconocoders.com/license/
 * @version   GIT: Release: 3.0.0-beta
 * @link      http://iconocoders.com
 */
namespace Iconocoders\OtpSimple\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Iconocoders\OtpSimple\Model\SimpleObject;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Customer\Model\Session\Storage as CustomerSession;

/**
 * Redirect
 *
 */
class Redirect extends Action
{
    /**
     * Result Page Factory
     *
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * Order Entity
     *
     * @var OrderInterface
     */
    private $order;

    /**
     * Customer Session
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Redirect constructor.
     *
     * @param Context         $context           Context
     * @param PageFactory     $resultPageFactory Result
     * @param OrderInterface  $order             Order
     * @param CustomerSession $customerSession   Customer Session
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        OrderInterface $order,
        CustomerSession $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->order = $order;
        $this->customerSession = $customerSession;

        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        $customerSession = $this->customerSession;
        $orderIncrementId = $customerSession->getSimpleOrderIncrementId();
        $order = $this->order;
        $order->loadByIncrementId($orderIncrementId);

        $simpleObject = new SimpleObject($order);
        $this->_redirect($simpleObject->trx_result['paymentUrl']);
    }
}
