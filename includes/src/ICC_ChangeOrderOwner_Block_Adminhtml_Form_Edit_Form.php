<?php
class ICC_ChangeOrderOwner_Block_Adminhtml_Form_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('change_owner_form');
    }

    protected function _prepareForm()
    {
        $formData = Mage::getSingleton('adminhtml/session')->getFormData(true);

        $form = new Varien_Data_Form(array(
                'id'      => 'edit_form',
                'action'  => $this->getUrl('*/*/savePost', array('id' => $this->getRequest()->getParam('id'))),
                'method'  => 'post',
                'enctype' => 'multipart/form-data'
            )
        );

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => Mage::helper('icc_changeorderowner')->__('Change owner'),
            'class'     => 'fieldset-wide',
        ));

        $fieldset->addField('increment_id', 'text', array(
            'name'      => 'increment_id',
            'label'     => Mage::helper('icc_changeorderowner')->__('Order #'),
            'title'     => Mage::helper('icc_changeorderowner')->__('Order #'),
            'class'     => 'validate-digits',
            'note'      => 'Enter the order increment ID, in which you want to change the email',
            'required'  => true,
        ));

        $fieldset->addField('customer_email', 'text', array(
            'name'      => 'customer_email',
            'label'     => Mage::helper('icc_changeorderowner')->__('Customer email'),
            'title'     => Mage::helper('icc_changeorderowner')->__('Customer email'),
            'class'     => 'validate-email',
            'note'      => 'Enter an email of the new owner (important: the customer should already be registered in the store)',
            'required'  => true,
        ));

        $form->setValues($formData);
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}