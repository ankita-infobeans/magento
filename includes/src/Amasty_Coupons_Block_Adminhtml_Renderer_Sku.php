<?php
/**
* @author Amasty Team
* @copyright Amasty
* @package Amasty_Coupons
*/
class Amasty_Coupons_Block_Adminhtml_Renderer_Sku extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row) {
        $array = array();
        $id =  $row->getData('rule_id');
        $coupons = Mage::getModel('salesrule/rule')->load($id);

        //echo $select = $coupons->getSelect();

        //   echo '<pre>'; print_r(unserialize($coupons->getConditionsSerialized()));
        //    echo "--------------------";
        //    print_r(unserialize($coupons->getActionsSerialized()));
        //
        $sku = '';
        $data = Mage::helper('amcoupons')->arrayDepth(unserialize($coupons->getConditionsSerialized()));
        if ($data['attribute'] == 'sku') {
	    $sku = $data['value'];
	  //  $strSku = settype($sku, 'string');
            return $sku."";
        }
        if ($sku == '') {
            $data = Mage::helper('amcoupons')->arrayDepth(unserialize($coupons->getActionsSerialized()));
            if ($data['attribute'] == 'sku') {
                return $sku = $data['value'];
            }
        }
        return '-';
    }
}
