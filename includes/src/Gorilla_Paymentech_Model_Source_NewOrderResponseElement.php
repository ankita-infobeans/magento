<?php

class Gorilla_Paymentech_Model_Source_NewOrderResponseElement extends Gorilla_Paymentech_Model_Source_NewOrderRequestElement {

    public $procStatus;
    public $approvalStatus;
    public $respCode;
    public $authorizationCode;
    public $procStatusMessage;
    public $hostRespCode;
    public $hostAVSRespCode;
    public $hostCVVRespCode;
    public $retryTrace;
    public $retryAttempCount;
    public $lastRetryDate;
    public $customerName;
    public $profileProcStatus;
    public $profileProcStatusMsg;
    public $requestedAmount;
    public $redeemedAmount;

    public function __construct($data = null) {

        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }

}

