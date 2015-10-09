<?php

require_once 'includes/Order.php';
require_once 'includes/Customer.php';

class File {

	private $_filename;
	private $_data;
	
	private $_orders;
	
	private $_customerArray;
	
	
	private $_orderArray;
	
	private $_currentWorkingOrderNumber = 0;
	private $_currentWorkingCustomerNumber = 0;
	
	//private $_workingOrder;
	
	
	function load($filename)
	{
		$this->_filename = $filename;
		if (($handle = fopen ( $this->_filename, "r" )) !== FALSE) {
			$this->_data = array ();
			while ( ($data = fgetcsv ( $handle, 1000, "," )) !== FALSE ) {
				$this->_data [] = $data;
			}
			fclose ( $handle );
		}
		array_shift ($this->_data);
	}
	
	function process()
	{
		$this->processLinesToOrderArray();
	}
	
	function processLinesToOrderArray() {
	
		$orderArray = array ();
		$customerArray = array();
		
		foreach ( $this->_data as $line ) {
				
			$email = $line [0];
				
			$first_name = $line [1];
			$last_name = $line [2];
			$coupon_number = $line [3];
			$old_order_id_a = $line [4];
			$old_order_id_b = $line [5];
			$order_datetime = $line [6];
			$status = $line [7];
			$member_nu = $line [8];
			$bill_street = $line [9];
			$bill_city = $line [10];
			$bill_state = $line [11];
			$bill_zip = $line [12];
			$bill_country = $line [13];
			$bill_phone = $line [14];
			$product_name = $line [15];
			$eCodes_ID = $line [16];
			$product_sku = $line [17];
			$line_item_total = $line [18];
			$product_qty = $line [19];
			$download_serial_number = $line [20];
			$download_remaining_downloads = $line [21];
			$subscription_start_date = $line [22];
			$subscription_end_date = $line [23];
			$subscription_num_users = $line [24];
			$subscription_master_user_name = $line [25];
			$subscription_master_password = $line [26];
			$download_subscription = $line [27];
				
				
			$product ['product_name'] = $product_name;
			$product ['eCodes_ID'] = $eCodes_ID;
			$product ['product_sku'] = $product_sku;
			$product ['line_item_total'] = $line_item_total;
			$product ['product_qty'] = $product_qty;
			$product ['download_serial_number'] = $download_serial_number;
			$product ['download_remaining_downloads'] = $download_remaining_downloads;
			$product ['subscription_start_date'] = $subscription_start_date;
			$product ['subscription_end_date'] = $subscription_end_date;
			$product ['subscription_num_users'] = $subscription_num_users;
			$product ['subscription_master_user_name'] = $subscription_master_user_name;
			$product ['subscription_master_password'] = $subscription_master_password;
			$product ['download_subscription'] = $download_subscription;
				
		

			
			$orderArray [$email] ['orders'] [$old_order_id_a]['email'] = $email;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['member_nu'] = $member_nu;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['status'] = $status;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['old_order_id_b'] = $old_order_id_b;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['old_order_id_a'] = $old_order_id_a;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['order_datetime'] = $order_datetime;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['status'] = $status;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['member_nu'] = $member_nu;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_street'] = $bill_street;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_city'] = $bill_city;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_state'] = $bill_state;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_zip'] = $bill_zip;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_country'] = $bill_country;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_phone'] = $bill_phone;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['products'] [] = $product;
			
			
			
			$customerArray [$email]['first_name'] = $first_name;
			$customerArray [$email]['last_name'] = $last_name;
			$customerArray [$email]['member_nu'] = $member_nu;
			$customerArray [$email]['bill_street'] = $bill_street;
			$customerArray [$email]['bill_city'] = $bill_city;
			$customerArray [$email]['bill_state'] = $bill_state;
			$customerArray [$email]['bill_zip'] = $bill_zip;
			$customerArray [$email]['bill_country'] = $bill_country;
			$customerArray [$email]['bill_phone'] = $bill_phone;

		}
		
		foreach($orderArray as $email=>$data)
		{
			foreach($data['orders'] as $order)
			{
				//print_r($order);
				$this->_orderArray[] = $order;
			//echo "\n";
			}
				
		}
			
		foreach($customerArray as $email=>$data)
		{
			
			$data['email'] = $email;
				//print_r($order);
				$this->_customerArray[] = $data;
				//echo "\n";
			
		
		}
			
	
		
		//$this->_customerArray = $customerArray;
		//echo "<pre>";
		//print_r($this->_orderArray);
		//echo "</pre>";
		
		//echo "<pre>";
		//print_r($this->_customerArray);
		//echo "</pre>";
		//die;
	}
	

	function getNextCustomer()
	{
		
		if(count($this->_customerArray) > $this->_currentWorkingCustomerNumber)
		{
			$this->_currentWorkingCustomerNumber++;
			return $this->_customerArray[$this->_currentWorkingCustomerNumber];
		}
		$this->_currentWorkingCustomerNumber = 0 ;
		
		return false;
	}
	
	function getNextOrder()
	{
		//echo "get next order\n";
		if(count($this->_orderArray) > $this->_currentWorkingOrderNumber)
		{
			$this->_currentWorkingOrderNumber ++;
			return $this->_orderArray[$this->_currentWorkingOrderNumber];
		}
		$this->_currentWorkingOrderNumber = 0 ;
	
		return false;
	}
}

?>