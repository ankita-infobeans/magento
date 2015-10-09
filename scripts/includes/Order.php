<?php

require_once 'includes/File.php';
require_once 'includes/Customer.php';

class Order {
	
	private $_data;
	function __construct($data) {
	
		$this->_data = $data;
		//echo "<pre>";
		//print_r($this->_data);
		//echo "</pre>";

	}
	
	
	
	function getCustomer($email)
	{
		$customer = new Customer();
		return $customer->getCustomerByEmail($email);
	}
	
	function createOrder()
	{
		print_r($this->_data);
		
		$customer = $this->getCustomer($this->_data['email']);
		
		if(!$customer)
		{
			//echo "error";
			return false;
			
			
		}
		$transaction = Mage::getModel('core/resource_transaction');
		
		$storeId = 1;//$customer->getStoreId();
		
		$reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);
		
		$order = Mage::getModel('sales/order')->setIncrementId($reservedOrderId)->setStoreId($storeId)->setQuoteId(0);
		
		//$customer = new Customer($this->_data['email']);
	
		//echo"--------------------------\n";
		//print_r($customer);
		
		// set Customer data
		$order->setCustomer_email($this->_data['email'])->setCustomerFirstname($customer->getFirstName())
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
		
		/*
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
		*/
		
		//$order->setShippingAddress($shippingAddress)
		//	->setShipping_method('flatrate_flatrate');
		/*->setShippingDescription($this->getCarrierName('flatrate'));*/
		/*some error i am getting here need to solve further*/
		
		//you can set your payment method name here as per your need
		
		$orderPayment = Mage::getModel('sales/order_payment')
		->setStoreId($storeId)
		->setCustomerPaymentId(0)
		->setMethod('checkmo');
		
		$order->setPayment($orderPayment);
		
		//print_r($order);
		
		$order->setOldOrderIdB($this->_data['old_order_id_b']);
		$order->setOldOrderIdA($this->_data['old_order_id_a']);
		
		$orderdate = Mage::getModel('core/date')
		   ->timestamp( strtotime($this->_data['order_datetime']));
		
		$order->setCreatedAt($orderdate);

		$subTotal = 0;

		$products = $this->_data['products'];
		
		
		
		foreach ($products as $product) {
			
			//print_r($product);
			
			
			//continue;
			
			//$_product = $this->getProduct($product['product_sku']);
			$_product = $this->getProduct("8740P09_PD-W-IC-P-2009-000003");
			$rowTotal = $_product->getPrice() * $product['product_qty'];
			$orderItem = Mage::getModel('sales/order_item')
			->setStoreId($storeId)
			->setQuoteItemId(0)
			->setQuoteParentItemId(NULL)
			->setProductId($_product->getSku())
			->setProductType($_product->getTypeId())
			->setQtyBackordered(NULL)
			->setTotalQtyOrdered($product['product_qty'])
			->setQtyOrdered($product['product_qty'])
			->setName($_product->getName())
			->setSku($_product->getSku())
			->setPrice($_product->getPrice())
			->setBasePrice($_product->getPrice())
			->setOriginalPrice($_product->getPrice())
			->setRowTotal($rowTotal)
			->setBaseRowTotal($rowTotal);
		
			$subTotal += $rowTotal;
			$order->addItem($orderItem);
			$this->createProductStuff($orderItem);
		}
		
		$order->setSubtotal($subTotal)
		->setBaseSubtotal($subTotal)
		->setGrandTotal($subTotal)
		->setBaseGrandTotal($subTotal);
		
		
		//print_r($order->debug());
		
		$transaction->addObject($order);
		$transaction->addCommitCallback(array($order, 'place'));
		$transaction->addCommitCallback(array($order, 'save'));
		$transaction->save();
		echo "saved ! \n";
		
	}
	
	function createProductStuff($orderitem)
	{
		echo" ----------------- Order item ------------------------\n";
		print_r($orderitem->debug());
		echo" -----------------------------------------------------\n\n\n\n\n\n\n\n";
	}
	
	function getProduct($id)
	{
		
		
		return Mage::getModel('catalog/product')->load($productId);
	}
}

?>