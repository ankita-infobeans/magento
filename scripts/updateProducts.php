<?php





require_once '../app/Mage.php';

umask ( 0 );

Mage::app ( 'default' );

$products = new Gorilla_Greatplains_Model_Product();
//$products->UpdateProductData();
//$products->initProductUpdate();

//$products->attId= 13;
$products->UpdateProductData();

// = array('9141S');
//roducts->processSkus($a);
