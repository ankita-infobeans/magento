<?php

class ICC_Orderfiltering_Model_Config extends Mage_Core_Model_Config_Base {

    public function getTypes() {
        return Mage::helper('icc_orderfiltering')->getTypes();
    }

}