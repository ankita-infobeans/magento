<?php

class ICC_Ecodes_Model_Premiumusers extends Mage_Core_Model_Abstract {
	
    protected function _construct() {
        $this->_init('ecodes/premiumusers');
    }

    public function getName() {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }
}
