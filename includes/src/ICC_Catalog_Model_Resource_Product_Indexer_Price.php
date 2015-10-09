<?php 
/* 
 * This module is overridden due to below error
 * Integrity constraint violation: 1062 Duplicate entry '42463-0-1' for key 'PRIMARY'
 * see https://github.com/organicinternet/magento-configurable-simple/issues/52
 * 
 */
class ICC_Catalog_Model_Resource_Product_Indexer_Price extends Mage_Catalog_Model_Resource_Product_Indexer_Price{ 
    
    /**
     * Copy relations product index from primary index to temporary index table by parent entity
     *
     * @package array|int $excludeIds
     *
     * @param array|int $parentIds
     * @param unknown_type $excludeIds
     * @return Mage_Catalog_Model_Resource_Product_Indexer_Price
     */
    protected function _copyRelationIndexData($parentIds, $excludeIds = null)
    {
        $write  = $this->_getWriteAdapter();
        $select = $write->select()
            ->from($this->getTable('catalog/product_relation'), array('child_id'))
            ->where('parent_id IN(?)', $parentIds);
        if (!empty($excludeIds)) {
            $select->where('child_id NOT IN(?)', $excludeIds);
        }

        $children = $write->fetchCol($select);

        if ($children) {
            $select = $write->select()
                ->from($this->getMainTable())
                ->where('entity_id IN(?)', $children);
           // $query  = $select->insertFromSelect($this->getIdxTable(), array(), false);
            $query  = $select->insertIgnoreFromSelect($this->getIdxTable(), array(), false);
            $write->query($query);
        }
        
        return $this;
    }
    

}
?>