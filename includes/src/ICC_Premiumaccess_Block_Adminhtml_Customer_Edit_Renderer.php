<?php

class ICC_Premiumaccess_Block_Adminhtml_Customer_Edit_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{	
    public function render(Varien_Object $row)
    {
        //return "test@gmail.com";
        $customerId =  $row->getData($this->getColumn()->getIndex());
        $customer = Mage::getModel('customer/customer')->load($customerId);
        return $customer->getEmail();
    }
}