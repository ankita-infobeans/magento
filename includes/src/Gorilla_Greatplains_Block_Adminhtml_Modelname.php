<?php

class Gorilla_Greatplains_Block_Adminhtml_Modelname extends Mage_Adminhtml_Block_Widget_Grid_Container {
	
	public function __construct() {
		
		$this->_controller = "adminhtml_modelname";
		$this->_blockGroup = "greatplains";
		$this->_headerText = Mage::helper ( "greatplains" )->__ ( "Modelname Manager" );
		$this->_addButtonLabel = Mage::helper ( "greatplains" )->__ ( "Add New Item" );
		parent::__construct ();
	
	}

}