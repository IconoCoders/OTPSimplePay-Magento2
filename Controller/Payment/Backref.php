<?php
/**
 * Payment Redirect Controller
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
     * @var string
     */
    protected $lang;

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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resolver = $objectManager->get('Magento\Framework\Locale\Resolver');
        $this->lang = strtolower(strstr($resolver->getLocale(), '_', true))=='hu' ? 'hu' : 'en';

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function execute()
    {
        $helper = $this->dataHelper;
        $provider = $this->configProvider;
        $config = $this->dataHelper->getConfiguration();

        $trx = new \Otp\Simplepay\SimplePayBack;
        $trx->addConfig($config);
        $result = array();
        if (isset($_REQUEST['r']) && isset($_REQUEST['s'])) {
            if ($trx->isBackSignatureCheck($_REQUEST['r'], $_REQUEST['s'])) {
                $result = $trx->getRawNotification();
                if (!empty($result)) {
                    $orderRef = $result['o'];
                    $transactionId = $result['t'];
                    $merchant = $result['m'];

                    $this->order->loadByIncrementId($orderRef);
                    if (!empty($this->order) && !empty($result['e'])) {
                        if ($result['e']=='SUCCESS') return $this->success($transactionId, $merchant);
                        if ($result['e']=='CANCEL') return $this->cancel($transactionId, $merchant);
                        if ($result['e']=='FAIL') return $this->fail( $transactionId, $merchant);
                        if ($result['e']=='TIMEOUT') return $this->timeout($transactionId, $merchant);
                    }
                }
            }
        }
        $this->messageManager->addError("A fizetés nem sikerült. ");
        return false;
    }


    protected function success( $transactionId, $merchant) {
        $helper = $this->dataHelper;
        $provider = $this->configProvider;
        $order = $this->order;

        if ( $order->getStatus() != $helper->getOrderStatus()) {
            $this->messageManager->addSuccess(
                $this->lang=='hu' ? ("Sikeres tranzakció. SimplePay tranzakció azonosító: $transactionId. Megrendelés azonosító: ".$this->order->getIncrementId())
                                  : ("Successful transaction. SimplePay transaction identifier: $transactionId. Order reference: ".$this->order->getIncrementId())

                );
            $order->setStatus($helper->getOrderStatus());
            $order->addStatusToHistory(
                $order->getStatus(),
                'Order is waiting for IPN'
            );
            $order->save();
        } else {
            $this->messageManager->addSuccess(
                    $this->lang=='hu' ? ("A rendelés már fel van dolgozva. SimplePay tranzakció azonosító: ".$transactionId)
                                      :     ("Your order is already processed. SimplePay transaction identifier: ".$transactionId)
                    );
        }

        return $this->_redirect($provider->getDefaultSuccessPageUrl());

    }

    protected function cancel($transactionId, $merchant) {
        $this->messageManager->addErrorMessage(
            $this->lang=='hu' ? ("Ön megszakította a fizetést. SimplePay tranzakció azonosító: $transactionId")
                              : ("You cancelled the payment, please try again. SimplePay transaction identifier: $transactionId")
            );
        return $this->_redirect('checkout/cart');
    }

    protected function fail($transactionId, $merchant) {
        $this->messageManager->addError(
            $this->lang=='hu' ? (
                                    "Sikertelen tranzakció. A rendelés nem lett kifizetve. ".
                                    "SimplePay tranzakció azonosító: $transactionId ".
                                    "Kérjük, ellenőrizze a tranzakció során megadott adatok helyességét. Amennyiben minden adatot helyesen adott meg, a visszautasítás okának kivizsgálása érdekében kérjük, szíveskedjen kapcsolatba lépni kártyakibocsátó bankjával."
                                 )
                              : (
                                    "Failed transaction. Your order has not been paid, please try again. ".
                                    "SimplePay transaction identifier: $transactionId ".
                                    "Please check if the details provided during the transaction are correct. If all of the details were provided correctly, please contact the bank that issued your card in order to investigate the cause of the rejection."
                                 )
                                 );

        $this->order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
        $this->order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
        $this->order->save();

        return $this->_redirect('checkout/onepage/failure');

    }

    protected function timeout($transactionId, $merchant) {

        $this->messageManager->addError(
            $this->lang=='hu' ? ("Ön túllépte a tranzakció elindításának lehetséges maximális idejét, a rendelés nem lett kifizetve. ".
                                "SimplePay tranzakció azonosító: $transactionId "
                                )
                              : ("Timeout, please try your payment again. ".
                                "SimplePay transaction identifier: $transactionId ")
                              );

        $this->order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
        $this->order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
        $this->order->save();

        return $this->_redirect('checkout/onepage/failure');

    }


}
