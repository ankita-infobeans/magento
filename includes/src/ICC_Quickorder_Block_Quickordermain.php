<?php
class ICC_Quickorder_Block_Quickordermain extends Mage_Core_Block_Template {

    var $_theData;

    public function getTheData() {
        if (!isset($this->_theData)) {
            $this->_theData = Mage::getModel('quickorder/quickorder');
        }
        return $this->_theData;
    }

    public function getQuickorderItems() {
        return Mage::helper('quickorder')->getQuickorderItems();
    }

    private function getUploadedData() {
        return $this->getTheData()->getArray();
    }

    public function getProductListJson() {
        return Zend_Json::encode($this->getUploadedData());
    }

    public function dumpProductList() {
        //return print_r($this->getUploadedData(), 1);
    }

    public function getProductList() {
        return MAge::helper('quickorder')->prettyQuickorder();
    }

}

?>
