<?php
class ICC_BillMember_Model_Observer
{
    /**
     *  KEEP FUNCTION - AT CLIENT REQUEST
    public function redirectOnCreditHold(Varien_Event_Observer $observer)
    {   
        $session = Mage::getSingleton('customer/session');
        if( ! $session->isLoggedIn() ) {
            return false;
        }
        
        $customer = $session->getCustomer();
        
        if( $customer->getCreditHold() ) {
            $message = Mage::helper('icc_billmember')->getAccountHoldMessage();
            Mage::getSingleton('customer/session')->addError($message);
            echo header('Location: /customer/account');
            exit;
        }
    }
     * 
     */
}