<?php

class Gorilla_Queue_Block_Adminhtml_Queue_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected function _construct()
    {
        parent::_construct();
        $this->_objectId = 'queue_id';
        $this->_blockGroup = 'gorilla_queue';
        $this->_controller = 'adminhtml_queue';
        $this->_mode = 'edit';
        $this->_headerText = $this->helper('ecodes')->__('Edit Queue Item');
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->_updateButton('save', 'label', $this->__('Save Queue Item'));
        $this->_addButton(
            'save_and_continue',
            array(
                'label' => $this->__('Save and Continue Edit'),
                'onclick' => 'saveAndContinueEdit()',
                'class' => 'save',
            ),
            100
        );

        $this->_formScripts[] = "
            function saveAndContinueEdit () {
                editForm.submit($('edit_form').action ='back/edit';
            }
        ";
        
        return $this;
    }
}