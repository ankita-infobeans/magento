<?php

class ICC_TEC_Block_Adminhtml_Event extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'icc_tec';
        $this->_controller = 'adminhtml_event';
        $this->_headerText = $this->__('List Event Products');
        parent::__construct();
        $this->_removeButton('add');
    }
    
}