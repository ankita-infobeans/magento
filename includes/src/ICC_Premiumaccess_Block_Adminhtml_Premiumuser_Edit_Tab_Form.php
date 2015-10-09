<?php

class ICC_Premiumaccess_Block_Adminhtml_Premiumuser_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
        
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('premiumuser_form', array('legend'=>Mage::helper('icc_premiumaccess')->__('Customer PremiumACCESS User Information')));
     
     
      $fieldset->addField('email', 'text', array(
          'label'     => Mage::helper('icc_premiumaccess')->__('Customer Email Id'),
          'class'     => 'required-entry validate-email',
          'required'  => true,
          'name'      => 'email',
      ));     
     
      if ( Mage::getSingleton('adminhtml/session')->getEcodesData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getEcodesData());
          Mage::getSingleton('adminhtml/session')->setEcodesData(null);
      } elseif ( Mage::registry('ecodes_data') ) {
          $form->setValues(Mage::registry('ecodes_data')->getData());
      }
      return parent::_prepareForm();
  }
}