<?php

require_once '../../app/Mage.php';



umask ( 0 );

Mage::app ( 'default' );//->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);


$obs = new Gorilla_Greatplains_Model_Observer();
$obs->processOrder()



?>