<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class ICC_CatalogSearch_Helper_Data extends Mage_CatalogSearch_Helper_Data{
    /**
     * checks if event type of product exists in search and returns boolean value
     * @return array
     */
    public function getDiffValuesForDate(){
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $queryId = Mage::helper('catalogSearch')->getQuery()->getQueryId();
        $resultIds = $readConnection->fetchAll("select product_id from catalogsearch_result where query_id = '".$queryId."'");
        $resultIds = array_column($resultIds, 'product_id');
        //Get all product ids of event category
        $products = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addFieldToFilter('status', 1)
                    ->addFieldToFilter('attribute_set_id', '11'); //attribute set id of event
       $pids = array_column($products->getData('entity_id'), 'entity_id');
       $diff = array_intersect($resultIds, $pids);
       unset($resultIds); unset($pids);
       if(!empty($diff)){
           return true;
       }else{
            return false;
       }
      
    }
    
}
