<?php
/**
 * Payment Model
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
namespace Iconocoders\OtpSimple\Model;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * OtpSimple
 */
class OtpSimple extends AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'otpsimple';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    public $_isInitializeNeeded = true;

    /* Ez kell ahhoz, h a vasarlas ne processing statusba keruljon (hanem pendingbe) mielott elkuldenenk az otp-hez fizetni */
    public function isInitializeNeeded()
    {
        return $this->_isInitializeNeeded;
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}
