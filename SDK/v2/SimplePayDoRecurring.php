<?php

namespace Iconocoders\OtpSimple\SDK\v2;

/**
 * Class SimplePayDoRecurring
 */
class SimplePayDoRecurring extends Base
{
    protected $currentInterface = 'dorecurring';

    /**
     * Constructor for dorecurring
     *
     * @return void
     */
    public function __construct()
    {
        $this->apiInterface['dorecurring'] = '/v2/dorecurring';

        parent::__construct();
    }

    /**
     * Run Dorecurring
     *
     * @return array $result API response
     */
    public function runDorecurring()
    {
        return $this->execApiCall();
    }
}
