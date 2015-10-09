<?php

class ICC_CatalogSearch_Model_Layer extends Mage_CatalogSearch_Model_Layer
{
    /**
     * These constants are set to the values that Magento develops on its own
     * In future releases they may change the algorithm for generating these names. To find these
     * uncomment the log and see what the new names are - then change the constants to correspond to that
     */
    const ORDER_BY_ATTRIBUTE_SELECT = 'search_results_position';
    const ORDER_BY_ATTRIBUTE = 'CAST( at_search_results_position.value AS UNSIGNED )';
    const ORDER_BY_RELEVANCE = 'search_result.relevance';
/*    
    public function prepareProductCollection($collection)
    {
        parent::prepareProductCollection($collection);
        
        
        
        
        $request = Mage::app()->getRequest();
        if( ! $request->has('order') || $request->getParam('order') == 'relevance' ) {

            $collection->addAttributeToSelect(self::ORDER_BY_ATTRIBUTE_SELECT, 'left');
            $collection->getSelect()->order( array( self::ORDER_BY_ATTRIBUTE . ' desc', self::ORDER_BY_RELEVANCE . ' DESC'));
//            Mage::log( $collection->getSelectSql(true), null, 'prepare-product-collection.log');
        }
        return $this;

    }
 * 
 */
}