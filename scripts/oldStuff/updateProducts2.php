<?php

require_once '../../app/Mage.php';



umask ( 0 );

Mage::app ( 'default' );//->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
echo "1\n";
/*
[attribute_set_id] => 1[attribute_set_name] => Default
     
            [attribute_set_id] => 2[attribute_set_name] => Default
       
        ([attribute_set_id] => 3 [attribute_set_name] => Default
       
            [attribute_set_id] => 4[attribute_set_name] => Default
       
            [attribute_set_id] => 5[attribute_set_name] => Default
        
            [attribute_set_id] => 6[attribute_set_name] => Default
       
            [attribute_set_id] => 7[attribute_set_name] => Default
       
            [attribute_set_id] => 8[attribute_set_name] => Default
       
            [attribute_set_id] => 9[attribute_set_name] => Default
       
            [attribute_set_id] => 10[attribute_set_name] => Clothing
       
            [attribute_set_id] => 11[attribute_set_name] => Event
        
            [attribute_set_id] => 12[attribute_set_name] => Exam
        
            [attribute_set_id] => 13[attribute_set_name] => Book
        
            [attribute_set_id] => 14[attribute_set_name] => eCode Premium Subscription
       
            [attribute_set_id] => 15 [attribute_set_name] => Downloadable
        
            [attribute_set_id] => 16[attribute_set_name] => Grouped Configurable
       
            [attribute_set_id] => 17[attribute_set_name] => CD-ROM
        

*/
//$skus = array("8700S00J","8705S12A");
//IC-P-2012-000019-1u1y = 8705S12A


//$skus = array('3010S12','870P12');
//$product = Mage::getModel('catalog/product')->load(28205);//loadByAttribute('gp_sku',"8700S00J");


//echo "2\n";
//$product->setPrice(1000.00);

//print_r($product->debug());

//$product->save();
//die;
$p = new Gorilla_Greatplains_Model_Product();

//echo "3\n";
//$p->skus = $skus;
//echo "4\n";

//$p->attId = "15";
$p->UpdateProductData();




print_r($p->getOnesNotFound());
exit;
