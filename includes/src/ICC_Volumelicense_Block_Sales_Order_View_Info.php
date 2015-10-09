<?php
class ICC_Volumelicense_Block_Sales_Order_View_Info extends Mage_Adminhtml_Block_Sales_Order_View_Info
{
    public function getChildOrders(){
        $order_collection = Mage::getModel('sales/order')
                ->getCollection()->addAttributeToSelect('*')
                ->addFieldToFilter('parent_order_id', $this->getOrder()->getId())
                ->addFieldToFilter('volume_license', true);
        return $order_collection;
    }
}
