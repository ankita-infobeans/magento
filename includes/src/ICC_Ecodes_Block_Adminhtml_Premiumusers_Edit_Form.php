<?php
class ICC_Ecodes_Block_Adminhtml_Premiumusers_Edit_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form', 
            'action' => $this->getData('action'), 
            'method' => 'post'
            ));
        
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
