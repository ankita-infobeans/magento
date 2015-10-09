<?php

class Gorilla_Paymentech_Model_Source_ReversalResponseElement extends Gorilla_Paymentech_Model_Source_ReversalElement {

    public $respDateTime;
    public $procStatus;
    public $procStatusMessage;
    public $retryAttemptCount;
    public $lastRetryDate;
    public $outstandingAmt;

    public function __construct($data = null) {

        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }

}

?>
