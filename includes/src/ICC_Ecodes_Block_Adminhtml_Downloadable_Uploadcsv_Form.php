<?php

class ICC_Ecodes_Block_Adminhtml_Downloadable_Uploadcsv_Form extends Mage_Adminhtml_Block_Widget_Form
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
            'action'  => $this->getUrl('*/*/savecsv'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));
        
        $fieldset = $form->addFieldset(
            'base_fieldset',
            array('legend' => $helper->__('CSV Upload'))
        );
        $fieldset->addField('csv_file', 'file', array(
            'name'     => 'csv_file',
            'title'    => $helper->__('CSV File'),
            'label'    => $helper->__('Select CSV Serials File'),
            'required' => true,
        ));
        
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}