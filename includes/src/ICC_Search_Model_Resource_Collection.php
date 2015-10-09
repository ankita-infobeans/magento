<?php
class ICC_Search_Model_Resource_Collection extends Enterprise_Search_Model_Resource_Collection {

    /**
     * Search documents by query
     * Set found ids and number of found results
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    
   
    protected function _beforeLoad() {
        
        $ids = array();
        if ($this->_engine) {
            list($query, $params) = $this->_prepareBaseParams();

            if ($this->_sortBy) {
                $params['sort_by'] = $this->_sortBy;
            }
            if ($this->_pageSize !== false) {
                $page = ($this->_curPage > 0) ? (int) $this->_curPage : 1;
                $rowCount = ($this->_pageSize > 0) ? (int) $this->_pageSize : 1;
                $params['offset'] = $rowCount * ($page - 1);
                $params['limit'] = $rowCount;
            }

            $needToLoadFacetedData = (!$this->_facetedDataIsLoaded && !empty($this->_facetedConditions));
            if ($needToLoadFacetedData) {
                $params['solr_params']['facet'] = 'on';
                $params['facet'] = $this->_facetedConditions;
            }

            $result = $this->_engine->getIdsByQuery($query, $params);
            $ids = (array) $result['ids'];
           /**
            * Sort search results based on name DESC added by infobeans 
            */
        /*   if (isset($params['sort_by'][0]['relevance'])) {
                //$idss_numeric = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('name', array('regexp'=>'^[0-9].*$'))->addAttributeToFilter('entity_id', array('in' => $ids))->addAttributeToSort('name', 'DESC');
                //$ids_alphabet = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('name', array('regexp'=>'^[A-Z].*$'))->addAttributeToFilter('entity_id', array('in' => $ids))->addAttributeToSort('name', 'DESC');
                $idss = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('entity_id', array('in' => $ids))->addAttributeToSort('name', 'DESC');
                $sortIds = array_column($idss->getData(), 'entity_id');
                //$sortIds = array_column($idss_numeric->getData(), 'entity_id') + array_column($ids_alphabet->getData(), 'entity_id');
            } else {
                $sortIds = $ids;
            }*/
            if ($needToLoadFacetedData) {
                $this->_facetedData = $result['faceted_data'];
                $this->_facetedDataIsLoaded = true;
            }
        }

        $this->_searchedEntityIds = $ids;

        $this->getSelect()->where('e.entity_id IN (?)', $this->_searchedEntityIds);

        /**
         * To prevent limitations to the collection, because of new data logic.
         * On load collection will be limited by _pageSize and appropriate offset,
         * but third party search engine retrieves already limited ids set
         */
        $this->_storedPageSize = $this->_pageSize;
        $this->_pageSize = false;

        //return parent::_beforeLoad();
    }

}

?>