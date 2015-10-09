<?php

class ICC_Premiumaccess_Model_Registry extends Mage_Core_Model_Abstract
{
    protected function _construct(){

       $this->_init("icc_premiumaccess/registry");

    }
    /**
     * This method used to return collection of premium access registry for given subscription id.
     * @param type $subscription_id
     * @return type
     */
    public function loadBySubscriptionId($subscription_id){
        $collection = Mage::getModel("icc_premiumaccess/registry")->getCollection()->addFieldToFilter('subscription_id', $subscription_id);
        $collection->addFieldToFilter('status', array('neq'=>  ICC_Premiumaccess_Helper_Data::DELETE));
        return $collection;
        
    }

}
	 