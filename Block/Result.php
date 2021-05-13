<?php
/**
 * Order Success Page - OTP Result Block
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
namespace Iconocoders\OtpSimple\Block;

use Magento\Customer\Model\Session\Storage as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Result
 */
class Result extends Template
{
    /**
     * Customer Session
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Constructor
     *
     * @param Context         $context         Context
     * @param CustomerSession $customerSession Customer Session
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
    }

    /**
     * Get Backref Data
     *
     * @return array
     */
    public function getBackrefData()
    {
        return $this->customerSession->getBackrefData();
    }

    /**
     * Get Customer Session
     *
     * @return CustomerSession
     */
    public function customerSession()
    {
        return $this->customerSession;
    }
}
