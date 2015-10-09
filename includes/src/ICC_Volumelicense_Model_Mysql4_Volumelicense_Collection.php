<?php

class ICC_Volumelicense_Model_Mysql4_Volumelicense_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        $this->_init("volumelicense/volumelicense");
    }

    public function getAll() {
        $this->getSelect();
        return $this;
    }

    public function getByOrderLineItemId($id) {
        $this->getSelect()->where('order_item_id = ' . (int) $id);
        return $this;
    }

    public function getRegisteredByCustomerId($id) {
        $this->getSelect()->where('customer_id = ' . (int) $id . ' and ' . ' status = '.ICC_Volumelicense_Helper_Data::ACTIVE);
        return $this;
    }

    public function getByUserId($uid) {
        $this->getSelect()->where("customer_id = " . (int) $uid);
        return $this;
    }

    public function getByVolumeId($id) {
        $this->getSelect()->where('id = ' . (int) $id);
        return $this;
    }

    public function getByOrderId($id) {
        $this->getSelect()->where('order_item_id = ' . (int) $id);
        return $this;
    }
    public function getNotifySubscription(){
        $this->getSelect()->where('max_register > registered_count');
        return $this;
    }

}
