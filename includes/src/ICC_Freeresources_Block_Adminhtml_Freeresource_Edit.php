<?php
/**
 * Free Resource admin edit form
 *
 * @category    ICC
 * @package     ICC_Freeresources
  */
class ICC_Freeresources_Block_Adminhtml_Freeresource_Edit
    extends Mage_Adminhtml_Block_Widget_Form_Container {
    /**
     * constructor
     * @access public
     * @return void
     */
    public function __construct(){
        parent::__construct();
        $this->_blockGroup = 'icc_freeresources';
        $this->_controller = 'adminhtml_freeresource';
        $this->_updateButton('save', 'label', Mage::helper('icc_freeresources')->__('Save Free Resource'));
        $this->_updateButton('delete', 'label', Mage::helper('icc_freeresources')->__('Delete Free Resource'));
        $this->_addButton('saveandcontinue', array(
            'label'        => Mage::helper('icc_freeresources')->__('Save And Continue Edit'),
            'onclick'    => 'saveAndContinueEdit()',
            'class'        => 'save',
        ), -100);
        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }
    /**
     * get the edit form header
     * @access public
     * @return string
     */
    public function getHeaderText(){
        if( Mage::registry('current_freeresource') && Mage::registry('current_freeresource')->getId() ) {
            return Mage::helper('icc_freeresources')->__("Edit Free Resource '%s'", $this->escapeHtml(Mage::registry('current_freeresource')->getFreeResource(              )));
        }
        else {
            return Mage::helper('icc_freeresources')->__('Add Free Resource');
        }
    }
}