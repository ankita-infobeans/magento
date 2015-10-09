<?php

class ICC_Ecodes_Block_Adminhtml_Premiumusers_Edit_Tab_Users extends Mage_Adminhtml_Block_Widget_Form {

	protected function _prepareForm() {

        /** @var $location  */
        $premiumusers = Mage::registry('ecodes_premiumusers');

        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('users', array('legend' => Mage::helper('ecodes')->__('User')));

        // die( get_class($fieldset ) );

        $fieldset->addField('id', 'hidden', array(
            'name' => 'id',
            'required' => false,
            'disabled' => false
        ));

        $fieldset->addField('firstname', 'text', array(
            'name' => 'firstname',
            'label' => Mage::helper('ecodes')->__('First Name'),
            'title' => Mage::helper('ecodes')->__('First Name'),
            'disabled' => false,
            'required' => false
        ));

        $fieldset->addField('lastname', 'text', array(
            'name' => 'lastname',
            'label' => Mage::helper('ecodes')->__('Last Name'),
            'title' => Mage::helper('ecodes')->__('Last Name'),
            'disabled' => false,
            'required' => false
        ));

        $fieldset->addField('email', 'text', array(
            'name' => 'email',
            'label' => Mage::helper('ecodes')->__('Email'),
            'title' => Mage::helper('ecodes')->__('Email'),
            'disabled' => false,
            'required' => false
        ));

        $fieldset->addField('user', 'text', array(
            'name' => 'user',
            'label' => Mage::helper('ecodes')->__('ICC Connect Username'),
            'title' => Mage::helper('ecodes')->__('ICC Connect Username'),
            'disabled' => false,
            'required' => false
        ));

        $fieldset->addField('pass', 'text', array(
            'name' => 'pass',
            'label' => Mage::helper('ecodes')->__('Password'),
            'title' => Mage::helper('ecodes')->__('Password'),
            'disabled' => false,
            'required' => false
        ));

        $fieldset->addField('', 'text', array(
            'name' => '',
            'label' => Mage::helper('ecodes')->__(''),
            'title' => Mage::helper('ecodes')->__(''),
            'disabled' => false,
            'required' => false
        ));

        $form->setValues($premiumusers->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

}

?>