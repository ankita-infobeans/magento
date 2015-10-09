<?php

class ICC_Ecodes_Block_Adminhtml_Premium_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare the inn form wrapper
     */
    protected function _prepareForm() {
        $form = new Varien_Data_Form( array(
            'id' =>'edit_form',
            'action'=> $this->getUrl('*/*/save',
                array(
                    'id' => (int)$this->getRequest()->getParam('id')
                )),
            'method' => 'post',
            'enctype' => 'multipart/form-data',
        ));
        $form->setUseContainer(true);
        $data = Mage::registry('current_premiumsubs');
        
        $fieldset = $form->addFieldset('edit_form', array(
            'legend' => $data->getProductName(),
        ));
        
        $fieldset->addField('sku', 'text', array(
            'label'     => 'The Product Sku',
            'name'      => 'sku',
            'disabled' => 'true',
        ));
        
        $fieldset->addField('expiration', 'text', array(
            'label'     => 'Expiry Date',
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'expiration',
            'note'     => 'Please use the following format: YYYY-MM-DD HH:MM:SS',
        ));
        
        $fieldset->addField('seats_total', 'text', array(
            'label'     => 'Total Number of Seats',
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'seats_total',
        ));
        
        $fieldset->addField('registered', 'text', array(
            'label'     => 'Registered?',
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'registered',
        ));
        
        $fieldset->addField('emails_sent', 'text', array(
            'label'     => 'Number of Notification Emails Sent',
            'name'     => 'emails_sent'
        ));
        
        $fieldset->addField('notes', 'textarea', array(
            'label'     => 'Notes Field',
           // 'class'     => 'required-entry',
           // 'required'  => true,
            'name'      => 'notes',
        )); 
        
        $form->setValues($data);
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}