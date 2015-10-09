<?php
class ICC_Ecodes_Model_Mysql4_Premiumsubusers extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct() {
        $this->_init('ecodes/premiumsubusers', 'id');
    }
}