<?php

namespace Iconocoders\OtpSimple\SDK\v2;

/**
 * Class SimplePayCardQuery
 */
class SimplePayCardQuery extends Base
{
    protected $currentInterface = 'cardquery';
    protected $returnArray      = [];
    public    $transactionBase  = [
        'salt'     => '',
        'cardId'   => '',
        'history'  => false,
        'merchant' => '',
    ];

    /**
     * Constructor for cardquery
     *
     * @return void
     */
    public function __construct()
    {
        $this->apiInterface['cardquery'] = '/v2/cardquery';

        parent::__construct();
    }

    /**
     * Run CardQuery
     *
     * @return array $result API response
     */
    public function runCardQuery()
    {
        return $this->execApiCall();
    }
}
