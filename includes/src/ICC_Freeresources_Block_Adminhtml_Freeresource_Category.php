<?php
/**
 * Free Resource category admin block
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Block_Adminhtml_Freeresource_Category
    extends Mage_Adminhtml_Block_Widget_Grid_Container {
    /**
     * constructor
     * @access public
     * @return void
     */
    public function __construct(){
        $this->_controller = 'adminhtml_freeresource_category';
        $this->_blockGroup         = 'icc_freeresources';
        parent::__construct();
        $this->_headerText         = Mage::helper('icc_freeresources')->__('Manage Category');
        $this->_updateButton('add', 'label', Mage::helper('icc_freeresources')->__('Add Category'));
    }
}