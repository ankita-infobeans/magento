<?php

class ICC_Orderfiltering_Helper_Data extends Mage_Paygate_Helper_Data {

    protected $types;
    
    public function getTypes($idsAsKeys=false) {
        
        
       
        
        $which = ($idsAsKeys) ? 'ids' : 'labels';
        
        if (!isset($this->types[$which])) {
            $types = array();
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'item_type');
            foreach ($attribute->getSource()->getAllOptions(false,true) as $option) {
                if (!$idsAsKeys) {
                    $types[$option['label']] = $option['label'];
                } else {
                    $types[$option['value']] = $option['label'];
                }
            }
            $this->types[$which] = $types;
        }
        return $this->types[$which];
        
//        $types = array
//            (
//            "premium" => "eCodes Premium",
//            "downloadable" => "Downloadable eCodes",
//            "membership" => "Membership",
//            "trainingandeducation" => "Training + Education",
//            "certification" => "Certification",
//        );
    }

}