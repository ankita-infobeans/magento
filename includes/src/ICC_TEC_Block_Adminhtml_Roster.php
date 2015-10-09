<?php

class ICC_TEC_Block_Adminhtml_Roster extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'icc_tec';
        $this->_controller = 'adminhtml_roster';
        $product_id = Mage::app()->getRequest()->getParam('id');
        $product = Mage::getModel('catalog/product')->load($product_id);
        $this->_headerText = $this->__('Events Roster for ' . $product->getName());
        parent::__construct();
        $this->_removeButton('add');
    }
    
}