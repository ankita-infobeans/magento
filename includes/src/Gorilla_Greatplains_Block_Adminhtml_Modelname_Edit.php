<?php

class Gorilla_Greatplains_Block_Adminhtml_Modelname_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {
	public function __construct() {
		
		parent::__construct ();
		$this->_objectId = "tablename_id";
		$this->_blockGroup = "greatplains";
		$this->_controller = "adminhtml_modelname";
		$this->_updateButton ( "save", "label", Mage::helper ( "greatplains" )->__ ( "Save Item" ) );
		$this->_updateButton ( "delete", "label", Mage::helper ( "greatplains" )->__ ( "Delete Item" ) );
		
		$this->_addButton ( "saveandcontinue", array ("label" => Mage::helper ( "greatplains" )->__ ( "Save And Continue Edit" ), "onclick" => "saveAndContinueEdit()", "class" => "save" ), - 100 );
		
		$this->_formScripts [] = "

							function saveAndContinueEdit(){
								editForm.submit($('edit_form').action+'back/edit/');
							}
						";
	}
	
	public function getHeaderText() {
		if (Mage::registry ( "greatplains_data" ) && Mage::registry ( "greatplains_data" )->getId ()) {
			
			return Mage::helper ( "greatplains" )->__ ( "Edit Item '%s'", $this->htmlEscape ( Mage::registry ( "greatplains_data" )->getName () ) );
		
		} else {
			
			return Mage::helper ( "greatplains" )->__ ( "Add Item" );
		
		}
	}
}