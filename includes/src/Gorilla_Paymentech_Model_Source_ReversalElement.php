<?php

class Gorilla_Paymentech_Model_Source_ReversalElement extends Gorilla_Paymentech_Model_Source_TransactionElement {

    public $txRefIdx;
    public $adjustedAmount;
    public $reversalRetryNumber;
    public $retryTrace;
    public $onlineReversalInd;
    public $txRefNum;

    public function __construct() {

        parent::__construct();
    }

}