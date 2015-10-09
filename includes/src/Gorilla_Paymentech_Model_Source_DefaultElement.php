<?php

class Gorilla_Paymentech_Model_Source_DefaultElement {
    public $orbitalConnectionUsername;
    public $orbitalConnectionPassword;
    public $version;
    public $merchantID;
    public $bin;
    public $industryType;
    public $terminalID;
    public $error;
    public $avsName;

    public function __construct() {

        $this->orbitalConnectionUsername =  Mage::helper('paymentech')->getApiLogin();
        $this->orbitalConnectionPassword =  Mage::helper('paymentech')->getApiPassword();
        $this->version = Gorilla_Paymentech_Model_Profile::VERSION;
        $this->merchantID =  Mage::helper('paymentech')->getMerchantId();
        $this->bin = Gorilla_Paymentech_Model_Profile::BIN;
        $this->terminalID = Gorilla_Paymentech_Model_Profile::TERMINALID;
        $this->industryType = "EC";
    }

}