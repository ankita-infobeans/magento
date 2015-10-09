<?php

class ICC_Orderfiltering_Block_Adminhtml_Renderer_Customername extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row){
       return Mage::getModel('sales/order')->load($row->getId())->getCustomerName();
    }
}

?>