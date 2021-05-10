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
 * @version   GIT: Release: 2.3.51
 * @link      http://iconocoders.com
 */
namespace Iconocoders\OtpSimple\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Iconocoders\OtpSimple\Helper\Data as DataHelper;
use Iconocoders\OtpSimple\Helper\Checkout;
use Magento\Customer\Model\Session\Storage as CustomerSession;

/**
 * Backref
 */
class Backref extends Action
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
     * @var Checkout
     */
    private $checkoutHelper;

    /**
     * @var DefaultConfigProvider
     */
    private $configProvider;

    /**
     * HTTP Request
     *
     * @var RequestInterface
     */
    private $httpRequest;

    /**
     * Data Helper
     *
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Customer Session
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Backref constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param OrderInterface $order
     * @param DefaultConfigProvider $configProvider
     * @param DataHelper $dataHelper
     * @param Checkout $checkoutHelper
     * @param CustomerSession $customerSession
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        OrderInterface $order,
        DefaultConfigProvider $configProvider,
        DataHelper $dataHelper,
        Checkout $checkoutHelper,
        CustomerSession $customerSession,
        ManagerInterface $messageManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->order = $order;
        $this->configProvider = $configProvider;
        $this->httpRequest = $dataHelper->getHttpRequest();
        $this->dataHelper = $dataHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function execute()
    {
        $helper = $this->dataHelper;
        $provider = $this->configProvider;
        $values = $this->getRequest()->getParams();

        $customerSession = $this->customerSession;
        $incrementId = $customerSession->getSimpleOrderIncrementId();
        $this->order->loadByIncrementId($incrementId);
        $customerSession->setBackrefData($values);

        //SIKERES
        if ($values['RC'] == 001 || $values['RC'] == 000) {
            //tranzakcio ellenorzes
            $simplebackref = new \Iconocoders\OtpSimple\SDK\SimpleBackRef(
                $this->dataHelper->getConfiguration(),
                $values['order_currency']
            );

            $simplebackref->checkResponse();
            $responseArray = $simplebackref->backStatusArray;
            $simpleTrxDate = $responseArray['BACKREF_DATE'];
            $simpleTrxId = $responseArray['PAYREFNO'];
            $simpleOrderId = $responseArray['REFNOEXT'];

            $this->messageManager->addSuccess("Sikeres tranzakció! SimplePay tranzakció azonosító: $simpleTrxId. Megrendelés azonosító: $simpleOrderId / Dátum: $simpleTrxDate");

            $this->order->setStatus($helper->getOrderStatus());
            $this->order->addStatusToHistory(
                $this->order->getStatus(),
                'Order is waiting for IPN'
            );
            $this->order->save();

            return $this->_redirect($provider->getDefaultSuccessPageUrl());
        }

        //SIKERTELEN
        else {
            $simpleTrxDate = $values['date'];
            $simpleTrxId = $values['payrefno'];
            $simpleOrderId = $values['order_ref'];

            $this->messageManager->addError("Sikertelen tranzakció! SimplePay tranzakció azonosító: $simpleTrxId.  Kérjük, ellenőrizze a tranzakció során megadott adatok helyességét.
Amennyiben minden adatot helyesen adott meg, a visszautasítás okának kivizsgálása érdekében kérjük, szíveskedjen kapcsolatba lépni kártyakibocsátó bankjával! Megrendelés azonosító: $simpleOrderId / Dátum: $simpleTrxDate");
            $this->order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
            $this->order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
            $this->order->save();

            return $this->_redirect('checkout/onepage/failure');
        }

        return false;
    }
}
