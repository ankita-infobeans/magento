<?php

class ICC_Ecodes_Block_Adminhtml_Downloadable_New_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Add fieldset
     *
     * @return \Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $helper = Mage::helper('ecodes');
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/import'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));
        $fieldset = $form->addFieldset(
            'base_fieldset',
            array('legend' => $helper->__('Import Settings (Associate new eCodes to a SKU below.)'))
        );
        $fieldset ->addField('sku', 'text', array(
            'name'     => 'sku',
            'title'    => $helper->__('Associated SKU'),
            'label'    => $helper->__('Associated SKU'),
            'required' => true,
        ));
        $fieldset ->addField('ecodes', 'textarea', array(
            'name'     => 'ecodes',
            'title'    => $helper->__('Enter eCodes here.'),
            'label'    => $helper->__('eCodes'),
            'required' => true,
            'note'     => $helper->__('eCodes must be delimited by newline characters.'),
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}