<?php
class ICC_Ecodes_Model_Mysql4_Premiumusers extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct() {
        $this->_init('ecodes/premiumusers', 'id');
    }
}