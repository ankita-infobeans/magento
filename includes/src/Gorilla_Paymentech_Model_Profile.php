<?php

class Gorilla_Paymentech_Model_Profile extends Mage_Core_Model_Abstract {
    const VERSION = "2.6"; /// CIM GATEWAY version
    const BIN = "000002";  // transaction routing definition
    const TERMINALID = "001";

    protected $_link = null;
    
    public function __construct() {
        $this->_init("paymentech/profile");
    }


    public function createOrder($paymentInfo, $type, $amount) {

        $paymentech_card_id = $paymentInfo->getAdditionalInformation("paymentech_card");
        $save = $paymentInfo->getAdditionalInformation("cc_save_card");

     

        /*
         * Create order using existing stored card
         */

        if ($paymentech_card_id != "NEWCARD" && $paymentech_card_id != null) {
            $data = $this->getSoap()->createOrder($paymentInfo, $type, $amount, false, $paymentech_card_id);
            return $data;
        }

        
        /*
         * New Card
         * To save or not to save, that is the question
         */
        
        if ($save) {
            return $this->getSoap()->createOrder($paymentInfo, $type, $amount, true);
        } else {
            return $this->getSoap()->createOrder($paymentInfo, $type, $amount);
        }

    }

    public function Refund($paymentInfo, $amount = null) {

        return $this->getSoap()->Refund($paymentInfo, $amount);
    }

    public function getSoap() {
        if ($this->_link == null)
        {
            Mage::helper('paymentech')->Log("getting new soap");
            $this->_link = new Gorilla_Paymentech_Model_Profile_Soap();
            return $this->_link;
        }

        return $this->_link;
    }

    public function getCustomerProfile($customer_id) {

        $profile = $this->getCollection()
                ->addFilter('customer_id', $customer_id)
                ->load();

        $profiles = array();
        foreach ($profile as $single) {
            $a = $this->fetchProfile(
                            $single->getCustomerRefNum())->return;
            $a->id = $single->getId();
            $profiles[] = $a; 
        }
        return $profiles;
    }


    public function getProfileByRefNum($customer_ref_num)
    {
         $profile = $this->getCollection() // first check locally to make sure there is a profile
                ->addFilter('customer_ref_num', $customer_ref_num)
                ->load();

         if($profile)
         {
             return $this->fetchProfile($customer_ref_num); // then verify that it is saved on chase
         }
         return $profile;
    }
    private function fetchProfile($id) {

        return $this->getSoap()->fetchProfile($id);
    }

    public function deleteProfile($id)
    {
         return $this->getSoap()->deleteProfile($id);
        
        
    }
    public function createProfile($data)
    {
         return $this->getSoap()->createProfile($data);
    }
    
    
     public function deleteProfileFromDatabase($id) {
        $model = Mage::getModel('paymentech/profile');
        $model->setId($id)->delete();
        return $this;
        //$model->delete();       
    }
    public function getCustomerPaymentProfile($id) {
        //echo $id;
        $profile = $this->getCollection()
                ->addFilter('id', $id)
                ->load();
        $profile = $profile->getFirstItem();
        //print_r($profile->getData());

        $ret = $this->fetchProfile($profile->getCustomerRefNum())->return;
        // print_r($ret);
        //$ret['id'] = $id;
        $data[$id] = $ret;
        return $data;
    }
}

?>
