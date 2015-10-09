<?php

class ICC_Premiumaccess_Block_Adminhtml_Assign extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {

        $this->_controller = "adminhtml_assign";
        $this->_blockGroup = "icc_premiumaccess";
        $this->_headerText = Mage::helper("icc_premiumaccess")->__("Assign Manager");
        $this->_addButtonLabel = Mage::helper("icc_premiumaccess")->__("Add New Item");
        parent::__construct();
    }

}
