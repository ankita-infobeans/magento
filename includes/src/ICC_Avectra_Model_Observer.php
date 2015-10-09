<?php

class ICC_Avectra_Model_Observer
{
	protected static $_alreadyAddedCustomerId;
    	private $_loginHook = 0;
        
        public function getIndToken()
        {
		$cookieIndToken = Mage::getModel('core/cookie')->get('iccAuth');
                $sessionIndToken = Mage::getSingleton('customer/session')->getIccAuth();
                
                if(!$cookieIndToken && Mage::helper('customer')->isLoggedIn())
                {
                    return $sessionIndToken;
                }
                else if($cookieIndToken && Mage::helper('customer')->isLoggedIn() && ($cookieIndToken != $sessionIndToken))
                {
                    return $sessionIndToken;
                }
                /* else if($cookieIndToken && Mage::helper('customer')->isLoggedIn() && ($cookieIndToken == $sessionIndToken))
                {
                    return $cookieIndToken;
                }*/
                
                return $cookieIndToken;
        }
        
	public function checkLoginCookie(Varien_Event_Observer $observer)
	{ Mage::log('come here===>',null,'debug.log',true);  
            //if (Mage::getStoreConfig('customer/avectra/login_hook') != 1)
            if ($this->_loginHook != 1)
            {   
		$key = Mage::app()->getRequest()->getParam('ind_token');    
                
		$signinCookie = $this->getIndToken();

            if (!Mage::helper('customer')->isLoggedIn() && $key && ($key != '00000000-0000-0000-0000-000000000000'))        
                {  
                    $customer = Mage::getModel('icc_avectra/account')->getUserByIndToken($key);

                    // New Customer get their volume license producst while login
                    Mage::getModel('volumelicense/volumelicense')->assignVolumelicense($customer);
                    Mage::getModel('icc_premiumaccess/premiumaccess')->assignPremiumaccess($customer);
                    if ($customer && $customer->getId())
                    {
                            $session = Mage::getSingleton('customer/session');
                            $session->setCustomerAsLoggedIn($customer);
                            $session->renewSession();
                            $session->setIccAuth($key);
                            $cookie = Mage::getSingleton('core/cookie');
                            setcookie('iccAuth', $key, time() + 3600, '/', 'iccsafe.org');
                            return $this;

                    }
                } elseif ( !Mage::helper('customer')->isLoggedIn() && $signinCookie) {
                    
                    // Code added for customer logged in, if token saved on global cookie 
                    $customer = Mage::getModel('icc_avectra/account')->getUserByIndToken($signinCookie);

                    // New Customer get their volume license producst while login
                    Mage::getModel('volumelicense/volumelicense')->assignVolumelicense($customer);
                    Mage::getModel('icc_premiumaccess/premiumaccess')->assignPremiumaccess($customer);
                    if ($customer && $customer->getId())
                    {      
                            $session = Mage::getSingleton('customer/session');
                            $session->setCustomerAsLoggedIn($customer);
                            $session->renewSession();
                            Mage::dispatchEvent('customer_login', array('customer'=>$customer));
                            header("Refresh:0");
                            return $this;
                    }
                }              
            } else {
                $signin_cookie = $this->getIndToken();

                $session = Mage::getSingleton('customer/session');
                preg_match('/{(.+)}/', $signin_cookie, $matches);
                $key = (isset($matches[1])) ? $matches[1] : false;
                if( ! $key) {
                    $session->logout(); // respect SSO cookie if customer logged out from a non-magento page
                    $this->deleteSharePointSsoCookie();
                    $session->setProcessedCookie(false);
                    return false;
                }
                // respect SSO cookie if customer logged out from a non-magento page
                if($session->isLoggedIn()) {
                    if($key === false || $key != $session->getCustomer()->getAvectraKey()) {
                        $session->logout();
                        $this->deleteSharePointSsoCookie();
                        $session->setProcessedCookie(false);
                        return false;
                    }
                }

                // make sure we don't add to the queue on every page load
                if($session->getProcessedCookie() && $session->isLoggedIn()) { // if not logged in means this  "already processed cookie" test is a false positive
                    return false;
                }
                if( ! preg_match('/^\{?[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\}?$/', strtolower($key) )) {
                    return; // the key doesn't match the anticipation of a hhhhhhhh-hhhh-hhhh-hhhh-hhhhhhhhhhhh so it's not a GUID (h = hexidecimal)
                }
                $customer = Mage::getModel('icc_avectra/account')->getUserByAvectraKey($key); // will return false if it does not find exactly 1 user

                if( ! $customer)
                {
                    $account = Mage::getModel('icc_avectra/account');
                    $customer = $account->createNewUser($key);
                    if( ! $customer)
                    {
                        $av_queue = Mage::getModel('icc_avectra/avectraQueue');
                        $av_queue->addCreateUser($key);
                        $session->addError('We could not create your account. Please contact ICC with your information and this error message.');
                        return;
                    }
                }

                if( $customer ) {
                    // login - following procedure in: Mage_Customer_Model_Session lines 214, 215
                    $session->setCustomerAsLoggedIn($customer);
                    $session->renewSession();
                    // queue address update
                    $av_q = Mage::getModel('icc_avectra/avectraQueue');
                    $av_q->addUpdateUser($customer->getAvectraKey());
                }
                $session->setProcessedCookie(true);
            }  
        } 
        
        
        /**
        * Method for updating user from Avectra while Add To Cart
        * @return Queue Entry
        */
	public function syncWhileAddToCart()
        {
	        //Get the IntToken from Cookie
            $intToken = $this->getIndToken();
 
            if ( $intToken && Mage::helper('customer')->isLoggedIn())
            {    
                //Queue, Account and Avectra Communication Object intiation 
                $avectraAccount = Mage::getModel('icc_avectra/account');
                $avectraComm = Mage::getModel('icc_avectra/avectraCommunication');                           

              $customer = $avectraComm->getCustomerByIndToken($intToken);
              if( $customer )
               {
                 //Get the Avectra Key from Customer
                 $avectraKey = $customer->getAvectraKey();			    

                 //Call update user from Avectra
                 $avectraAccount->quickUpdateUser($avectraKey);

                 //Customer Data Syncronize entry in queue
                 $queueModel = Mage::getModel('gorilla_queue/queue');
                 $this->_logSoapToQueue($queueModel, $avectraAccount);
                 $queueModel->addToQueue($this->getMageModelClass(), 'updateAvectra', array('avectra_key' => $avectraKey), $code = 'update-avectra')->setShortDescription('Quick Update user while Add-To-Cart for user : '.$customer->getEmail());

                 //Update Queue Entry Status
                 $queueModel->setStatus(Gorilla_Queue_Model_Queue::STATUS_SUCCESS);
                 $queueModel->setLastAttempt(date('Y-m-d H:i:s'));
                 $queueModel->setNumberOfTimesAttempted(0);
                 $queueModel->save();  
                }           
            }	  
        }                      

	public function _logSoapToQueue($q, $account)
	{
	    $client = $account->getAvComm()->getClient();
	    if ($client instanceof SoapClient) {
		    $q->setSoapRequest($account->getAvComm()->getClient()->__getLastRequest());
		    $q->setSoapResponse($account->getAvComm()->getClient()->__getLastResponse());
	    }
	}
    
	public function getMageModelClass()
	{
	  return 'icc_avectra/avectraQueue';
	}
    
	public function deleteSharePointSsoCookie()
	{       
        	
	   $session = Mage::getSingleton('customer/session');
            $session->setProcessedCookie(false);

            Mage::getSingleton('core/cookie')->delete('iccAuth', '/', 'iccsafe.org');
            setcookie("iccAuth", "",time()-3600);
            Mage::app()->getRequest()->setParam('ind_token','');
	    $key = Mage::app()->getRequest()->getParam('ind_token');    

	    Mage::getSingleton('core/cookie')->delete('iccAuth');
            Mage::getSingleton('customer/session')->logout();
	    Mage::getSingleton('customer/session')->unsetAll();

	}

	public function deleteAddressFromAvectra(Varien_Event_Observer $observer)
	{
		$address = $observer['customer_address'];
		if($address->getIsAffiliatedOrg()) {
			return;
		}
		// return if we can not confirm the number of addresses is more than one
		$customer = $address->getCustomer();
		if( ! $customer ) return;

		$_numberOfAddresses = count( $customer->getAddresses() );
		if($address->getAvectraKey() && ($_numberOfAddresses > 1) && $customer->getAvectraKey())
		{
			$account = Mage::getModel('icc_avectra/account');
			$customer_av_key = $customer->getAvectraKey();
			$axc_key = $address->getAvectraKey();
			$account->deleteAvectraAddress($axc_key, $customer_av_key);
		}
	}

	public function addAvectraUpdateToQueue(Varien_Event_Observer $observer)
	{
		//Mage::Log("addAvectraUpdateToQueue");
		$customer = $observer['customer'];
		$this->_addCustomerToQueue($customer);
	}

    public function addAddressAvectraUpdateToQueue(Varien_Event_Observer $observer)
    {
  	$address = $observer['customer_address'];
	$customer = $address->getCustomer();
	$this->_addCustomerToQueue($customer);
    }

    //redirect user to shop Avectra's login if login cookie is present
    public function checkReferrer(Varien_Event_Observer $observer)
    {   
        $url = Mage::helper('icc_avectra')->getLoginUrl();
        
        if (!Mage::helper('customer')->isLoggedIn() && Mage::getSingleton('core/cookie')->get('iccAuth'))
        {   
            $observer->getControllerAction()->getResponse()->setRedirect($url, 301);
            $observer->getControllerAction()->getRequest()->setDispatched(true);
        }
    }

	private function _addCustomerToQueue($customer)
	{
	    if( ! is_null($this->getAlreadyAddedCustomerId()) && $customer->getId() == $this->getAlreadyAddedCustomerId()) {
		Mage::log('not adding because added customer id is: ' . $this->getAlreadyAddedCustomerId() . ' the customer id is: ' .  $customer->getId(), null, 'avectra-communication.log', true );
		return;
	    }
	
            $avectraQ = Mage::getModel('icc_avectra/avectraQueue');
        if (!$customer->getAvectraKey()){
            $recNo = $customer->getCustomerNo();
		    $avectraQ->addUpdateAvectra($recNo, null, true);
            return;
        }
		$avectraQ->addUpdateAvectra($customer->getAvectraKey());
		$this->setAlreadyAddedCustomerId($customer->getId());
	}

	private function getAlreadyAddedCustomerId()
	{
		return self::$_alreadyAddedCustomerId;
	}

	private function setAlreadyAddedCustomerId($customerId)
	{
		self::$_alreadyAddedCustomerId = $customerId;
	}
}
