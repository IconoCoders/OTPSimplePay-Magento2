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
 * @version   GIT: Release: 3.0.0-beta
 * @link      http://iconocoders.com
 */
namespace Iconocoders\OtpSimple\Model;
use Iconocoders\OtpSimple\SDK\SimpleLiveUpdate;
use \Otp\Simplepay\SimplePayStart;

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

    public $trx_result;

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

        $resolver = $this->objectManager->get('Magento\Framework\Locale\Resolver');
        $address = $order->getBillingAddress();
        $config = $this->helper->getConfiguration();

        //Tranzakcio start elokeszitese es meghivasa
        $trx = new SimplePayStart;
        $trx->addConfig($config);

        $trx->addData('currency', 'HUF');
        $trx->addData('total', $order->getGrandTotal());
        $trx->addData('orderRef', $order->getIncrementId()); ###


        $trx->addData('customer', $address->getCustomerName() ); ###
        if ($address->getEmail()) $trx->addData('customerEmail', trim($address->getEmail())); ###
        $trx->addData('language', strtolower(strstr($resolver->getLocale(), '_', true))=='hu' ? 'HU' : 'EN'); ###
        $timeoutInSec = 600;
        $timeout = @date("c", time() + $timeoutInSec);
        $trx->addData('timeout', $timeout);

        $trx->addData('methods', array('CARD'));
        $trx->addData('url', $config['URL']);

        if ($address->getName()) {
            $trx->addGroupData('invoice', 'name', $address->getName()); ###
            $trx->addGroupData('invoice', 'company', $address->getCompany()); ###
            $trx->addGroupData('invoice', 'country', $address->getCountryId()); // $address->getCountry()->getTwoLetterAbbreviation() ); ###
            $trx->addGroupData('invoice', 'state', $address->getState()); ###
            $trx->addGroupData('invoice', 'city', $address->getCity()); ###
            $trx->addGroupData('invoice', 'zip', $address->getPostcode()); ###
            $street = $address->getStreet();
            $trx->addGroupData('invoice', 'address', $street[0]); ###
            if (!empty($street[1])) $trx->addGroupData('invoice', 'address2', $street[1]); ###
            if ($address->getTelephone()) $trx->addGroupData('invoice', 'phone', $address->getTelephone()); ###
        }
        $trx->formDetails['element'] = 'button';
        $trx->runStart();
        $this->trx_result = $trx->getReturnData();
        return;

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
