<?php
class Gorilla_Favorites_Block_List extends Mage_Catalog_Block_Product_List {

    public function  _construct() {
        $this->setDisplay('plain');
        $this->setMode('grid');
        parent::_construct();
    }

    public function getLoadedProductCollection($number=null, $order="random") {	
        $category = Mage::registry('current_category');        

        // if cached version of this block
        if ($number == null) {
            $number = $this->getProductCount();
        }
        if (!$category) {
            $categoryId = $this->getCategoryId();
            if ($categoryId) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
            }
        }
        
        $collection = Mage::getModel('catalog/product')->getCollection()
                                                       ->setPage(1, $number)
                                                       ->addPriceData()
                                                       ->addAttributeToSelect('name')
                                                       ->addAttributeToSelect('small_image');

        if ($category) {
			$collection->addCategoryFilter($category)
                       ->addAttributeToFilter('featured', array('eq'=>'1'));
		} else {
			$collection->addAttributeToFilter('featured_home', array('eq'=>'1'));			
		}
        $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());
        if ($order == "random") $collection->getSelect()->order('rand()');

        return $collection;
    }
    
    public function getCacheKeyInfo() {
        $info = array(
                        'FAVORITES_LIST',
                        Mage::app()->getStore()->getCode(),
                        $this->getTemplateFile(),
                        'template' => $this->getTemplate()
                    );
        $category = Mage::registry('current_category');
        $info['category'] = ($category) ? $category->getId() : false;
        $info['product_count'] = $this->getProductCount();
        $info['column_count'] = $this->getColumnCount();
        return $info;
    }
    
}