<?php
/**
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

/**
 * OTPSimple Payment Observer
 */
namespace Iconocoders\OtpSimple\Observer;

use Magento\Framework\Event\ObserverInterface;

class BeforeOrderPaymentSaveObserver implements ObserverInterface
{
    /**
     * Sets current instructions for OTP Simple transaction
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getEvent()->getPayment();
        $payment->setAdditionalInformation(
            'instructions',
            $payment->getMethodInstance()->getInstructions()
        );
    }
}
