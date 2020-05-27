<?php
/**
 * Data Helper
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
namespace Iconocoders\OtpSimple\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

/**
 * Data
 */
class Data extends AbstractHelper
{
    const CONFIG_MERCHANT = 'payment/otpsimple/merchant';

    const CONFIG_SECRET_KEY = 'payment/otpsimple/secret_key';

    const CONFIG_PAYMENT_MODE = 'payment/otpsimple/mode';

    const CONFIG_LOG_ENABLED = 'payment/otpsimple/log';

    const CONFIG_DEBUG_ENABLED = 'payment/otpsimple/debug';

    const CONFIG_NEW_ORDER_STATUS = 'payment/otpsimple/order_status';

    /**
     * Currently selected store ID if applicable
     *
     * @var int
     */
    private $storeId;

    /**
     * Currency
     *
     * @var string
     */
    private $currency;

    /**
     *
     * @var Encrypted
     */
    private $encryptor;

    /**
     * Request instance
     *
     * @var RequestInterface
     */
    protected $httpRequest;

    /**
     * Store Manager
     *
     * @var StoreManagerInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $dir;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $file;

    /**
     * Constructor
     *
     * @param Context          $context     Context
     * @param Encrypted        $encryptor   Encryptor
     */
    public function __construct(
        Context $context,
        Encrypted $encryptor,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Filesystem\DirectoryList $dir
//        RequestInterface $httpRequest
    ) {
        parent::__construct($context);

        $this->encryptor = $encryptor;
        $this->httpRequest = $context->getRequest();
        $this->urlBuilder = $context->getUrlBuilder();
        $this->file = $file;
        $this->dir = $dir;
    }

    /**
     * Set a specified store ID value
     *
     * @param int $store Store Id
     *
     * @return $this
     */
    public function setStoreId($store)
    {
        $this->storeId = $store;
        return $this;
    }

    /**
     * Get Merchant Identifier
     *
     * @param int $storeId Store view ID
     *
     * @return string
     */
    public function getMerchant($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_MERCHANT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Order Status
     *
     * @param int $storeId Store view ID
     *
     * @return string
     */
    public function getOrderStatus($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_NEW_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Secret Key
     *
     * @param int $storeId Store view ID
     *
     * @return string
     */
    public function getSecretKey($storeId = null)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        return $this->encryptor->processValue(
            $this->scopeConfig->getValue(
                self::CONFIG_SECRET_KEY,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );
    }

    /**
     * Get Payment Mode
     *
     * @param int $storeId Store view ID
     *
     * @return bool
     */
    public function getPaymentMode($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PAYMENT_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is Log Enabled
     *
     * @param int $storeId Store view ID
     *
     * @return bool
     */
    public function isLogEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_LOG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is Debug Enabled
     *
     * @param int $storeId Store view ID
     *
     * @return bool
     */
    public function isDebugEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_DEBUG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        $protocol = 'https';
        $baseURL = $this->urlBuilder->getBaseUrl();
        //$baseURL = str_replace('https://', '', $baseURL);

        $logPath = $this->dir->getPath('var').'/simplepay';
        if ( ! @file_exists($logPath)) {
            $this->file->mkdir($logPath);
        }

        $currency = $this->currency ? $this->currency : 'HUF';

        $config = [

            $currency.'_MERCHANT' => $this->getMerchant($this->storeId),
            $currency.'_SECRET_KEY' => $this->getSecretKey($this->storeId),

            'SANDBOX' => $this->getPaymentMode($this->storeId),

            //common return URL
            'URL' => $baseURL .'otpsimple/payment/backref',

            'GET_DATA' => (isset($_GET['r']) && isset($_GET['s'])) ? ['r' => $_GET['r'], 's' => $_GET['s']] : [],
            'POST_DATA' => $_POST,
            'SERVER_DATA' => $_SERVER,

            'LOGGER' => true,          //basic transaction log
            'LOG_PATH' => $logPath,    //path of log file

            'AUTOCHALLENGE' => true,                      //in case of unsuccessful payment with registered card run automatic challange
        ];

        return $config;
    }

    /**
     * Set Currency
     *
     * @param string $currency Currency Code
     *
     * @return string
     */
    public function setCurrency($currency)
    {
        return $this->currency = strtoupper($currency);
    }

    /**
     * Calaculate Hash
     *
     * @param string $sourceString Source String
     *
     * @return string
     */
    public function calculateHash($sourceString)
    {
        return hash_hmac(
            'md5',
            $sourceString,
            trim($this->getSecretKey())
        );
    }

    /**
     * Get Http Request
     *
     * @return RequestInterface
     */
    public function getHttpRequest()
    {
        return $this->_getRequest();
    }
}
