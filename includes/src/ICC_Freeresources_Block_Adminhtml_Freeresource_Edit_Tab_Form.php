<?php
/**
 * Free Resource edit form tab
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Block_Adminhtml_Freeresource_Edit_Tab_Form
    extends Mage_Adminhtml_Block_Widget_Form {
    /**
     * prepare the form
     * @access protected
     * @return Freeresources_Freeresource_Block_Adminhtml_Freeresource_Edit_Tab_Form
     */
    protected function _prepareForm(){
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('freeresource_');
        $form->setFieldNameSuffix('freeresource');
        $this->setForm($form);
        $fieldset = $form->addFieldset('freeresource_form', array('legend'=>Mage::helper('icc_freeresources')->__('Free Resource')));

        $fieldset->addField('title', 'text', array(
            'label' => Mage::helper('icc_freeresources')->__('Free Resource'),
            'name'  => 'title',
            'required'  => true,
            'class' => 'required-entry',

        ));
       
        $formValues = Mage::registry('current_freeresource')->getDefaultValues();
        if (!is_array($formValues)){
            $formValues = array();
        }
        if (Mage::getSingleton('adminhtml/session')->getFreeresourceData()){
            $formValues = array_merge($formValues, Mage::getSingleton('adminhtml/session')->getFreeresourceData());
            Mage::getSingleton('adminhtml/session')->setFreeresourceData(null);
        }
        elseif (Mage::registry('current_freeresource')){
            $formValues = array_merge($formValues, Mage::registry('current_freeresource')->getData());
        }
        $form->setValues($formValues);
        return parent::_prepareForm();
    }
}