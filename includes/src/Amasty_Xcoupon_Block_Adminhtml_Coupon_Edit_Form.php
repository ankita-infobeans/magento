<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */  
class Amasty_Xcoupon_Block_Adminhtml_Coupon_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
        //create form structure
        $form = new Varien_Data_Form(array(
          'id' => 'edit_form',
          'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
          'method' => 'post',
         ));
        
        $form->setUseContainer(true);
        $this->setForm($form);
        
        $hlp   = Mage::helper('salesrule');
        $model = Mage::registry('amxcoupon_coupon');
        
        $fldMain = $form->addFieldset('main', array('legend'=> $hlp->__('General Information')));
        
        $fldMain->addField('code', 'text', array(
          'label'     => $hlp->__('Coupon Code'),
          'name'      => 'code',
          'required'  => true,
        )); 
               
        $fldMain->addField('usage_limit', 'text', array(
          'label'     => $hlp->__('Uses per Coupon'),
          'name'      => 'usage_limit',
        ));
        $fldMain->addField('usage_per_customer', 'text', array(
          'label'     => $hlp->__('Uses per Customer'),
          'name'      => 'usage_per_customer',
        ));
        
        //set form values
        $data = Mage::getSingleton('adminhtml/session')->getFormData();
        if ($data) {
            $form->setValues($data);
            Mage::getSingleton('adminhtml/session')->setFormData(null);
        }
        elseif ($model) {
            $form->setValues($model->getData());
        }
        
        return parent::_prepareForm();
  }
}