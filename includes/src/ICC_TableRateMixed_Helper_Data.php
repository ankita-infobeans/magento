<?php

class ICC_TableRateMixed_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Retrieve fixed international shipping amount
     *
     */
    public function getFixedInternationalShippingAmount()
    {
        $config = Mage::getStoreConfig('icc_fixed_intl_shipping/intl_fixed_shiping_conf/intl_fixed_shiping',Mage::app()->getStore());
        return $config;
    }
}