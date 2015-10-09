<?php

class ICC_TaxRates_Model_Rate extends Mage_Tax_Model_Calculation_Rate
{
    protected $checkIsInRule = false;
    
    /**
     * Varien model constructor
     */
    protected function _construct()
    {   
        $this->_init('taxrates/rate');
    }

    
//    public function getCollection()
//    {
//        return parent::getCollection();
//    }
    
    public function setCheckIsInRule($bool)
    {
        $this->checkIsInRule = $bool;
    }
    
    /**
     * Overriding parent's _isInRule since the importer deletes all[1] rules first
     * and then all[1] rates.
     * This halves the number of SQL calls per delete of a rate.
     * 
     * [1] "all" = all rates and rules that were not just imported.
     * 
     * @return bool 
     */
    protected function _isInRule()
    {
        if ($this->checkIsInRule) {
            return parent::_isInRule();
        }
        return false;
    }
}