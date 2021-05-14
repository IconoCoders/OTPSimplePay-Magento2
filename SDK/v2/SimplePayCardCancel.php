<?php

namespace Iconocoders\OtpSimple\SDK\v2;

/**
 * Class SimplePayCardCancel
 */
class SimplePayCardCancel extends Base
{
    protected $currentInterface = 'cardcancel';
    protected $returnArray      = [];
    public    $transactionBase  = [
        'salt'     => '',
        'cardId'   => '',
        'merchant' => '',
    ];

    /**
     * Constructor for cardcancel
     *
     * @return void
     */
    public function __construct()
    {
        $this->apiInterface['cardcancel'] = '/v2/cardcancel';

        parent::__construct();
    }

    /**
     * Run CardCancel
     *
     * @return array $result API response
     */
    public function runCardCancel()
    {
        return $this->execApiCall();
    }
}
