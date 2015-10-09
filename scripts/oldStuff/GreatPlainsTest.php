<?php

require_once '../../app/Mage.php';

umask(0);

Mage::app('default');









$gp = new Gorilla_Greatplains_Model_Soap();
$gp = new Gorilla_Greatplains_Model_Order();

$a = array(100146087,100146088);
$a = array(100146087);
$gp->processOrders($a);


die;







$o = new Gorilla_Greatplains_Model_Observer();
$o->processOrder();
exit;
$o->run2(905);


exit;

/*
$order = Mage::getModel('sales/order')->load(804);

 $order->_invoices = Mage::getResourceModel('sales/order_invoice_collection')
                ->setOrderFilter($order->getId());

            if ($order->getId()) {
                foreach ($order->_invoices as $invoice) {
                    $invoice->setOrder($order);
                    $invoice_id = $invoice->getIncrementId();
                   // print_r($invoice->debug());
                }
            }

$quote = Mage::getModel('sales/quote')->load(431);

print_r($quote->debug());


exit;

*/

$gpsku = "3000L12";
/*
$collection = Mage::getModel ( 'catalog/product' )->getCollection ();
$collection->addAttributeToSelect ( 'gp_sku' );

// filter for products whose orig_price is greater than (gt) 100
$collection->addFieldToFilter ( array (array ('attribute' => 'gp_sku', 'eq' => $gpsku ) ) );
echo $gpsku;
if ($collection->getSize () > 0) {
	echo $gpsku;
	// echo "size is ".$collection->getSize ()."\n";
	// echo " collection : ";print_r($collection->getData());
	$firstproduct = $collection->getFirstItem ();
	$product = Mage::getModel ( 'catalog/product' );
		
	// echo " product : ";print_r($firstproduct->getData());
	// die;
	$sk = $firstproduct->getSku ();
		
	$productId = $product->getIdBySku ( $sk );
	// echo $productId."\n";
	print_r( $product->load ( $productId ));

	
}




exit;
*/

$gp = new Gorilla_Greatplains_Model_Soap();
//$return = $gp->getProductBySku('8705P09');
//3000L09
//3000S09


// 8855P27B


$return = $gp->getProductBySku('3000L12');
print_r($return);

exit;

//$return = $gp->getOfflineOrderSummary("5156005");
echo "----------";
$return = $gp->getProductBySku('3000L12');

print_r($return);
exit;


echo "-------------get product by sku 3000L09---------------\n";
$return = $gp->getProductBySku('3000L09');
print_r($return);



exit;


echo "-------------get product by sku 3000S09---------------\n";
$return = $gp->getProductBySku('3000S09');
print_r($return);
$a = array('5897L11','C99','0001TS03','0002PR00');




echo "-------------------- GET PRODUCT LISTING ------------------- \n";


$a = array('3000S09','3000L09');
print_r($a);

$return = $gp->getProductList($a);


$return = $gp->getOfflineOrderSummary('0111693');

//$return = $gp->getOrderDetail('5758069');

//$data = array('5758069','0828807','0828808','5787034');
//$return = $gp->updateOrder($data);

print_r($return);
