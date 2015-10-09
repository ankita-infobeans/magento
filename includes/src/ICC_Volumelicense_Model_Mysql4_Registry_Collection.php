<?php

class ICC_Volumelicense_Model_Mysql4_Registry_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        $this->_init("volumelicense/registry");
    }

    public function getByUserId($uid) {
        $this->getSelect()->where("assign_customer_id = " . (int) $uid);
        return $this;
    }

    public function getByVolumelicenseAndUserId($sid, $uid) {
        $this->getSelect()->where('volumelicense_id = ' . (int) $sid . ' AND assign_customer_id = ' . (int) $uid. ' AND (assign_status = '.ICC_Volumelicense_Helper_Data::PENDING.' OR assign_status = '.ICC_Volumelicense_Helper_Data::ACTIVE.' )');
        return $this->getFirstItem();
    }

    public function getByVolumelicenseId($vid) {
        $this->getSelect()->where('id = ' . (int) $vid);
        return $this->getFirstItem();
    }

    public function getByVolumelicenseAndUserEmail($vid, $email) {
        $this->getSelect()->where('volumelicense_id = ' . (int) $vid . ' AND assign_customer_email = "'.$email.'" AND (assign_status = '.ICC_Volumelicense_Helper_Data::PENDING.' OR assign_status = '.ICC_Volumelicense_Helper_Data::ACTIVE.' )');
        return $this->getFirstItem();
    }

    public function getBySharedEmail($email) {
        $this->getSelect()->where('assign_customer_email = "' . $email . '" AND assign_status = "'.ICC_Volumelicense_Helper_Data::ACTIVE.'"');
        return $this;
    }

}
