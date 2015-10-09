<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_CatalogSearch
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product search result block
 *
 * @category   Mage
 * @package    Mage_CatalogSearch
 * @module     Catalog
 */
class Cmsmart_Ajaxsearch_Block_Result extends Mage_Core_Block_Template
{
   
    protected $_productCollection;

   
    protected function _getQuery()
    {
        return $this->helper('catalogsearch')->getQuery();
    }
    protected function _prepareLayout()
    {
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $title = $this->__("Search results for: '%s'", $this->helper('catalogsearch')->getQueryText());

            $breadcrumbs->addCrumb('home', array(
                'label' => $this->__('Home'),
                'title' => $this->__('Go to Home Page'),
                'link'  => Mage::getBaseUrl()
            ))->addCrumb('search', array(
                'label' => $title,
                'title' => $title
            ));
        }

        $title = $this->__("Search results for: '%s'", $this->helper('catalogsearch')->getEscapedQueryText());
        $this->getLayout()->getBlock('head')->setTitle($title);

        return parent::_prepareLayout();
    }

    public function getAdditionalHtml()
    {
        return $this->getLayout()->getBlock('search_result_list')->getChildHtml('additional');
    }
    public function getListBlock()
    {
        return $this->getChild('search_result_list');
    }

    public function setListOrders()
    {
        $category = Mage::getSingleton('catalog/layer')
            ->getCurrentCategory();
        /* @var $category Mage_Catalog_Model_Category */
        $availableOrders = $category->getAvailableSortByOptions();
        unset($availableOrders['position']);
        $availableOrders = array_merge(array(
            'relevance' => $this->__('Relevance')
        ), $availableOrders);

        $this->getListBlock()
            ->setAvailableOrders($availableOrders)
            ->setDefaultDirection('desc')
            ->setSortBy('relevance');

        return $this;
    }

    public function setListModes()
    {
        $this->getListBlock()
            ->setModes(array(
                'grid' => $this->__('Grid'),
                'list' => $this->__('List'))
            );
        return $this;
    }

    public function setListCollection()
    {
     
       return $this;
    }

    public function getProductListHtml()
    {
        return $this->getChildHtml('search_result_list');
    }

					   
    protected function _getProductCollection()
    {
		$product_results = Mage::getResourceModel('reports/product_collection');                
        switch (Mage::getStoreConfig('ajaxsearch/preview/sort_by')) {
            case 0:
                $sort_by = 'entity_id';
                break;
            case 1:
                $sort_by = 'name';
                break;
            case 2:
                $sort_by = 'price';
                break;
            case 3:
                $sort_by = 'created_at';
                break;
            case 4:
                $sort_by = 'qty';
                break;
            default:
                $sort = 'entity_id';
        }
        switch (Mage::getStoreConfig('ajaxsearch/preview/order')) {
            case 0:
                $order = 'ASC';
                break;  
            case 1:
                $order = 'DESC';
                break; 
            default:
                $order = 'ASC';  
        }        
        $productids = Mage::app()->getRequest()->getParam('search_categories');
				foreach ($productids as $k => $cat) {
					 $ctf[]['finset'] = $cat;
				} 
		if($ctf){
			$product_results->joinField('category_id','catalog/category_product','category_id','product_id=entity_id',null,'left')
						->addAttributeToSelect('entity_id')
                        ->addAttributeToSelect('description')                        
                        ->addAttributeToSelect('short_description')
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('price')                      
                        ->addAttributeToSelect('url_path')
                        ->addAttributeToSelect('small_image') 
                        ->addAttributeToSelect('type_id')                                                                       
                        ->addAttributeToFilter(                        
                            array(
                                array('attribute'=>'name','like' => '%'.Mage::app()->getRequest()->getParam('q').'%'),
                                array('attribute'=>'description', 'like' => '%'.Mage::app()->getRequest()->getParam('q').'%'),
                                array('attribute'=>'short_description', 'like' => '%'.Mage::app()->getRequest()->getParam('q').'%'),                                                                                                                                               
                            )
                        )
						->addAttributeToFilter('category_id', array($ctf))
                        ->addAttributeToFilter(
                            'status', array('eq'=>'1')
                        )
                        ->addAttributeToFilter(
                            'visibility', array('neq' => '1')
                            
                        )
                        ->setOrder(
                            $sort_by,$order
                        )
                        ->setPageSize(Mage::getStoreConfig('ajaxsearch/preview/number_product'));            
                                             
			$product_results->getSelect()->group('product_id')->distinct(true);
            $product_results->load();
            return $product_results;
		} else {
			$product_results->addAttributeToSelect('entity_id')
                        ->addAttributeToSelect('description')                        
                        ->addAttributeToSelect('short_description')
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('price')                      
                        ->addAttributeToSelect('url_path')
                        ->addAttributeToSelect('small_image') 
                        ->addAttributeToSelect('type_id')                                                                       
                        ->addAttributeToFilter(                        
                            array(
                                array('attribute'=>'name','like' => '%'.Mage::app()->getRequest()->getParam('q').'%'),
                                array('attribute'=>'description', 'like' => '%'.Mage::app()->getRequest()->getParam('q').'%'),
                                array('attribute'=>'short_description', 'like' => '%'.Mage::app()->getRequest()->getParam('q').'%'),                                                                                                                                               
                            )
                        )
                        ->addAttributeToFilter(
                            'status', array('eq'=>'1')
                        )
                        ->addAttributeToFilter(
                            'visibility', array('neq' => '1')
                            
                        )
                        ->setOrder(
                            $sort_by,$order
                        )
                        ->setPageSize(Mage::getStoreConfig('ajaxsearch/preview/number_product'));            
                                             
            $product_results->load();
            return $product_results;
		}
    }

    public function getResultCount()
    {
        if (!$this->getData('result_count')) {
            $size = $this->_getProductCollection()->getSize();
            $this->_getQuery()->setNumResults($size);
            $this->setResultCount($size);
        }
        return $this->getData('result_count');
    }
    public function getNoResultText()
    {
        if (Mage::helper('catalogsearch')->isMinQueryLength()) {
            return Mage::helper('catalogsearch')->__('Minimum Search query length is %s', $this->_getQuery()->getMinQueryLength());
        }
        return $this->_getData('no_result_text');
    }
    public function getNoteMessages()
    {
        return Mage::helper('catalogsearch')->getNoteMessages();
    }
}
