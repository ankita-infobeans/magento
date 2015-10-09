<?php

class Gorilla_Queue_Block_Adminhtml_Queue extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected function _construct()
    {
        $this->_blockGroup = 'gorilla_queue';
        $this->_controller = 'adminhtml_queue';
        $this->_headerText = $this->__('List Queue Items');

        parent::_construct();
    }
    
}