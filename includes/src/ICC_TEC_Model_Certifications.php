<?php

class ICC_TEC_Model_Certifications extends  Mage_Core_Model_Abstract
{
    public function disableExpiredCertifications()
    {        
        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID)); // set admin environment
        
        $attribute_collection = Mage::getModel('eav/entity_attribute_set')
                        ->getCollection()
                        ->addFieldToFilter('attribute_set_name', array('exam'));
        $attribute_ids = array();
        
        foreach($attribute_collection as $attr )
        {
            $attribute_ids[] = $attr->getId();
        }
        $prods = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('attribute_set_id', array('in' => implode(', ', $attribute_ids)) );
        $today = date('Ymd');
        foreach($prods as $prod)
        {
            $optionCollection = $prod->getProductOptionsCollection();
            foreach($optionCollection as $option) 
            {
                //if($option->getSku() == 'date') // always returns NULL, added following strings by ticket Ticket#2014012710000591
                if(strcasecmp($option->getTitle(), 'date') == 0 && ($option->getSku() == 'date'  ||  $option->getSku() == null))
                {
                    foreach($option->getValues() as $val)
                    {
                        $dateVal = $val->getData('title');
                        if (preg_match('/^[\d]{1,2}\/[\d]{1,2}\/[\d]{4}$/', $dateVal)){
                            $compareableDateVal = date('Ymd', strtotime($dateVal . ' -43 day') );
                            if( $today > $compareableDateVal )
                            {
                                try{
                                    $val->delete();
                                } catch(Exception $e) {
                                    Mage::logException($e);
                                }
                            }
                        }
                    } // end foreach values
                } // end if
            } // end foreach options collection
        } // end foreach product
        
        
        
        $attribute_collection = Mage::getModel('eav/entity_attribute_set')
                        ->getCollection()
                        ->addFieldToFilter('attribute_set_name', array('event'));
        $attribute_ids = array();
        foreach($attribute_collection as $attr )
        {
            $attribute_ids[] = $attr->getId();
        }
        $prods = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addFieldToFilter('status', '1') // 1 is for enabled statuses
                    ->addFieldToFilter('attribute_set_id', array('in' => implode(', ', $attribute_ids)) )
                    ->addFieldToFilter('purchase_deadline', array('lteq' => date('Y-m-d')) );
        foreach($prods as $prod)
        {
            $prod->setStatus(2); // 2 is for disabled
            $prod->save();
        }
    }
}
