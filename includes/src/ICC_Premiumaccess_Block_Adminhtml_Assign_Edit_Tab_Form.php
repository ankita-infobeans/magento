<?php
class ICC_Premiumaccess_Block_Adminhtml_Assign_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {

        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset("premiumaccess_form", array("legend" => Mage::helper("icc_premiumaccess")->__("Ressign Order")));
        
        if (Mage::getSingleton("adminhtml/session")->getAssignData()) {
            $data = Mage::getSingleton("adminhtml/session")->getAssignData();
            Mage::getSingleton("adminhtml/session")->setAssignData(null);
        } elseif (Mage::registry("assign_data")) {
            $data = Mage::registry("assign_data")->getData();
        }
        $field_id = '';
        if($data['future_email'] == NULL){
            $field_id = 'customer_email';
        }else{
            $field_id = 'future_email';
        }
        
        $fieldset->addField($field_id, "text", array(
            "label" => Mage::helper("icc_premiumaccess")->__("Customer Email"),
            "class" => "required-entry validate-email",
            "required" => true,
            "name" => "customer_email",
        ));
        $fieldset->addField('increment_id', 'hidden', array(
            'label' => '',
            'class' => '',
            'required' => true,
            'name' => 'increment_id',
        ));

        $form->setValues($data);

        return parent::_prepareForm();
    }

}
