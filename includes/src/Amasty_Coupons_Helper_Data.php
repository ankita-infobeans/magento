<?php
/**
* @author Amasty Team
* @copyright Amasty
* @package Amasty_Coupons
*/
class Amasty_Coupons_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function arrayDepth($array)
    {
       foreach ($array['conditions'] as $key => $node) {             
            if (array_key_exists('conditions', $node)) {
               $r = $this->arrayDepth($node);
               if ($r !== null) {
                   return $r;
               }
           }  return $node;        
        } 
        return null;
    }
}