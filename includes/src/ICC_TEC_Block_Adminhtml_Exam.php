<?php

class ICC_TEC_Block_Adminhtml_Exam extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'icc_tec';
        $this->_controller = 'adminhtml_exam';
        $this->_headerText = $this->__('List Exam Roster');

        parent::__construct();
        $this->_removeButton('add');
    }
    
}