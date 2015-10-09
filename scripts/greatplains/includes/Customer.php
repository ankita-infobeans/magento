<?php
require_once '../../app/Mage.php';

class Local_Customer extends Mage_Customer_Model_Customer {
	

	
	
	public function loadCustomerByEmail($email) {
		//qecho "email $email\n";
	
		//$collection = Mage::getResourceModel ( 'customer/customer_collection' )->addAttributeToSelect ( '*' )->addAttributeToFilter ( 'email', $email );
		$collection = Mage::getModel('customer/customer')->getCollection()->
		addAttributeToSelect ( '*' )->
		addAttributeToFilter ( 'email', $email );

		if ($collection->count() < 1)
			return false;
		
		$tmp =  $collection->getFirstItem ();
		$this->load($tmp->getId());
		
		return $this;
		
	
	}
	
	
	
	public function checkAddress($data)
	{
		//echo "\nchecking address\n";
		$found = false;
        
        
        if($data['bill_street'] == "")
        {
           // echo "street is empty returning default address\n";
           $data['bill_street'] = $data['first_name']." ".$data['last_name'];
           $data['bill_city'] = "none";
           $data['bill_zip'] = "00000";
           $data['bill_country'] = "US";
           $data['bill_phone'] = "5555555555";
           
            //return $this->getDefaultBillingAddress();
        }
        
		foreach ($this->getAddresses() as $address) {

			$st = $address->getStreet();
			$st = $st[0];
            //echo " checking address '".trim($st)."' and '".trim($data['bill_street'])."'\n";
			if((trim($data['bill_street']) == $st))
			{
				$found = true;
               // echo "returning address\n";
				return $address;
			}
		}
		
		if(!$found)
		{
			return $this->addAddress($data);
		}
		
		
	}
	public function addAddress($data)
	{
	    //echo "Adding Address\n";
		//Build billing and shipping address for customer, for checkout
		$_custom_address = array (
				'firstname' =>  $data['first_name'],
				'lastname' =>  $data['last_name'],
				'street' => array (
						'0' =>  $data['bill_street'],
				),
		
				'city' =>  $data['bill_city'],
				'region_id' => '',
				'region' => '',
				'postcode' => $data['bill_zip'],
				'country_id' => $data['bill_country'],
				'telephone' =>  $data['bill_phone'],
		);
		
		$customAddress = Mage::getModel('customer/address')
		//echo "-------";print_r($customAddress);
		
		->setData($_custom_address)
		->setCustomerId($this->getId())
		->setIsDefaultBilling('1')
		->setIsDefaultShipping('1')
		->setSaveInAddressBook('1');
		
		try {
		   // echo "Saving address to customer\n";
            $customAddress->save();
           // echo "------\n";
		}
		catch (Exception $ex) {
			Zend_Debug::dump($ex->getMessage());
		}
		
		return $customAddress;
	}
	/*
	 * [first_name] => Greg [last_name] => Walker [email] =>
	 * greg.walker@otis.com [member_nu] => 1078553 [bill_street] => [bill_city]
	 * => [bill_state] => [bill_zip] => [bill_country] => [bill_phone] =>
	 */
	public function createNewCustomer($data) {
		
		//print_r ( $data );
		//return;
		//continue;
		//exit ();
		
		$this->setEmail ( $data ['email'] );
		$this->setFirstname ( $data['first_name'] );
		$this->setLastname (  $data['last_name'] );
		$this->setPassword ( $this->generatePassword () );
        $this->setMemberNumber($data['member_nu']);
        
		try {
			$this->save ();
			$this->setConfirmation ( null );
			$this->save ();
			//echo "saving customer\n";
			// Make a "login" of new customer
		//	Mage::getSingleton ( 'customer/session' )->loginById ( $customer->getId () );
			
		//	echo "customer is logged in\n";
		} 

		catch ( Exception $ex ) {
			 Zend_Debug::dump($ex->getMessage());
		}
		
	
		//$this->addAddress(data);
		
		return $this;
		
		
	
	}
	
	function generatePassword($length = 8) {
		
		$password = "";
		
		$possible = "1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM";
		
		$maxlength = strlen ( $possible );
		
		if ($length > $maxlength) {
			$length = $maxlength;
		}
		
		$i = 0;
		
		while ( $i < $length ) {
			
			$char = substr ( $possible, mt_rand ( 0, $maxlength - 1 ), 1 );
			
			if (! strstr ( $password, $char )) {
				$password .= $char;
				$i ++;
			}
		
		}
		
		// done!
		return $password;
	}

}