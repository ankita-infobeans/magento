<?php
include('../app/Mage.php');
Mage::App('default'); //might be "default"

require_once 'includes/File.php';
require_once 'includes/Order.php';
require_once 'includes/Customer.php';




$filename = "ecodes_data_history_2.csv";

$file = new File();

$file->load($filename);

$file->process();
echo "done processing\n";
//$order = $file->getNextOrder();
//exit;

while($customerData = $file->getNextCustomer())
{
	$c = new Customer();
	$customer = $c->setData($customerData);

	
	if($customer)
	{
		//print_r($customer->getCustomer()->debug());
	}
	
}




while($orderData = $file->getNextOrder())
{
	$order = new Order($orderData);
	$order->createOrder();
	

}
