<?php
class ICC_Ecodes_Model_Mysql4_Premiumsubs_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        $this->_init('ecodes/premiumsubs', 'id');
    }
	
    public function getAll() {
            $this->getSelect();		
            return $this;
    }

    public function getByOrderLineItemId($id) {
            $this->getSelect()->where('order_item_id = ' . (int)$id);
            return $this;
    }

    public function getRegisteredByCustomerId($id) {
            $this->getSelect()->where('registered = 1 and customer_id = ' . (int)$id);
            return $this;
    }

    public function getBySku($sku) {
            $this->getSelect()->where('sku = "' . $sku.'"');
            return $this;
    }

    public function getByExpiration() {
            $this->getSelect()->where('expiration >= "' . date('Y-m-d H:i:s').'"');
            return $this;
    }
}