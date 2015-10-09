<?php
/**
 * @copyright  Copyright (c) 2010 Amasty (http://www.amasty.com)
 */ 
class Amasty_Xcoupon_Block_Adminhtml_Promo_Quote_Edit_Form extends Mage_Adminhtml_Block_Promo_Quote_Edit_Form
{

    public function __construct()
    {
        parent::__construct();
    }
    
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post',
            'enctype'=>'multipart/form-data'));
        $form->setUseContainer(true);
        $this->setForm($form);
        return Mage_Adminhtml_Block_Widget_Form::_prepareForm();
    }

}