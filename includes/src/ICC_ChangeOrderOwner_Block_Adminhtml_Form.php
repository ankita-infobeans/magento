<?php
class ICC_ChangeOrderOwner_Block_Adminhtml_Form extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'icc_changeorderowner';
        $this->_controller = 'adminhtml_form';

        $this->_updateButton('save', 'label', Mage::helper('icc_changeorderowner')->__('Save'));
    }

    public function getHeaderText()
    {
        return Mage::helper('icc_changeorderowner')->__('Change order\'s owner');
    }
}