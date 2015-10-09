<?php

class ICC_Ecodes_Block_Adminhtml_Managepremium_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare the inn form wrapper
     */
    protected function _prepareForm() 
    {
        $id = (int) $this->getRequest()->getParam('id');
        $is_new = (empty($id));
        $form = new Varien_Data_Form( array(
            'id' =>'edit_form',
            'action'=> $this->getUrl('*/*/save',
                array(
                    'id' => $id,
                    'subscription_id' => (int)$this->getRequest()->getParam('subscription_id')
                )),
            'method' => 'post',
            'enctype' => 'multipart/form-data',
        ));
        $form->setUseContainer(true);
        $data = Mage::registry('current_premiumusers');
        
        $fieldset = $form->addFieldset('edit_form', array(
            'legend' => $data->getName(),
        ));
        
        $fieldset->addField('firstname', 'text', array(
            'label'     => 'First Name',
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'firstname',
        ));
        
        $fieldset->addField('lastname', 'text', array(
            'label'     => 'Last Name',
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'lastname',
        ));
        
        $user_params = array(
            'label'     => 'Username',
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'user',
            'note'      => 'To add users from other lists enter their username here. Other fields will not be updated.',
        );
        
        if(!$is_new)
        {
            unset($user_params['note']);
            $user_params['disabled'] = 'true';
        }
        
        $fieldset->addField('user', 'text', $user_params );

        $fieldset->addField('email', 'text', array(
            'label'     => 'Email Address',
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'email',
        ));
        
        $fieldset->addField('new_pass', 'password', array(
            'label'     => 'Password',
            'name'      => 'new_pass',
        ));        

        $fieldset->addField('confirm_new_pass', 'password', array(
            'label'     => 'Confirm Password',
            'name'      => 'confirm_new_pass',
        ));        
        
        
        $form->setValues($data);
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}