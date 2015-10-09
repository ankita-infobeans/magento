<?php

class Gorilla_Paymentech_Model_Source_ProfileResponseElement extends Gorilla_Paymentech_Model_Source_ProfileAddRequestElement {

    public function __construct($data = null) {

        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }

    public $profileAction;
    public $procStatus;
    public $procStatusMessage;

}
