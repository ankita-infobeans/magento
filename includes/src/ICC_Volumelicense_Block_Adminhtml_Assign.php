<?php

class ICC_Volumelicense_Block_Adminhtml_Assign extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {

        $this->_controller = "adminhtml_assign";
        $this->_blockGroup = "volumelicense";
        $this->_headerText = Mage::helper("volumelicense")->__("Assign Manager");
        $this->_addButtonLabel = Mage::helper("volumelicense")->__("Add New Item");
        parent::__construct();
    }

}
