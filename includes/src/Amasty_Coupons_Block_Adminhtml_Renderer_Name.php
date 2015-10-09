<?php
/**
* @author Amasty Team
* @copyright Amasty
* @package Amasty_Coupons
*/
class Amasty_Coupons_Block_Adminhtml_Renderer_Name extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row) {
        $id =  $row->getData('rule_id');
        $coupons = Mage::getModel('salesrule/rule')->load($id);
        $sku = '';
        $data = Mage::helper('amcoupons')->arrayDepth(unserialize($coupons->getConditionsSerialized()));
        if ($data['attribute'] == 'sku') {
            $sku = $data['value'];
        }
        if ($sku == '') {
            $data = Mage::helper('amcoupons')->arrayDepth(unserialize($coupons->getActionsSerialized()));
            if ($data['attribute'] == 'sku') {
                $sku = $data['value'];
            }
        }
        $productDetails = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        if ($productDetails) {
            $name = $productDetails->getName();
            return $name;
        }
        return '-';
    }
}
