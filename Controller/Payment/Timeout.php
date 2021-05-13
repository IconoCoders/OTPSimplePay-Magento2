<?php
/**
 * IPN Manager Controller
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
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\Session\Storage as CustomerSession;

class Timeout extends Action
{
	protected $_pageFactory;

	/**
     * Result Page Factory
     *
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
	protected $messageManager;

    /**
     * @var $orderFactory
     */
    private $orderFactory;

    /**
     * Customer Session
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var string
     */
    protected $lang;

    /**
     * Timeout constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param OrderInterface $order
     * @param ManagerInterface $messageManager
     * @param CustomerSession $customerSession
     * @param OrderFactory $orderFactory
     */
	public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        OrderFactory $orderFactory,
        ManagerInterface $messageManager,
        CustomerSession $customerSession
		)
	{
		$this->messageManager = $messageManager;
		$this->resultPageFactory = $resultPageFactory;
		$this->orderFactory = $orderFactory;
        $this->customerSession = $customerSession;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resolver = $objectManager->get('Magento\Framework\Locale\Resolver');
        $this->lang = strtolower(strstr($resolver->getLocale(), '_', true))=='hu' ? 'hu' : 'en';

		parent::__construct($context);
	}

    /**
     * @return mixed
     */
	public function execute()
	{
        $customerSession = $this->customerSession;
        $values = $this->getRequest()->getParams();
        $incrementId = $values['order_ref'];
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);

        if($values['redirect']) {
            $this->messageManager->addError(
                $this->lang=='hu' ? ("Ön megszakította a fizetést!")
                    :     ("You have canceled the payment!")
            );
        } else {
            $this->messageManager->addError(
                $this->lang=='hu' ? ("Ön túllépte a tranzakció elindításának lehetséges maximális idejét!")
                    :     ("You have exceeded the maximum possible time to start the transaction!")
            );
        }

        $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
        $order->save();

        $customerSession->setBackrefData($values);

        return $this->_redirect('checkout/onepage/failure');
    }
}
