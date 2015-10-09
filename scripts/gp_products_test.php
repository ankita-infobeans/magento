<?php
ini_set("memory_limit", "2048M");
require_once '../app/Mage.php';
require_once 'includes/Customer.php';

umask(0);

//Mage::app('default');
Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));


$gp = new Gorilla_Greatplains_Model_Product();

$gp->initProductUpdate();
/*
$a = array('4081S12',
    '7054S12',
"9456S2",
"3750S12",
"300CB12",
"5703L04",
"9101S7");
*/

$a = array(
'300CB12',  // -28
'0717S',    // -18 
'300CDB12', // -13
'4881S12',  // -6
'3810S12',  // -5
'9435H10',  // -4
'1046S09',  // -3
'3751S12',  // -2
'5520L07',  // -2
'9401S09',  // -2
'303S12OH', // -1 
'1013S4'    // -1
    );


$gp->processSkus($a);

