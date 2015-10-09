<?php
class ICC_Volumelicense_Block_Adminhtml_Assign_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    public function __construct() {
  
        
        parent::__construct();
        $this->_objectId = "id";
        $this->_blockGroup = "volumelicense";
        $this->_controller = "adminhtml_assign";
        
        $this->_removeButton('delete');
        $this->_removeButton('reset');
        $this->_removeButton('back');
        $data = array(
            'label' => 'Back',
            'onclick' => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $this->getRequest()->getParam("parent"))) . '\')',
            'class' => 'back'
        );
        $reset = array(
            'label' => 'Reset',
            'onclick' => 'location.reload()',
            'class' => 'reset'
        );
        $this->_removeButton('reset');
        $this->addButton('back', $data, 0, 0, 'header');
        $this->addButton('reset', $reset, 0, 0, 'header');
        $this->_updateButton("save", "label", Mage::helper("volumelicense")->__("Save Item"));
        $this->_addButton("saveandcontinue", array(
            "label" => Mage::helper("volumelicense")->__("Save And Continue Edit"),
            "onclick" => "saveAndContinueEdit()",
            "class" => "save",
                ), -100);

        $this->_formScripts[] = "
                                function saveAndContinueEdit(){
                                        editForm.submit($('edit_form').action+'back/edit/');
                                }
                        ";
    }

    public function getHeaderText() {
        if (Mage::registry("assign_data") && Mage::registry("assign_data")->getId()) {

            return Mage::helper("volumelicense")->__("Reassign Order#: %s", $this->htmlEscape(Mage::registry("assign_data")->getIncrementId()));
        } else {

            Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$this->getRequest()->getParam("parent"))));
        }
    }

}
