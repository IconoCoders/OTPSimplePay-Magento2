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
 * @version   GIT: Release: 2.3.6
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
    const ORDER_STATUS_COMPLETE = 'COMPLETE';

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
        $values = $this->request->getPost();
        if(!$values) {
            throw new NotFoundException(__('Required IPN parameters not exists.'));
        }

        $this->sourceStringArray[] = $values['IPN_PID'][0];
        $this->sourceStringArray[] = $values['IPN_PNAME'][0];
        $this->sourceStringArray[] = $values['IPN_DATE'];
        $this->sourceStringArray[] = date('YmdHis', time());

        $hash = $this->_calculateHash();

        $response = '<EPAYMENT>'.$this->sourceStringArray[3].'|'.$hash.'</EPAYMENT>';
        $result = $this->rawResultFactory->create();
        $result->setHeader('Content-Type', 'text/xml');
        $result->setContents($response);
        $this->order->loadByIncrementId($values['REFNOEXT']);
        $this->_setOrderStatus($values['ORDERSTATUS'], $values['REFNOEXT']);

        return $result;
    }

    /**
     * Calculate Hash
     *
     * @return string
     */
    private function _calculateHash()
    {
        $sourceString = '';
        foreach ($this->sourceStringArray as $source) {
            $sourceString .= strlen($source).$source;
        }

        return $this->helper->calculateHash($sourceString);
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
