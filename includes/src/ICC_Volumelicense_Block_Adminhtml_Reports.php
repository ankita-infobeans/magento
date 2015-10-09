<?php


class ICC_Volumelicense_Block_Adminhtml_Reports extends Mage_Adminhtml_Block_Widget_Grid_Container{

	public function __construct()
	{

	$this->_controller = "adminhtml_reports";
	$this->_blockGroup = "volumelicense";
	$this->_headerText = Mage::helper("volumelicense")->__("Volume License Report");
	$this->_addButtonLabel = Mage::helper("volumelicense")->__("Add New Item");
	parent::__construct();
	$this->_removeButton('add');
	}

}