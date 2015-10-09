<?php

require_once '../app/Mage.php';




$process = new processLegacyOrders ();

$process->run ();

class processLegacyOrders {
	private $data;
	private $orderArray;

	
	
	public $filename = "ecodes_data_history_2.csv";
	
	function __construct(){
		Mage::app ();
		
	}
	
	function run() {
		$this->loadFile ();
		$this->processLinesToOrderArray ();
		
		foreach ( $this->orderArray as $orders ) {
			foreach ( $orders ['orders'] as $order ) {
				$this->createOrder ( $order );
			}
		}
	
	}
	
	function getCustomer($custdata)
	{
		
		
		$id = 5;
		$customer = Mage::getModel('customer/customer')->load($id);
		
	}
	
	function isGuest()
	{
		return false;
	}
	
	function createOrder($order) {
		
		
		
		$customer = $this->getCustomer($order);
		
		
		$transaction = Mage::getModel('core/resource_transaction');

		$storeId = $customer->getStoreId();
		
		$reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);
		
		$order = Mage::getModel('sales/order')->setIncrementId($reservedOrderId)->setStoreId($storeId)->setQuoteId(0);
		
		//Set your store currency USD or any other
		
		// set Customer data
		$order->setCustomer_email($customer->getEmail())->setCustomerFirstname($customer->getFirstname())
		->setCustomerLastname($customer->getLastname())->setCustomerGroupId($customer->getGroupId())->setCustomer_is_guest(0)->setCustomer($customer);
		
		// set Billing Address
		$billing = $customer->getDefaultBillingAddress();
		$billingAddress = Mage::getModel('sales/order_address')
		->setStoreId($storeId)
		->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
		->setCustomerId($customer->getId())
		->setCustomerAddressId($customer->getDefaultBilling())
		->setCustomer_address_id($billing->getEntityId())
		->setPrefix($billing->getPrefix())
		->setFirstname($billing->getFirstname())
		->setMiddlename($billing->getMiddlename())
		->setLastname($billing->getLastname())
		->setSuffix($billing->getSuffix())
		->setCompany($billing->getCompany())
		->setStreet($billing->getStreet())
		->setCity($billing->getCity())
		->setCountry_id($billing->getCountryId())
		->setRegion($billing->getRegion())
		->setRegion_id($billing->getRegionId())
		->setPostcode($billing->getPostcode())
		->setTelephone($billing->getTelephone())
		->setFax($billing->getFax());
		$order->setBillingAddress($billingAddress);
		
		$shipping = $billing;// $customer->getDefaultShippingAddress();
		$shippingAddress = Mage::getModel('sales/order_address')
		->setStoreId($storeId)
		->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
		->setCustomerId($customer->getId())
		->setCustomerAddressId($customer->getDefaultShipping())
		->setCustomer_address_id($shipping->getEntityId())
		->setPrefix($shipping->getPrefix())
		->setFirstname($shipping->getFirstname())
		->setMiddlename($shipping->getMiddlename())
		->setLastname($shipping->getLastname())
		->setSuffix($shipping->getSuffix())
		->setCompany($shipping->getCompany())
		->setStreet($shipping->getStreet())
		->setCity($shipping->getCity())
		->setCountry_id($shipping->getCountryId())
		->setRegion($shipping->getRegion())
		->setRegion_id($shipping->getRegionId())
		->setPostcode($shipping->getPostcode())
		->setTelephone($shipping->getTelephone())
		->setFax($shipping->getFax());
		
		$order->setShippingAddress($shippingAddress)
		->setShipping_method('flatrate_flatrate');
		/*->setShippingDescription($this->getCarrierName('flatrate'));*/
		/*some error i am getting here need to solve further*/
		
		//you can set your payment method name here as per your need
		$orderPayment = Mage::getModel('sales/order_payment')
		->setStoreId($storeId)
		->setCustomerPaymentId(0)
		->setMethod('purchaseorder')
		->setPo_number(' – ');
		$order->setPayment($orderPayment);
		
		//$current_date = new DateTime(time());
		
		
		$now = Mage::getModel('core/date')->timestamp( time() + (7 * 24 * 60 * 60));
		
		$order->setCreatedAt($now);
		
		// let say, we have 2 products
		//check that your products exists
		//need to add code for configurable products if any
		$subTotal = 0;
		$products = array(
				'1510' => array(
						'qty' => 2
				),
				'1509' => array(
						'qty' => 1
				)
		);
		
		foreach ($products as $productId=>$product) {
			$_product = Mage::getModel('catalog/product')->load($productId);
			$rowTotal = $_product->getPrice() * $product['qty'];
			$orderItem = Mage::getModel('sales/order_item')
			->setStoreId($storeId)
			->setQuoteItemId(0)
			->setQuoteParentItemId(NULL)
			->setProductId($productId)
			->setProductType($_product->getTypeId())
			->setQtyBackordered(NULL)
			->setTotalQtyOrdered($product['rqty'])
			->setQtyOrdered($product['qty'])
			->setName($_product->getName())
			->setSku($_product->getSku())
			->setPrice($_product->getPrice())
			->setBasePrice($_product->getPrice())
			->setOriginalPrice($_product->getPrice())
			->setRowTotal($rowTotal)
			->setBaseRowTotal($rowTotal);
		
			$subTotal += $rowTotal;
			$order->addItem($orderItem);
		}
		
		$order->setSubtotal($subTotal)
		->setBaseSubtotal($subTotal)
		->setGrandTotal($subTotal)
		->setBaseGrandTotal($subTotal);
		
		$transaction->addObject($order);
		$transaction->addCommitCallback(array($order, 'place'));
		$transaction->addCommitCallback(array($order, 'save'));
		$transaction->save();
		
		
		
		
		
		
		echo "<br>-------Done---------<br>";
	}
	
	function processProduct($product) {
	
	}
	
	function processLinesToOrderArray() {
		
		$orderArray = array ();
		
		foreach ( $this->data as $line ) {
			
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
			
			$customer = array ();
			
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
			
			$orderArray [$email] ['first_name'] = $first_name;
			$orderArray [$email] ['last_name'] = $last_name;
			
			$orderArray [$email] ['orders'] [$old_order_id_a] ['first_name'] = $first_name;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['last_name'] = $last_name;
			
			$orderArray [$email] ['orders'] [$old_order_id_a] ['member_nu'] = $member_nu;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['status'] = $status;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['old_order_id_b'] = $old_order_id_b;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['order_datetime'] = $order_datetime;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['status'] = $status;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['member_nu'] = $member_nu;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_street'] = $bill_street;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_city'] = $bill_city;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_state'] = $bill_state;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_zip'] = $bill_zip;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_country'] = $bill_country;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['bill_phone'] = $bill_phone;
			$orderArray [$email] ['orders'] [$old_order_id_a] ['product'] [] = $product;
		}
		
		$this->orderArray = $orderArray;
	}
	
	function loadFile() {
		echo "Loading file ";
		if (($handle = fopen ( $this->filename, "r" )) !== FALSE) {
			
			$this->data = array ();
			
			while ( ($data = fgetcsv ( $handle, 1000, "," )) !== FALSE ) {
				$this->data [] = $data;
			}
			fclose ( $handle );
		
		}
		array_shift ( $this->data );
	
	}

}


