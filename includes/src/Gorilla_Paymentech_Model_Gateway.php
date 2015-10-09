<?php

class Gorilla_Paymentech_Model_Gateway extends Mage_Payment_Model_Method_Cc {

    /**
     * unique internal payment method identifier
     */
    protected $_code = 'paymentech';
    /**
     * this should probably be true if you're using this
     * method to take payments
     */
    protected $_formBlockType = 'paymentech/form_cc';
    /**
     * The Block type for the Payment Info
     *
     * @var string
     */
    protected $_infoBlockType = 'paymentech/info_cc';
    protected $_isGateway = true;
    /**
     * can this method authorise?
     */
    protected $_canAuthorize = false;
    /**
     * can this method capture funds?
     */
    protected $_canCapture = true;
    /**
     * can we capture only partial amounts?
     */
    protected $_canCapturePartial = false;
    /**
     * can this method refund?
     */
    protected $_canRefund = true;
    /**
     * can this method void transactions?
     */
    protected $_canVoid = true;
    /**
     * can admins use this payment method?
     */
    protected $_canUseInternal = true;
    /**
     * show this method on the checkout page
     */
    protected $_canUseCheckout = true;
    /**
     * available for multi shipping checkouts?
     */
    protected $_canUseForMultishipping = true;
    /**
     * can this method save cc info for later use?
     */
    protected $_canSaveCc = false;

    /* Payment Profile Object */
    protected $_profile;

    public function getCode() {

        return $this -> _code;
    }

    public function __construct() {
        $this -> _profile = new Gorilla_Paymentech_Model_Profile();
        parent::__construct();
    }

    /**
     * Validate the provided payment information - happens after customer clicks
     * next from payment section of checkout.
     *
     * @return Gorilla_Paymentech_Model_Gateway
     */
    public function validate() {

        $paymentInfo = $this -> getInfoInstance();

        //Mage::Log(print_r($paymentInfo -> debug(), true));

        if ($paymentInfo -> getAdditionalInformation('paymentech_card') != "NEWCARD") {// stored card
            $profile = new Gorilla_Paymentech_Model_Profile();

            if ($profile -> getProfileByRefNum($paymentInfo -> getAdditionalInformation('paymentech_card'))) {

                return $this;
            }

            Mage::throwException("Error with stored Profile");
            return $this;
        }

        return parent::validate();
    }

    /**
     * this method is called if we are just authorising
     * a transaction
     */
    public function authorize(Varien_Object $payment, $amount) {

        $profile = $this -> _profile -> createOrder($payment, "authorize", $amount);

        if ($this -> _profile -> getSoap() -> getErrors()) {
            foreach ($this->_profile->getSoap()->getErrors() as $error) {
                Mage::helper('paymentech') -> Log($error, Zend_Log::ERR);
                Mage::throwException($error);
            }
        }

        $quote = Mage::getModel('checkout/cart') -> getQuote();
        $payment -> setTransactionId($quote -> getTxRefNum());

        return $this;
    }

    /**
     * this method is called if we are authorising AND
     * capturing a transaction
     */
    public function capture(Varien_Object $payment, $amount) {

        $profile = $this -> _profile -> createOrder($payment, "capture", $amount);

        $cardid = $payment ->additional_information['paymentech_card'];
        if ($this -> _profile -> getSoap() -> getErrors()) {

            foreach ($this->_profile->getSoap()->getErrors() as $error) {
                Mage::helper('paymentech') -> Log("Capture : " . $error, Zend_Log::ERR);
                //Mage::getSingleton('core/session')->addError($error);
                Mage::throwException($error);
            }
            return false;
        }

        $quote = Mage::getModel('checkout/cart') -> getQuote();
        // Mage::log(print_r($profile, true));
        //   Mage::log(print_r($profile->getResponse(), true));

        // die ;
        $payment -> setTransactionId($profile -> getResponse() -> txRefNum);
        $quote -> setTransactionId($profile -> getResponse() -> txRefNum);
        $quote -> setCustomerCreditCardId($cardid);
        $quote -> setLastFour($cardid);
        $quote->setAdditionalInformation("customer_credit_card_id",$cardid);
        $quote->setAdditionalInformation("card_id",$cardid);
        
        // Mage::log(print_r($quote->debug(), true));
        //die ;
        return $this;
    }

    /**
     * called if refunding
     */
    public function refund(Varien_Object $payment, $amount) {
        $txid = $payment -> getRefundTransactionId();
        $profile = $this -> _profile -> Refund($payment, $amount);

        Mage::helper('paymentech') -> Log("--------------refund profile--------------------");
        Mage::helper('paymentech') -> Log(print_r($profile, true));
        Mage::helper('paymentech') -> Log("--------------end refund profile--------------------");

        if ($profile -> getResponse() -> error) {
            foreach ($profile->getResponse()->error as $error) {
                Mage::helper('paymentech') -> Log($error, Zend_Log::ERR);
                Mage::throwException($error);
            }
            return $this;
        }
        $payment -> setTransactionId($txid);
        return $this;
    }

    /**
     * called if voiding a payment
     */
    public function void(Varien_Object $payment) {

        $this -> refund($payment, null);
    }

    public function assignData($data) {

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        if ($data -> getCcSaveCard() == "Yes") {
            $cc_save_card = true;
        } else {
            $cc_save_card = false;
        }

        $info = $this -> getInfoInstance();
        $info -> setCcType($data -> getCcType()) -> setCcOwner($data -> getCcOwner()) -> setCcLast4(substr($data -> getCcNumber(), -4)) -> setCcNumber($data -> getCcNumber()) -> setCcCid($data -> getCcCid()) -> setCcExpMonth($data -> getCcExpMonth()) -> setCcExpYear($data -> getCcExpYear()) -> setCcSsIssue($data -> getCcSsIssue()) -> setCcSsStartMonth($data -> getCcSsStartMonth()) -> setCcSsStartYear($data -> getCcSsStartYear()) -> setCcSaveCard('true') -> setAdditionalInformation('paymentech_card', $data -> getPaymentechCard()) -> setAdditionalInformation('cc_save_card', $cc_save_card);
        return $this;
    }

}
