<?php
class ICC_Volumelicense_Block_Adminhtml_Assign_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

    public function __construct() {
        parent::__construct();
        $this->setId("assign_tabs");
        $this->setDestElementId("edit_form");
        $this->setTitle(Mage::helper("volumelicense")->__("Reassign Order"));
    }

    protected function _beforeToHtml() {
        $this->addTab("form_section", array(
            "label" => Mage::helper("volumelicense")->__("Reassign Order"),
            "title" => Mage::helper("volumelicense")->__("Reassign Order"),
            "content" => $this->getLayout()->createBlock("volumelicense/adminhtml_assign_edit_tab_form")->toHtml(),
        ));
        return parent::_beforeToHtml();
    }

}
