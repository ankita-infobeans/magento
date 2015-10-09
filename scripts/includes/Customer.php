<?php

require_once 'includes/File.php';
require_once 'includes/Order.php';

class Customer {
	
	/*
	 * [first_name] => Randy [last_name] => Roig [member_nu] => NULL
	 * [bill_street] => 2100 Embarcadero Suite 204 [bill_city] => Oakland
	 * [bill_state] => CA [bill_zip] => 94606 [bill_country] => USA [bill_phone]
	 * => 5106580591 [email] => rroig@stcenv.com
	 */
	private $_customer;
	
	public function setData($c_data) {
		$this->_customer = Mage::getModel ( 'customer/customer' )->setWebsiteId ( 0 );
		
		if (($c_data ['email'] == "")) {
			return false;
		}
		
		$this->_customer->setWebsiteId ( 0 );
		$this->_customer->loadByEmail ( $c_data ['email'] )->setWebsiteId ( 0 );
		
		if (! $this->_customer->getId ()) {
			//echo " cannot find " . $c_data ['email'] . "\n";
			$this->_customer->setEmail ( $c_data ['email'] );
			$this->_customer->setFirstname ( $c_data ['first_name'] );
			$this->_customer->setLastname ( $c_data ['last_name'] );
		} else {
			return $this;
		}
		$this->_customer->setEmail ( $c_data ['email'] );
		$this->_customer->save ();
		
		$_custom_address = array ('firstname' => $c_data ['first_name'], 'lastname' => $c_data ['last_name'], 'street' => array ('0' => $c_data ['bill_street'] ), 
		'city' => $c_data ['bill_city'], 'region_id' => '', 'region' => $c_data ['bill_state'], 'postcode' => $c_data ['bill_zip'], 'country_id' => 'US', /* Croatia */
				'telephone' => $c_data ['bill_phone'] );
		$customAddress = Mage::getModel ( 'customer/address' )->setData ( $_custom_address )->setCustomerId ( $this->_customer->getId () )->setIsDefaultBilling ( '1' )->setIsDefaultShipping ( '1' )->setSaveInAddressBook ( '1' );
		
		try {
			$customAddress->save ();
		} catch ( Exception $ex ) {
			Zend_Debug::dump ( "foo " . $ex->getMessage () );
		}
		return $this;
	
	}
	
	public function __construct() {
	
	}
	
	public function getCustomerByEmail($email) {
		$this->_customer = Mage::getModel ( 'customer/customer' )->setWebsiteId ( 0 );
		//print_r($this->_customer);
		//echo "$email 1";
		if ($email == "") {
			return false;
		}
		//echo "2";
		//$this->_customer;//->setWebsiteId ( 0 );
		//print_r($this->_customer);
		//echo "3";
		$this->_customer->loadByEmail ( $email );//->setWebsiteId ( 0 );
		//echo "1111111111111111111111111111111111";
		///print_r($this->_customer);
		//echo "2222222222222222222222222";
		//echo "4";
		//return $this->_customer;
		return $this->getCustomer ();
	
	}
	
	public function getCustomer() {
		return $this->_customer;
	}
	
	private function createCustomer() {
	
	}
}

?>