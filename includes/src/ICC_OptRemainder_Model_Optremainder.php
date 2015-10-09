<?php

class ICC_OptRemainder_Model_Optremainder extends Mage_Core_Model_Abstract
{
    /**
     * User Type
     */
    const PURCHASING_AGENT = 1;
    const NON_REGISTERED_USER = 2;
    
    protected function _construct(){

       $this->_init("optremainder/optremainder");

    }
    
    public function getOptOutFlag($orderId, $customerEmail) {
        $collection = Mage::getModel("optremainder/optremainder")->getCollection()
                ->addFieldToSelect('flag')
                ->addFieldToFilter("order_id", array('eq' => $orderId))
                ->addFieldToFilter("customer_email", array('eq' => $customerEmail));
        foreach ($collection as $coll) {
            return $coll->getFlag();
        }
        return 0;
    }

}
	 