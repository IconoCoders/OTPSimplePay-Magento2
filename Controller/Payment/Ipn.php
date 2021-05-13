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
use Iconocoders\OtpSimple\Helper\Data as DataHelper;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\NotFoundException;

/**
 * IPN
 */
class Ipn extends Action implements CsrfAwareActionInterface
{
    const ORDER_STATUS_COMPLETE = 'FINISHED';

    /**
     * Raw Result Factory
     *
     * @var RawFactory
     */
    private $rawResultFactory;

    /**
     * Data Helper
     *
     * @var Data
     */
    private $helper;

    /**
     * Order Interface
     *
     * @var OrderInterface
     */
    private $order;

    /**
     * @var $orderFactory
     */
    private $orderFactory;

    /**
     * Objectmanager
     *
     * @var Objectmanager
     */
    private $objectManager;

    /**
     * Source String Array
     *
     * @var array
     */
    private $sourceStringArray = [];

     /**
     * RequestInterface
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * Ipn constructor.
     * @param Context $context
     * @param RawFactory $rawResultFactory
     * @param Order $order
     * @param OrderFactory $orderFactory
     * @param DataHelper $helper
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        RawFactory $rawResultFactory,
        Order $order,
        OrderFactory $orderFactory,
        DataHelper $helper,
        RequestInterface $request
    ) {
        $this->rawResultFactory = $rawResultFactory;
        $this->helper = $helper;
        $this->order = $order;
        $this->orderFactory = $orderFactory;
        $this->request = $request;

        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Execute
     *
     * @return RawFactory
     */
    public function execute()
    {

        $helper = $this->helper;
        $config = $helper->getConfiguration();


        $json = file_get_contents('php://input');
        $values = json_decode($json, true);
        $trx = new \Otp\Simplepay\SimplePayIpn;
        $trx->addConfig($config);

        if ($trx->isIpnSignatureCheck($json)) {
            if ($trx->runIpnConfirm()) {
                $this->_setOrderStatus($values['status'], $values['orderRef']);
                exit;//helyes valaszt ad vissza az otp-nek
            } else {
                $order = $this->orderFactory->create()->loadByIncrementId($values['orderRef']);
                if (!empty($order)) {
                    $order->addStatusToHistory($order->getStatus(),'OTP IPN error');
                    $order->save();
                }
            }
        }


    }

    /**
     * Set Order Status
     *
     * @param string $orderStatus Status
     * @param integer $incrementId IncrementId
     *
     * @return void
     */
    private function _setOrderStatus($orderStatus, $incrementId)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);

        if ($orderStatus == self::ORDER_STATUS_COMPLETE) {
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        } else {
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
        }
        $order->addStatusToHistory(
            $order->getStatus(),
            'Order has received IPN with "'.$orderStatus.'" order status'
        );

        $order->save();
    }
}
