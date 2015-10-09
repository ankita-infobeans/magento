<?php

include('../app/Mage.php');
Mage::App('default'); //might be "default"


$order = Mage::getModel('sales/order') -> loadByIncrementId('100000863');

     $m_items = $order -> getAllItems();
        //print_r($m_items);
        foreach ($m_items as $item) {
            print_R($item->getData());        
        }
        
            