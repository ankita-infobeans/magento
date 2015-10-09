<?php
class ICC_Customer_Model_Session extends Mage_Customer_Model_Session {

	public function isMember() {
		return in_array($this->getCustomer()->group_id, array(
			2  // Member
		));
	}

	public function isReseller() {
		return in_array($this->getCustomer()->group_id, array(
			3,  // Reseller
			7,  // Hidden Reseller
		));
	}

    /**
     * Customer authorization
     *
     * @param   string $username
     * @param   string $password
     * @return  bool
     *
     * AVECTRA login
     */
	
     public function login($username, $password)
     {
        if (Mage::getStoreConfig('customer/avectra/login_hook') == 1)
            return parent::login($username, $password);

        $key = Mage::getModel('icc_avectra/account')->getAvectraKeyByUserPass($username,$password);
        if ($key && ($key != '00000000-0000-0000-0000-000000000000'))
        {
            $customer = Mage::getModel('icc_avectra/account')->getUserByAvectraKey($key);
            if ($customer && $customer->getId())
            {
                $this->setCustomerAsLoggedIn($customer);
                $this->renewSession();
                $cookie = Mage::getSingleton('core/cookie');
                $cookie->set('Gorilla', $key);

                return true;
            }
            else
            {
                $avcustomer = Mage::getModel('icc_avectra/account')->getAvCustomer($key);
                if ($avcustomer)
                {
                    $customerData = (array)$avcustomer->Individual;
                    if (count($customerData))
                    {
                        $firstname = isset($customerData['ind_first_name']) ? $customerData['ind_first_name'] : '';
                        $lastname = isset($customerData['ind_last_name']) ? $customerData['ind_last_name'] : '';

                        $filteredData = array(
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'email' => $username,
                            'password' => $password,
                            'avectra_key' => $key
                        );
                        $customer = Mage::getModel('customer/customer');
                        $customer->addData($filteredData);
                        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                        $customer->setConfirmation(null);

                        try
                        {
                            $customer->save();
                            $this->loginById($customer->getId());
                            $cookie = Mage::getSingleton('core/cookie');
                            $cookie->set('Gorilla', $key);

                            return true;
                        }
                        catch (Exception $e) { }
                    }
                }
            }
        }

        $errorMessage = Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/login_error_message');
        if (empty($errorMessage))
            $errorMessage = Mage::helper('customer')->__('Invalid Email or Password.');
        throw Mage::exception('Mage_Core', $errorMessage, Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD);
    }
    
    /**
     * Get customer group id
     * If customer is not logged in system, 'not logged in' group id will be returned
     *
     * @return int
     */
    
     public function getCustomerGroupId() {
    	$tmpgroup =  $this -> getCustomerTempGroup();
    
    	if ($tmpgroup != "") {
    		return $tmpgroup;
    	}
    
    	if ($this -> getData('customer_group_id')) {
    		return $this -> getData('customer_group_id');
    	}
    	return ($this -> isLoggedIn()) ? $this -> getCustomer() -> getGroupId() : Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
    }
}
