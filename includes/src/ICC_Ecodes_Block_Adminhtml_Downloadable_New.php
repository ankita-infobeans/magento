<?php
class ICC_Ecodes_Block_Adminhtml_Downloadable_New extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_downloadable';
        $this->_blockGroup = 'ecodes';
        $this->_mode = 'new';
        $this->_headerText = $this->helper('ecodes')->__('eCode Import Tool');

        parent::__construct();

        $this->_removeButton('reset')
            ->_removeButton('delete')
            ->_updateButton('save', 'label', $this->__('Import Data'))
            ->_updateButton('save', 'id', 'upload_button')
            ->_updateButton('save', 'onclick', 'editForm.submit();');;
    }

}