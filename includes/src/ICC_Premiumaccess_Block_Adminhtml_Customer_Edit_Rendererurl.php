<?php

class ICC_Premiumaccess_Block_Adminhtml_Customer_Edit_Rendererurl extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{	
    public function render(Varien_Object $row)
    {
        //return "test@gmail.com";
        $increment_id =  $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel('sales/order')->load($increment_id,'increment_id');
        return '<a href="'.Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$order->getId())).'">'.$order->getIncrementId().'</a>';
    }
}