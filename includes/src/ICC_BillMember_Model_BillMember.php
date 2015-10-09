<?php

//    protected $_isGateway                   = false;
//    protected $_canOrder                    = false;
//    protected $_canAuthorize                = false;
//    protected $_canUseInternal = true;

class ICC_BillMember_Model_BillMember extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'icc_billmember';
    // either array if display errors, false if no display errors or true - show option
    protected static $results; // store results for a single page load / ajax load
    
    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    
    public function getResults()
    {
        if(is_null(self::$results)) {
            $this->isBillMemberAvailable();
        }
        return self::$results;   
    }
    
    public function setResults($results)
    {
        self::$results = $results;
    }
     
    public function isAvailable($quote = null)
    {   
        $result = $this->isBillMemberAvailable();

        if($result === true) {
            return parent::isAvailable();
        }
        return false;
    }

    private function isBillMemberAvailable()
    {
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();

		$grandTotal = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal(); // for frontend

		if (!$customer->getEmail()) {
			$session = Mage::getSingleton('adminhtml/session_quote');
        	$customer = $session->getCustomer();
			Mage::log('backend customer', null, 'billmember-admin.log');
			Mage::log($customer->getEmail(), null, 'billmember-admin.log');
			$grandTotal = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getGrandTotal();
		}

        $memberStatus = (bool)$customer->getMemberStatus();
        $creditHold = (bool)$customer->getCreditHold();
        $creditLimit = (float) $customer->getCreditLimit();

        if( ! $memberStatus ) {
            return false;
        }

        if($creditHold) {
            $message = Mage::helper('icc_billmember')->getAccountHoldMessage();
            $this->setResults(array('message' => $message ));
            return false;
        }
        if (($creditLimit < $grandTotal)) {
            $this->setResults(array('message' => 'The amount in your cart is larger than your credit limit ($' . $creditLimit . ').'));
            return false;
        }

           /**** Infobeans ****/
        
        //return ( $memberStatus );
        
//        $session = Mage::getSingleton('customer/session');
//        if( ! $session->isLoggedIn() ) {
//            return false;
//        }
//        $customer = $session->getCustomer();
//
    /*    $account = Mage::getModel('icc_avectra/account');
//        
//        if( !$customer || !((bool)(int)$customer->getMemberStatus()) ) // check out local copy first
//        {   //Mage::log( 'locally does not have bill memeber status', null, 'model-billmember.log');
//            // no error message
//            $this->setResults(false);
//            return false;
//        }
//        // now ensure from Avectra
////return true;
        if( ! $account->hasAvectraConnection())
        {   //Mage::log( 'no connection', null, 'model-billmember.log');
            if( Mage::app()->getFrontController()->getRequest()->getParam('conn'))
            {
                $this->setResults(false);
                return false;
            }
            $this->setResults(array('message' => 'Sorry, we could not connect to the the webservice to verify your Bill Member status.'));
            return false;
        }
        $is_billmember = $account->hasBillMemberStatus($customer->getAvectraKey());
        if(!$is_billmember)
        {   //Mage::log( 'Avectra said no', null, 'model-billmember.log');
            $this->setResults(array('message' => 'Sorry, it seems you no longer qualify for Bill Member status.'));
            return false;
        }
//
        //xdebug_break();
        $is_credit_hold = $account->hasCreditHold($customer->getAvectraKey());
        if( $is_credit_hold ) // we should never get here - should be caught on another hook
        {   //Mage::log( 'has a credit hold', null, 'model-billmember.log');
            $message = Mage::helper('icc_billmember')->getAccountHoldMessage();
            Mage::getSingleton('customer/session')->addError($message);
            return false;
        }
//        
        $grand_total = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
        $credit_limit =  $account->getCreditLimit($customer->getAvectraKey());
        //Mage::log( $grand_total . ' is cart total and credit limit is: ' . $credit_limit, null, 'model-billmember.log');
        if( $grand_total > $credit_limit )
        {   //Mage::log( 'The total in the cart is greater than their credit limit', null, 'model-billmember.log');
            $this->setResults(array('message' => 'The amount in your cart is larger than your credit limit ($' . $credit_limit . ').'));
            return false;
        }*/
         /**** Infobeans ****/
        $this->setResults(true);
        return true;
    }
}
