<?php
/**
 * Simple Payment Object
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
namespace Iconocoders\OtpSimple\Model;
use Iconocoders\OtpSimple\SDK\SimpleLiveUpdate;

/**
 * SimpleObject
 */
class SimpleObject
{
    /**
     * @var \Iconocoders\OtpSimple\Model\SimpleLiveUpdate;
     */
    private $simpleLiveUpdate;
    /**
     * @var \Iconocoders\OtpSimple\Helper\Data
     */
    private $helper;
    /**
     * @var \Magento\Sales\Model\Order
     */
    private $objectManager;
    private $currency;
    private $sourceStringArray = [];

    /**
     * Class constructor.
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function __construct(\Magento\Sales\Model\Order $order)
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->helper = $this->objectManager->create('Iconocoders\OtpSimple\Helper\Data');
        $this->helper->setCurrency($order->getStoreCurrencyCode());
        $this->currency = $order->getStoreCurrencyCode();
        $this->simpleLiveUpdate = new SimpleLiveUpdate(
            $this->helper->getConfiguration(),
            $order->getStoreCurrencyCode()
        );
        $this->simpleLiveUpdate->setField("ORDER_REF", $order->getIncrementId());
        //$this->simpleLiveUpdate->setField("ORDER_DATE", $order->getCreatedAt());
        //$this->simpleLiveUpdate->setField("PRICES_CURRENCY", $this->currency);
        $this->sourceStringArray[1] =  [$this->helper->getMerchant()];
        $this->sourceStringArray[2] =  [$order->getIncrementId()];
        $this->sourceStringArray[3] =  [$order->getCreatedAt()];
        $this->sourceStringArray[12] =  [0];

        /** Shipping Amount */
        $shippingAmount = $this->currency == 'HUF'
            ? round($order->getShippingInclTax())
            : $order->getShippingInclTax();
        $this->sourceStringArray[11] =  [$this->currency];
        $this->simpleLiveUpdate->setField("ORDER_SHIPPING", $shippingAmount);
        $this->simpleLiveUpdate->setField("DISCOUNT", $order->getDiscountAmount());
        $this->sourceStringArray[10] =  [$shippingAmount];

        /** Payment site languge */
        $resolver = $this->objectManager->get('Magento\Framework\Locale\Resolver');
        $language = strstr($resolver->getLocale(), '_', true);
        $this->simpleLiveUpdate->setField("LANGUAGE", $language);

        $this->_setItems($order->getItems());
        $this->_setBillingData($order->getBillingAddress());
        if($order->getShippingAddress()) {
            $this->_setShippingAddress($order->getShippingAddress());
        } else {
            $this->_setShippingAddress($order->getBillingAddress());
        }
        ksort($this->sourceStringArray);
        $hash = $this->_calculateHash();
        $order->setOtpSimpleHash($hash);
        $order->save();
    }

    /**
     * Set Items
     *
     * @param array $items
     */
    private function _setItems($items)
    {
        $this->sourceStringArray[4] = [];
        $this->sourceStringArray[5] = [];
        $this->sourceStringArray[7] = [];
        $this->sourceStringArray[8] = [];
        $this->sourceStringArray[9] = [];

        foreach ($items as $item) {
            if ($item->getPrice() != 0) {
                $product = [
                    'name' => $item->getName(),
                    'code' => $item->getSku(),
                    'price' => $this->currency == 'HUF'
                    ? round($item->getPriceInclTax())
                    : $item->getPriceInclTax(),
                    'vat' => 0,
                    'qty' => $item->getQtyOrdered(),
                ];
                $this->sourceStringArray[4][] = $product['name'];
                $this->sourceStringArray[5][] = $product['code'];
                $this->sourceStringArray[7][] = $product['price'];
                $this->sourceStringArray[8][] = $product['qty'];
                $this->sourceStringArray[9][] = $product['vat'];

                $this->simpleLiveUpdate->addProduct($product);
            }
        }
    }

    /**
     * Set Billing Data
     * @param \Magento\Sales\Model\Order\Address $address
     */
    private function _setBillingData(\Magento\Sales\Model\Order\Address $address)
    {
        $this->simpleLiveUpdate->setField("BILL_FNAME", $address->getFirstname());
        $this->simpleLiveUpdate->setField("BILL_LNAME", $address->getLastname());
        $this->simpleLiveUpdate->setField("BILL_EMAIL", $address->getEmail());
        $this->simpleLiveUpdate->setField("BILL_PHONE", $address->getTelephone());
        $this->simpleLiveUpdate->setField("BILL_COMPANY", $address->getCompany());
        $this->simpleLiveUpdate->setField("BILL_COUNTRYCODE", $address->getCountryId());
        $this->simpleLiveUpdate->setField("BILL_STATE", $address->getRegion());
        $this->simpleLiveUpdate->setField("BILL_CITY", $address->getCity());
        $this->simpleLiveUpdate->setField("BILL_ADDRESS", implode(" ", $address->getStreet()));
        $this->simpleLiveUpdate->setField("BILL_ZIPCODE", $address->getPostcode());
    }

    /**
     * Set Shipping Address
     *
     * @param \Magento\Sales\Model\Order\Address $address
     */
    private function _setShippingAddress(\Magento\Sales\Model\Order\Address $address)
    {
        $this->simpleLiveUpdate->setField("DELIVERY_FNAME", $address->getFirstname());
        $this->simpleLiveUpdate->setField("DELIVERY_LNAME", $address->getLastname());
        $this->simpleLiveUpdate->setField("DELIVERY_EMAIL", $address->getEmail());
        $this->simpleLiveUpdate->setField("DELIVERY_PHONE", $address->getTelephone());
        $this->simpleLiveUpdate->setField("DELIVERY_COUNTRYCODE", $address->getCountryId());
        $this->simpleLiveUpdate->setField("DELIVERY_STATE", $address->getRegion());
        $this->simpleLiveUpdate->setField("DELIVERY_CITY", $address->getCity());
        $this->simpleLiveUpdate->setField("DELIVERY_ADDRESS", implode(" ", $address->getStreet()));
        $this->simpleLiveUpdate->setField("DELIVERY_ZIPCODE", $address->getPostcode());
    }

    /**
     * Calculate Hash
     *
     * @return string
     */
    private function _calculateHash()
    {
        $sourceString = '';
        foreach ($this->sourceStringArray as $sources) {
            foreach ($sources as $source) {
                $sourceString .= strlen($source).$source;
            }
        }

        return $this->helper->calculateHash($sourceString);
    }

    /**
     * Redirect
     *
     * @return string Redirect HTML
     */
    public function redirect()
    {
        $display = $this->simpleLiveUpdate->createHtmlForm('SinglePayForm', 'auto');
        return '<div style="display: none;">'.$display.'</div>';
    }
}
