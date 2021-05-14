<?php

namespace Iconocoders\OtpSimple\SDK\v2;

// @codingStandardsIgnoreFile

/**
 * Class SimplePayDo
 */
class SimplePayDo extends Base
{
    protected $currentInterface = 'do';
    protected $returnArray      = [];
    public    $transactionBase  = [
        'salt'          => '',
        'orderRef'      => '',
        'customerEmail' => '',
        'merchant'      => '',
        'currency'      => '',
        'customer'      => '',
    ];

    /**
     * Constructor for do
     *
     * @return void
     */
    public function __construct()
    {
        $this->apiInterface['do'] = '/v2/do';

        parent::__construct();
    }

    /**
     * Run Do
     *
     * @return array $result API response
     */
    public function runDo()
    {
        return $this->execApiCall();
    }
}
