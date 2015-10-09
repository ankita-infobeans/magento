<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Block_Adminhtml_Multipleorderemail_Edit_Tab_Template extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $data = Mage::registry('multipleorderemail_data')->getData();  
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('multipleorderemail_');
        $this->setForm($form);
        $fieldset = $form->addFieldset('multipleorderemail_form', array('legend'=>Mage::helper('multipleorderemail')->__('Email Template')));

        $fieldset->addField('order_email_block', 'editor', array (
            'name'      => 'order_email_block',
            'label'     => Mage::helper('multipleorderemail')->__('Content'),
            'title'     => Mage::helper('multipleorderemail')->__('Content'),
            'style'     => 'height:15em; width:50em;',
            'config'    => Mage::getSingleton('cms/wysiwyg_config')->getConfig(),
            'wysiwyg'   => true,
            'required'  => true,
        ));
        if ( Mage::getSingleton('adminhtml/session')->getMultiplelorderemailData() ) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getMultiplelorderemailData());
            Mage::getSingleton('adminhtml/session')->setMultiplelorderemailData(null);
        } elseif ( Mage::registry('multipleorderemail_data') ) {
            $form->setValues($data);
        }                
        return parent::_prepareForm();
    }
}
