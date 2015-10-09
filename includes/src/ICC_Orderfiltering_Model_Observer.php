<?php

class ICC_Orderfiltering_Model_Observer extends Mage_Core_Model_Abstract {

    public function collectionLoad($observer) {
/*
        $collection = $observer->collection;
        if (get_class($collection) == "Mage_Sales_Model_Resource_Order_Grid_Collection") {
            
            $filter = Mage::app()->getRequest()->getParams(); 
              $doSearch = false;
            if (isset($filter['filter'])) {
                $filter = $filter['filter'];
                $filter_data = Mage::helper('adminhtml')->prepareFilterString($filter);

              
                if (isset($filter_data['Order_Type'])) {
                    $filtervalue = $filter_data['Order_Type'];
                    $doSearch = true;
                }
            }


            foreach ($collection as $c) {
                $searchfound = false;

                $id = $c->getEntityId();
                $order = Mage::getModel('sales/order')->load($id);
                $items = $order->getItemsCollection();

                $product_types = array();

                foreach ($items as $item) {

                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                    $product_types[] = $product->getAttributeText('item_type');

                    if ($doSearch) {
                        if ($product->getAttributeText('item_type') == $filtervalue) {
                            $searchfound = true;
                        }
                    }
                }
                $c->setProductTypes(
                        array_unique($product_types)
                );

                if (!$searchfound && $doSearch) {
                    $collection->removeItemByKey($id);
                }
            }
        }
 * 
 */
    }

    public function addFilter($observer) {
/*
        $type = $observer->getEvent()->getBlock()->getType();
        if ($type == "adminhtml/sales_order_grid") {
            $block = $observer->getEvent()->getBlock();
            $block->addColumn('Order Type', array(
                'header' => Mage::helper('sales')->__('Product Types'),
                'index' => 'product_type',
                'type' => 'options',
                'width' => '150px',
                'options' => Mage::helper('icc_orderfiltering')->getTypes(),
                'renderer' => 'ICC_Orderfiltering_Block_Adminhtml_Renderer_Producttype',
            ));
        }
  */
    }

}