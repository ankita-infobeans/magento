<?php
/*____________________________________________________________________

* Name Extension: Magento Ajaxsearch Autocomplete And Suggest

* Author: The Cmsmart Development Team 

* Date Created: 2013

* Websites: http://cmsmart.net

* Technical Support: Forum - http://cmsmart.net/support

* GNU General Public License v3 (http://opensource.org/licenses/GPL-3.0)

* Copyright © 2011-2013 Cmsmart.net. All Rights Reserved.

______________________________________________________________________*/
?>
<?php
class Cmsmart_Ajaxsearch_Block_Suggest extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {           
		return parent::_prepareLayout();
    }
    
     public function getAjaxsearch()     
     { 
        if (!$this->hasData('ajaxsearch')) {
            $this->setData('ajaxsearch', Mage::registry('ajaxsearch'));
        }
        return $this->getData('ajaxsearch');
     }

     public function getSuggestProducts()     
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
      
    public function getSuggestProductsAll()     
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
									'status', array('eq'=>'1')
								)
								->addAttributeToFilter(
									'visibility', array('neq' => '1')                           
								)
								->addAttributeToFilter('category_id', array($ctf))
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
      
    public function getSuggestCategories() 
    {
        $category_results = Mage::getModel('catalog/category')->getCollection();
        $category_results->addAttributeToSelect('name')
                        ->addAttributeToSelect('description')
                        ->addAttributeToFilter(                        
                            array(
                                array('attribute'=>'name','like'=>'%'.Mage::app()->getRequest()->getParam('q').'%'),
                                array('attribute'=>'description', 'like'=>'%'.Mage::app()->getRequest()->getParam('q').'%'),                               
                            )
                        )
                        ->addAttributeToFilter(
                            'is_active', array('eq'=>'1')
                        );
        return $category_results;                       
    }
    
    public function getSuggestCatDes() 
    {
        $category_results = Mage::getModel('catalog/category')->getCollection();
        $category_results->addAttributeToSelect('title')
						 ->addAttributeToSelect('description')                        
						 ->addAttributeToFilter('description', array('like' => '%'.Mage::app()->getRequest()->getParam('q').'%'));
        return $category_results;
    }
            
    public function getSuggestPage() {       
        $resource = Mage::getSingleton('core/resource');
         
        $readConnection = $resource->getConnection('core_read');
                 
        $query = 'SELECT title, identifier, content FROM ' . $resource->getTableName('cms_page') . ' WHERE is_active = 1 AND( title LIKE \'%' . Mage::app()->getRequest()->getParam('q') .'%\' OR content LIKE \'%' . Mage::app()->getRequest()->getParam('q') .'%\')';        
        
        $results = $readConnection->fetchAll($query);                
        
        return $results;
        
    }
    
    public function getCat($product_id) {
        $product = Mage::getModel('catalog/product')->load($product_id);
        $cats = $product->getCategoryIds();
        foreach($cats as $category_id) {
            $cat = Mage::getModel('catalog/category')->load($category_id);
            $result = $cat->getName();
            return $result;
        }
        
    }
    
    public function getAdditionalData($pro)
    {
        $data = array();        
        $attributes = $pro->getAttributes();        
        foreach ($attributes as $attribute) {
            if ($attribute->getIsVisibleOnFront()) {               
                $value = $attribute->getFrontend()->getValue($pro);
    
                if (!$pro->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

                if (is_string($value) && strlen($value)) {
                    $data[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code'  => $attribute->getAttributeCode()
                    );
                }
            }
        }
        return $data;
    }
              
     public function enabledSuggest()     
     {
        return Mage::getStoreConfig('ajaxsearch/suggest/enable');
     }
    
    public function  enabledSearchByAtt()
    {
        return Mage::getStoreConfig('ajaxsearch/settings/search_by_att');
    }
    
     public function enabledPreview()     
     {
        return Mage::getStoreConfig('ajaxsearch/preview/enable');
     }
     
     public function enabledCat()     
     {
        return Mage::getStoreConfig('ajaxsearch/category/enable');
     }
     
     public function enabledPage()     
     {
        return Mage::getStoreConfig('ajaxsearch/page/enable');
     }
    
     public function getImageWidth()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/image_width');
     }

     public function getImageHeight()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/image_height');
     }
     public function getEffect()
     {
        return Mage::getStoreConfig('ajaxsearch/settings/effect');
     }

     public function getPreviewBackground()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/background');
     }

     public function getSuggestBackground()
     {
        return Mage::getStoreConfig('ajaxsearch/suggest/background');
     }

     public function getSuggestColor()
     {
        return Mage::getStoreConfig('ajaxsearch/suggest/suggest_color');
     }

     public function getSuggestCountColor()
     {
        return Mage::getStoreConfig('ajaxsearch/suggest/count_color');
     }

     public function getBorderColor()
     {
        return Mage::getStoreConfig('ajaxsearch/settings/border_color');
     }

     public function getBorderWidth()
     {
        return Mage::getStoreConfig('ajaxsearch/settings/border_width');
     }

     public function isShowImage()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/show_image');
     }

     public function isShowName()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/show_name');
     }
     
     public function isShowCat()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/show_cat');
     }
     
     public function isShowRating()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/show_rating');
     }
     
     public function isShowCountReviews()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/show_countreviews') ;  
     }
     
     public function getProductNameColor()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/pro_title_color');
     }

     public function getProductDescriptionColor()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/pro_description_color');
     }
     
     public function isShowAttribute()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/show_attribute');
     }

     public function isShowDescription()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/show_description');
     }
     
     public function isShowPrice()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/show_price');
     }

     public function isShowViewall ()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/show_viewall');
     }
     
     public function getNumDescriptionChar()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/num_char_description');
     }


     public function getImageBorderWidth()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/image_border_width');
     }
     public function getImageBorderColor()
     {
        return Mage::getStoreConfig('ajaxsearch/preview/image_border_color');
     }

     public function getHoverBackground()
     {
        return Mage::getStoreConfig('ajaxsearch/settings/hover_bg');
     }
     
     public function getWidth()
     {
        return Mage::getStoreConfig('ajaxsearch/settings/width');
     }
          
}
