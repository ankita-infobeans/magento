<?php

require_once '../app/Mage.php';

umask ( 0 );

Mage::app ( 'default' );

$order = new Gorilla_Greatplains_Model_Order();

//$orderIds = array(100146241);
$orderIds = array(100146249,100146248,100146246);

$gp_orders = $order->processOrders($orderIds);


//$order->updateOrders();

//print_r($gp_orders);

