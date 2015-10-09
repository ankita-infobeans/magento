<?php

class ICC_Ecodes_Block_Adminhtml_Downloadable_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected function _construct()
    {
        parent::_construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'ecodes';
        $this->_controller = 'adminhtml_downloadable';
        $this->_mode = 'edit';
        $this->_headerText = $this->helper('ecodes')->__('Edit eCode Serial Item');
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->_updateButton('save', 'label', $this->__('Save eCode Serial Item'));

        return $this;
    }
}