<?php
class ICC_Catalog_Block_Product_List extends Mage_Catalog_Block_Product_List {
	/**
	 * Need use as _prepareLayout - but problem in declaring collection from
	 * another block (was problem with search result)
	 */
	protected function _beforeToHtml() {
                $catId ='';
            if(Mage::registry('current_category')):
                $catId =  Mage::registry('current_category')->getId();
        endif;
		$toolbar = $this->getToolbarBlock ();
		
		// called prepare sortable parameters
		$collection = $this->_getProductCollection ();
                
                if($catId == '539'){ // 539 stands for live Schedule category
                    $curr_date = Mage::getModel('core/date')->date('Y-m-d H:i:s');

                    $collection->addAttributeToFilter('event_date', array('gteq' => $curr_date));
                }
                
		// use sortable parameters
		if ($orders = $this->getAvailableOrders ()) {
			$toolbar->setAvailableOrders ( $orders );
		}
		if ($sort = $this->getSortBy ()) {
			$toolbar->setDefaultOrder ( $sort );
		}
		if ($dir = $this->getDefaultDirection ()) {
			$toolbar->setDefaultDirection ( $dir );
		}
		if ($modes = $this->getModes()) {
			$toolbar->setModes ( $modes );
		}
		
		// fix for POSITION sorting by ASC but all other sortings are by DESC
		$current_sort = Mage::app ()->getRequest ()->getParam ( 'order' );
		
		// set collection to toolbar and apply sort
		$toolbar->setCollection ( $collection );
		
		$this->setChild ( 'toolbar', $toolbar );
		Mage::dispatchEvent ( 'catalog_block_product_list_collection', array (
				'collection' => $this->_getProductCollection () 
		) );
		$store_parameter = Mage::app ()->getRequest ()->getParam ( '___store' );
		if ($current_sort == 'position') {
			$collection->getSelect ()->reset ( Zend_Db_Select::ORDER );
			$collection->setOrder ( 'position', 'asc' );
		} elseif (empty ( $current_sort ) && ! isset ( $store_parameter )) {
			$collection->getSelect ()->reset ( Zend_Db_Select::ORDER );
			$collection->setOrder ( 'position', 'asc' );
		}
		$this->_getProductCollection ()->load ();
		return parent::_beforeToHtml ();
	}
}
?>