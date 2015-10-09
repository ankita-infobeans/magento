<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Block_Adminhtml_Multipleorderemail_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('multipleorderemail_');
        $this->setForm($form);    
        $fieldset = $form->addFieldset('multipleorderemail_form', array('legend'=>Mage::helper('multipleorderemail')->__('Information')));

        $fieldset->addField('title', 'text', array(
            'label'     => Mage::helper('multipleorderemail')->__('Order Email Block Name'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'title',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $field = $fieldset->addField('store_id', 'multiselect', array(
                'name'      => 'store_id',
                'label'     => Mage::helper('multipleorderemail')->__('Store View'),
                'title'     => Mage::helper('multipleorderemail')->__('Store View'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),      
            ));
            $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
            $field->setRenderer($renderer);
        } else {
            $fieldset->addField('store_id', 'hidden', array(
              'name'      => 'store_id',
              'value'     => Mage::app()->getStore(true)->getId()
            ));
            Mage::registry('multipleorderemail_data')->setStoreId(Mage::app()->getStore(true)->getId());
        }


        $fieldset->addField('description', 'textarea', array(
            'name' => 'description',
            'label' => Mage::helper('multipleorderemail')->__('Description'),
            'title' => Mage::helper('multipleorderemail')->__('Description'),
            'style' => 'height: 100px;',
        ));

        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('multipleorderemail')->__('Status'),
            'title'     => Mage::helper('multipleorderemail')->__('Status'),
            'name'      => 'status',
            'required' => true,
            'options'    => array(
                '' => Mage::helper('multipleorderemail')->__('Please Select Status'),
                '1' => Mage::helper('multipleorderemail')->__('Enable'),
                '2' => Mage::helper('multipleorderemail')->__('Disable'),
            ),
        ));     

        $fieldset->addField('sort_order', 'text', array(
            'name' => 'sort_order',
            'label' => Mage::helper('multipleorderemail')->__('Priority'),
        ));  

        if ( Mage::getSingleton('adminhtml/session')->getMultiplelorderemailData() ) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getMultiplelorderemailData());
            Mage::getSingleton('adminhtml/session')->setMultiplelorderemailData(null);
        } elseif ( Mage::registry('multipleorderemail_data') ) {
            $form->setValues(Mage::registry('multipleorderemail_data')->getData());
        }                
        return parent::_prepareForm();
    }
}