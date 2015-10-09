<?php
class ICC_Volumelicense_Block_Checkout_Onepage_Success extends Mage_Checkout_Block_Onepage_Success
{
    public function hasExamInOrder()
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        $attribute_collection = Mage::getModel('eav/entity_attribute_set')
                        ->getCollection()
                        ->addFieldToFilter('attribute_set_name', 'exam');
        
        $attribute_set = $attribute_collection->getFirstItem();
        Mage::log($attribute_set->getId(), null, 'checkout-block-onepage-success.log');
        $items = $order->getAllItems();
        $product_ids = array();
        foreach($items as $item)
        {
            $product_ids[] = $item->getProductId();
        }
        $products = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToFilter('entity_id', array( 'in' => $product_ids))
            ->addAttributeToFilter('attribute_set_id', $attribute_set->getId());
        return (bool) (int) $products->count();
    }
}