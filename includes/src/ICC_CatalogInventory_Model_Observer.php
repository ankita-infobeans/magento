<?php

class ICC_CatalogInventory_Model_Observer extends Mage_CatalogInventory_Model_Observer
{

    /**
     * Refresh stock index for specific stock items after succesful order placement
     *
     * @param $observer
     */
    public function reindexQuoteInventory($observer)
    {
        // Reindex quote idsgi
        $quote = $observer->getEvent()->getQuote();
        $productIds = array();
        foreach ($quote->getAllItems() as $item) {
            $productIds[$item->getProductId()] = $item->getProductId();
            $children = $item->getChildrenItems();
            if ($children) {
                foreach ($children as $childItem) {
                    $productIds[$childItem->getProductId()] = $childItem->getProductId();
                }
            }
        }

        if (count($productIds)) {
            Mage::getResourceSingleton('cataloginventory/indexer_stock')->reindexProducts($productIds);
        }

        // Reindex previously remembered items
        $rememberedProductIds = array();
        foreach ($this->_itemsForReindex as $item) {
            if (in_array($item->getProductId(), $productIds)) {
                continue;
            }
            $item->save();
            $rememberedProductIds[] = $item->getProductId();
        }
        Mage::getResourceSingleton('catalog/product_indexer_price')->reindexProductIds($rememberedProductIds);

        $this->_itemsForReindex = array(); // Clear list of remembered items - we don't need it anymore

        return $this;
    }

}
