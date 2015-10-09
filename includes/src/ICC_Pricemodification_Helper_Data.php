<?php

class ICC_Pricemodification_Helper_Data extends Mage_Paygate_Helper_Data {
   
    const SPECIAL_PRICE = "special";
    const NEW_PRODUCT = "new";

    public function setSession($key, $data) {
        $session = Mage::getSingleton("core/session", array("name" => "frontend"));
        $session->setData($key, $data);
    }

    public function getSession($key) {
        $session = Mage::getSingleton("core/session", array("name" => "frontend"));
        return $session->getData($key);
    }

    /*
     * Check to see if product is either flagged new or special price
     */
    public function isPriceSpecial(Mage_Catalog_Model_Product $product) {

        /*
         * Check if sale price is activated and if so if sale price is LESS than normal price
         * I.E., customer might be a Member, so the member price might be less than sale price
         */
        $originalPrice = $product->getPrice();
        $finalPrice = $product->getFinalPrice();

        if ($finalPrice < $originalPrice) {
            return self::SPECIAL_PRICE;
        }
        
        /*
         * check if product is new
         */
        $current_date = time(); // compare date
        $from_date = $product->getData('news_from_date'); // begin date
        $to_date = $product->getData('news_to_date'); // end date

        
        if ($this->isDateBetween($current_date, $from_date, $to_date)) {
            return self::NEW_PRODUCT;
        }

        return false;
    }
    
    
    private function isDateBetween($now=null, $start=null, $end=null)
    {
        if ($now==null) {
            $now = time();
        }
        
        $start = strtotime($start);
        $end = strtotime($end);
        
        // neither start or end are set, so technically we are not between them
        if ($start == false && $end == false) {
            return false;
            
        // start isn't set, but end is
        } elseif ($start == false) {
            if ($now <= $end) {
                return true;
            } else {
                return false;
            }
            
        // end isn't set but start is
        } elseif ($end == false) {
            if ($now >= $start) {
                return true;
            } else {
                return false;
            }
            
        // start and end are both set
        } else {
            if ($now >= $start && $now <= $end) {
                return true;
            }
        }
        
        return false;
            
    }

}