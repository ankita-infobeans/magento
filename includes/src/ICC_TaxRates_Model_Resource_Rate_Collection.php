<?php

class ICC_TaxRates_Model_Resource_Rate_Collection extends Mage_Tax_Model_Resource_Calculation_Rate_Collection
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('taxrates/rate');
    }
}