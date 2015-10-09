<?php
class ICC_VirtualInStock_Model_Observer
{
    public function virtualZeroQty(Varien_Event_Observer $observer){
        // the purpose is to set flag VirtualZeroQty to true if quantity less or equal 0 (and then use this flag in  the templates), by the client request in the #2014032010000224 ticket
        if ($observer->getProduct() && $observer->getProduct()->getTypeId() == 'virtual'){
            $_product = Mage::registry('product');
            if ($_product && $_product->getStockItem()->getManageStock() == 1 && $_product->getStockItem()->getQty() <= 0){
                $observer->getProduct()->setVirtualZeroQty(true);
            }
        }
    }
}