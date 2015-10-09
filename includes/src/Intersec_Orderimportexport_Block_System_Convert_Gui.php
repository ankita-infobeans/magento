<?php

class Intersec_Orderimportexport_Block_System_Convert_Gui extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'system_convert_gui';
        $this->_blockGroup = 'intersec_orderimportexport';
        
        $this->_headerText = Mage::helper('intersec_orderimportexport')->__('Profiles');
        $this->_addButtonLabel = Mage::helper('intersec_orderimportexport')->__('Add New Profile');

        parent::__construct();
    }
}