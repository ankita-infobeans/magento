<?php

class ICC_Premiumaccess_Block_Adminhtml_Premiumuser_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'icc_premiumaccess';
        $this->_controller = 'adminhtml_premiumuser';
        
        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Save Customer'));
        $this->_updateButton('delete', 'label', Mage::helper('adminhtml')->__('Delete Customer'));
        
        
	/*	
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);
        * 
        */

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('ecodes_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'ecodes_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'ecodes_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('premiumuser_data') && Mage::registry('premiumuser_data')->getId() ) {
            return Mage::helper('icc_premiumaccess')->__("Edit Customer '%s'", $this->htmlEscape(Mage::registry('ecodes_data')->getTitle()));
        } else {
            return Mage::helper('icc_premiumaccess')->__('Add Customer PremiumACCESS User');
        }
    }
}
