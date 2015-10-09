<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Block_Adminhtml_Multipleorderemail_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'multipleorderemail';
        $this->_controller = 'adminhtml_multipleorderemail';

        $this->_updateButton('save', 'label', Mage::helper('multipleorderemail')->__('Save Order Email Block'));
        $this->_updateButton('delete', 'label', Mage::helper('multipleorderemail')->__('Delete'));

        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
        function toggleEditor() {
            if (tinyMCE.getInstanceById('multipleorderemail_content') == null) {
                tinyMCE.execCommand('mceAddControl', false, 'multipleorderemail_content');
            } else {
                tinyMCE.execCommand('mceRemoveControl', false, 'multipleorderemail_content');
            }
        }

        function saveAndContinueEdit(){
            editForm.submit($('edit_form').action+'back/edit/');
        }
        ";
  }

    public function getHeaderText()
    {
        if (Mage::registry('multipleorderemail_data') && Mage::registry('multipleorderemail_data')->getId()) {
            return Mage::helper('multipleorderemail')->__("Edit Order Email Block '%s'", $this->htmlEscape(Mage::registry('multipleorderemail_data')->getTitle()));
        } else {
          return Mage::helper('multipleorderemail')->__('Add Order Email Block');
        }
    }
  
    protected function _prepareLayout() {
        parent::_prepareLayout();
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
    }
}