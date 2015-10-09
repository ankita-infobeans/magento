<?php

class ICC_Quickorder_Model_Quickorder extends Mage_Core_Model_Abstract{
    
    public function _construct()
    {
        $this->_init('quickorder/quickorder');
    }
    public function getQuickorderItems()
    {
         return Mage::helper('quickorder')->getQuickorderItems();
    }
    public function getCustomerQuickorders($customer_id)
    {
         $collection = $this->getCollection()
                ->addFilter('customer_id', $customer_id)
                ->load();

     
        return $collection;
    }
    public function resetCustomerQuickorders($customer_id)
    {
        return $this;
    }
    
    public function removequickOrderItem($id)
    {
        
         return $this;
    }
    public function changeQuantity($id,$qty)
    {
        
         return $this;
    }
}