<?php
/**
 * Payment Model
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
