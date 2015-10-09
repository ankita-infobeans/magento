<?php
class ICC_Checkout_Block_Onepage_Success extends Mage_Checkout_Block_Onepage_Success
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
    
    public function getOrderContent($orderId)
    {
        $itemArray = array();
        $appStore = Mage::app()->getStore()->getStoreId();
        $stores = array(0, $appStore);
        $ruleResource = Mage::getResourceModel('multipleorderemail/multipleorderemailrule')->getlistRuleIds($stores);    
        $ordeEmailRule = Mage::getModel('multipleorderemail/multipleorderemailrule');
        $ruleModel = $ordeEmailRule->getCollection()->addFieldToFilter('rule_id',array('in'=> $ruleResource))->AddFieldToFilter('status',1)->setOrder('sort_order', 'asc');
        $dynamic_block = '';
        $order = Mage::getModel("sales/order")->loadByIncrementId($orderId);
        $tyepArray = array();
        $itemCollection = $order->getAllItems();
        foreach ($itemCollection as $item) {      
            $products[] = $item->getProductId();
        }
        $productsCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addIdFilter($products)
                ->load();
        $product_types = Mage::helper('icc_orderfiltering')->getTypes(true);
        foreach ($itemCollection as $item) {
            $product = $productsCollection->getItemById($item->getProductId());
            $itemType = $product->getData('item_type');
            if (isset($itemType)) {
                $attrVal = $product_types [$itemType];
                $attrVal = strtolower($attrVal);
                $tyepArray[$item->getItemId()]['item_type'] = $attrVal;
            }
        }
        foreach ($ruleModel as $rule) {
            $itemArray = array();
            $items = array();
            $parentItemId = '';
            foreach($itemCollection as $item) {
                $items[$item->getItemId()] = $item->getProductId();
                $result = $rule->getActions()->validate($item); 
                if ($result == true) {
                    $itemArray[$item->getProductId()] =  $item->getProductId();
                }
                $itemType = $tyepArray[$item->getItemId()]['item_type'];
                if ( $itemType == 'membership') {
                    $parentItemId = $item->getItemId();
                }
                if (($item->getParentItemId()) && ($item->getParentItemId() != $parentItemId)) {
                    unset($itemArray[$items[$item->getParentItemId()]]);
                }
            }
            if (!empty($itemArray)) {
                $dynamic_block .= $rule->getOrderEmailBlock();
            }
        }  
        return $dynamic_block;
    }
}
