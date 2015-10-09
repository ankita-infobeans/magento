<?php
class Gorilla_Greatplains_Block_Adminhtml_Modelname_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {
	protected function _prepareForm() {
		
		$form = new Varien_Data_Form ();
		$this->setForm ( $form );
		$fieldset = $form->addFieldset ( "greatplains_form", array ("legend" => Mage::helper ( "greatplains" )->__ ( "Item information" ) ) );
		
		$fieldset->addField ( "name", "text", array ("label" => Mage::helper ( "greatplains" )->__ ( "Greatplains Name" ), "class" => "required-entry", "required" => true, "name" => "name" ) );
		
		if (Mage::getSingleton ( "adminhtml/session" )->getGreatplainsData ()) {
			$form->setValues ( Mage::getSingleton ( "adminhtml/session" )->getGreatplainsData () );
			Mage::getSingleton ( "adminhtml/session" )->setGreatplainsData ( null );
		} elseif (Mage::registry ( "greatplains_data" )) {
			$form->setValues ( Mage::registry ( "greatplains_data" )->getData () );
		}
		return parent::_prepareForm ();
	}
}
