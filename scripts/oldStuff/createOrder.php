<?php

$proxy = new SoapClient ( 'http://local.iccsafe.org/index.php/api/soap/?wsdl' );

$sessionId = $proxy->login ( 'order1234', 'order1234' );

// Create a quote, get quote identifier
 echo $sessionId;
$shoppingCartId = $proxy->call ( $sessionId, 'cart.create', array ('default' ) );

echo $shoppingCartId."\n";

// Set customer, for example guest

$customerAsGuest = array (

"firstname" => "Firstname", 

"lastname" => "testLastName", 

"email" => "testEmail@gmail.com", 

"website_id" => "0", 

"store_id" => "0", 

"mode" => "guest" );



$resultCustomerSet = $proxy->call ( $sessionId, "cart_customer.set", array ($shoppingCartId, $customerAsGuest ) );

// Set customer addresses, for example guest’s addresses

$arrAddresses = array (

array ("mode" => "shipping", 
		"firstname" => "testFirstname", 
		"lastname" => "testLastname", 
		"company" => "testCompany", 
		"street" => "testStreet", 
		"city" => "testCity", 
		"region" => "testRegion", 
		"postcode" => "testPostcode", "country_id" => "id", "telephone" => "0123456789", "fax" => "0123456789", "is_default_shipping" => 0, 
		"is_default_billing" => 0 ), array ("mode" => "billing", "firstname" => "testFirstname", "lastname" => "testLastname", 
				"company" => "testCompany", "street" => "testStreet", "city" => "testCity", "region" => "testRegion", 
				"postcode" => "testPostcode", "country_id" => "id", "telephone" => "0123456789", "fax" => "0123456789", 
				"is_default_shipping" => 0, "is_default_billing" => 0 ) );

$resultCustomerAddresses = $proxy->call ( $sessionId, "cart_customer.addresses", array ($shoppingCartId, $arrAddresses ) );

// add products into shopping cart
// array( “sku" => “LV1234", “quantity" => 1, ‘special_price’=>’110.00’, ),
// array( “product_id" => “103", “qty" => 1,"super_attribute"
// =>array("150"=>"74") ),
// array("sku" => “ecco","quantity" => 1, “super_attribute" =>array("150"=>"74")
// )
$arrProducts = array (

array ("sku" => "1510", "quantity" => 1, "special_price" => 90.00 ), array ("sku" => "1511", "quantity" => 1, "special_price" => "75.00" ) );

//$resultCartProductAdd = $proxy->call ( $sessionId, "cart_product.add", array ($shoppingCartId, $arrProducts ) );

$shoppingCartProducts = $proxy->call ( $sessionId, "cart_product.list", array ($shoppingCartId ) );

// get list of shipping methods

$resultShippingMethods = $proxy->call ( $sessionId, "cart_shipping.list", array ($shoppingCartId ) );
var_dump ( $resultShippingMethods );
$randShippingMethodIndex = rand ( 1, count ( $resultShippingMethods ) );

$shippingMethod = $resultShippingMethods [0] ["code"];

$resultShippingMethod = $proxy->call ( $sessionId, "cart_shipping.method", array ($shoppingCartId, $shippingMethod ) );

// get list of payment methods

$resultPaymentMethods = $proxy->call ( $sessionId, "cart_payment.list", array ($shoppingCartId ) );



 print_r($resultPaymentMethods);

 $stuff = array("po_number"=>"234234","method"=>"checkmo");
 
 $paymentMethod = array(
 		'method' => 'paymentech',
 		'cc_exp_year' => 2013,
 		'cc_exp_month' => 12,
 		'cc_type' => 'VI',
 		'cc_number' => '4111111111111111'
 );
 
 
$resultPaymentMethod = $proxy->call ( $sessionId, "cart_payment.method", array ($shoppingCartId, $paymentMethod) );

$shoppingCartTotals = $proxy->call ( $sessionId, "cart.totals", array ($shoppingCartId ) );

$shoppingCartId = ( string ) $shoppingCartId;
 var_dump( $shoppingCartId);

// get full information about shopping cart

$shoppingCartInfo = $proxy->call ( $sessionId, "cart.info", array ($shoppingCartId ) );

 print_r( $shoppingCartInfo );




// CREATE ORDER

$resultOrderCreation = $proxy->call ( $sessionId, "cart.order", array ($shoppingCartId, null, $licenseForOrderCreation ) );
if ($resultOrderCreation) {
	echo "Order Created Successfully";
} 