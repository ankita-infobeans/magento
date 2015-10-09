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
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product list
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Cmsmart_Ajaxsearch_Block_Product_List extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'catalog/product_list_toolbar';

    /**
     * Product Collection
     *
     * @var Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected $_productCollection;

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
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
                        );            
                                             
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
                        );            
                                             
            $product_results->load();
            return $product_results;
		}
    }

    /**
     * Get catalog layer model
     *
     * @return Mage_Catalog_Model_Layer
     */
    public function getLayer()
    {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        return Mage::getSingleton('catalog/layer');
    }

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getLoadedProductCollection()
    {
        return $this->_getProductCollection();
    }

    /**
     * Retrieve current view mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->getChild('toolbar')->getCurrentMode();
    }

    /**
     * Need use as _prepareLayout - but problem in declaring collection from
     * another block (was problem with search result)
     */
    protected function _beforeToHtml()
    {
        $toolbar = $this->getToolbarBlock();

        // called prepare sortable parameters
        $collection = $this->_getProductCollection();

        // use sortable parameters
        if ($orders = $this->getAvailableOrders()) {
            $toolbar->setAvailableOrders($orders);
        }
        if ($sort = $this->getSortBy()) {
            $toolbar->setDefaultOrder($sort);
        }
        if ($dir = $this->getDefaultDirection()) {
            $toolbar->setDefaultDirection($dir);
        }
        if ($modes = $this->getModes()) {
            $toolbar->setModes($modes);
        }

        // set collection to toolbar and apply sort
        $toolbar->setCollection($collection);

        $this->setChild('toolbar', $toolbar);
        Mage::dispatchEvent('catalog_block_product_list_collection', array(
            'collection' => $this->_getProductCollection()
        ));

        $this->_getProductCollection()->load();

        return parent::_beforeToHtml();
    }

    /**
     * Retrieve Toolbar block
     *
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    public function getToolbarBlock()
    {
        if ($blockName = $this->getToolbarBlockName()) {
            if ($block = $this->getLayout()->getBlock($blockName)) {
                return $block;
            }
        }
        $block = $this->getLayout()->createBlock($this->_defaultToolbarBlock, microtime());
        return $block;
    }

    /**
     * Retrieve additional blocks html
     *
     * @return string
     */
    public function getAdditionalHtml()
    {
        return $this->getChildHtml('additional');
    }

    /**
     * Retrieve list toolbar HTML
     *
     * @return string
     */
    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }

    public function setCollection($collection)
    {
        $this->_productCollection = $collection;
        return $this;
    }

    public function addAttribute($code)
    {
        $this->_getProductCollection()->addAttributeToSelect($code);
        return $this;
    }

    public function getPriceBlockTemplate()
    {
        return $this->_getData('price_block_template');
    }

    /**
     * Retrieve Catalog Config object
     *
     * @return Mage_Catalog_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('catalog/config');
    }

    /**
     * Prepare Sort By fields from Category Data
     *
     * @param Mage_Catalog_Model_Category $category
     * @return Mage_Catalog_Block_Product_List
     */
    public function prepareSortableFieldsByCategory($category) {
        if (!$this->getAvailableOrders()) {
            $this->setAvailableOrders($category->getAvailableSortByOptions());
        }
        $availableOrders = $this->getAvailableOrders();
        if (!$this->getSortBy()) {
            if ($categorySortBy = $category->getDefaultSortBy()) {
                if (!$availableOrders) {
                    $availableOrders = $this->_getConfig()->getAttributeUsedForSortByArray();
                }
                if (isset($availableOrders[$categorySortBy])) {
                    $this->setSortBy($categorySortBy);
                }
            }
        }

        return $this;
    }
}
