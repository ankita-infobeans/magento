<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */  
class Amasty_Xcoupon_Block_Adminhtml_Import extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        //create form structure
        $form = new Varien_Data_Form();
        $this->setForm($form);
        
        $hlp = Mage::helper('amxcoupon');
        
        $fldSet = $form->addFieldset('amxcoupon_general', array('legend'=> $hlp->__('General')));
        $fldSet->addField('import_clear', 'select', array(
          'label'     => $hlp->__('Delete Existing Coupons'),
          'name'      => 'import_clear',
          'values'    => array(
            array(
                'value' => 0,
                'label' => Mage::helper('catalog')->__('No')
            ),
            array(
                'value' => 1,
                'label' => Mage::helper('catalog')->__('Yes')
            ))
        ));
                
        $fldSet = $form->addFieldset('amxcoupon_generate', array('legend'=> $hlp->__('Generate Options')));
        $fldSet->addField('generate_num', 'text', array(
          'label'     => $hlp->__('Number of Coupons'),
          'name'      => 'generate_num',
        )); 
        $fldSet->addField('generate_pattern', 'text', array(
          'label'     => $hlp->__('Template'),
          'name'      => 'generate_pattern',
          'note'      => Mage::getModel('amxcoupon/generator')->getDescription()
        ));  

        $fldSet = $form->addFieldset('amxcoupon_import', array('legend'=> $hlp->__('Import Options')));
        $fldSet->addField('import_file', 'file', array(
          'label'     => $hlp->__('CSV File'),
          'name'      => 'import_file',
          'note'      => $hlp->__('Each coupon code on a new line')
        ));               

        return parent::_prepareForm();
    }
}