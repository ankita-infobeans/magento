<?php
class Gorilla_Greatplains_Block_Adminhtml_Modelname_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
	public function __construct() {
		parent::__construct ();
		$this->setId ( "modelname_tabs" );
		$this->setDestElementId ( "edit_form" );
		$this->setTitle ( Mage::helper ( "greatplains" )->__ ( "Item Information" ) );
	}
	protected function _beforeToHtml() {
		$this->addTab ( "form_section", array ("label" => Mage::helper ( "greatplains" )->__ ( "Item Information" ), "title" => Mage::helper ( "greatplains" )->__ ( "Item Information" ), "content" => $this->getLayout ()->createBlock ( "greatplains/adminhtml_modelname_edit_tab_form" )->toHtml () ) );
		return parent::_beforeToHtml ();
	}

}
