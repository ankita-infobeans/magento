<?php

require_once '../app/Mage.php';

umask(0);

Mage::app('default');

$id		=	5; // get Customer Id

$customer = Mage::getModel('customer/customer')->load($id);

$transaction = Mage::getModel('core/resource_transaction');

$storeId = $customer->getStoreId();

$reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);

$order = Mage::getModel('sales/order')
->setIncrementId($reservedOrderId)
->setStoreId($storeId)
->setQuoteId(0);

//Set your store currency USD or any other

// set Customer data
$order->setCustomer_email($customer->getEmail())
->setCustomerFirstname($customer->getFirstname())
->setCustomerLastname($customer->getLastname())
->setCustomerGroupId($customer->getGroupId())
->setCustomer_is_guest(0)
->setCustomer($customer);

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