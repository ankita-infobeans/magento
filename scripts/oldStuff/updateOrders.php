<?php
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);


require_once '../../app/Mage.php';

umask(0);

Mage::app('default');

//echo "foo";
//$product = Mage::getModel('catalog/product')->loadByAttribute('gp_sku','8705S12A'); 

//print_r($product->debug());
//die;
$proc = new Gorilla_Greatplains_Model_Order();

$a = array('100069194');
$proc->processOrders($a);

//$proc->updateOrders();







/*
 * #100069189  2 day—bill—partial ship (2 ordered, 1 shipped)
- Magento shows this as partially shipped (correct).  Ryan: Can you confirm that it looks right?
- Problem: Tami did not get a shipping notification
#100069190—ground –cc-partial (2 ordered, 1 shipped)
- Problem: Magento shows this as complete, even though it was partially shipped
# 100069191--2-day—cc shipped (2 ordered, 2 shipped)
- Problem: Magento still shows this as processing
#100069192-ground --bill --shipped  (2 ordered, 2 shipped)
- Problem: Magento still shows this as processing
 */

exit;


