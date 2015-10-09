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

class Cmsmart_Ajaxsearch_Helper_Data extends Mage_Core_Helper_Abstract
{

	public function getCategoriesDropdown() {
		$categoriesArray = Mage::getModel('catalog/category')
			->getCollection()
			->addAttributeToSelect('name')
			->addAttributeToSort('path','asc')
			->addFieldToFilter('is_active', array('eq'=>'1'))
			->load()
			->toArray();
		foreach ($categoriesArray as $categoryId => $category) {
					if (isset($category['name']) & $categoryId > 3) {
						$categories[] = array(
						'label' => $category['name'],
						'level'  =>$category['level'],
						'value' => $categoryId
						);
					}
		}
		return $categories;
	}
			
	public function buildCategoriesMultiselectValues(Varien_Data_Tree_Node $node, $values, $level = 0)
    {
    	$level++;
    
    	$values[$node->getId()]['value'] =  $node->getId();
		if($level > 3){
			$values[$node->getId()]['label'] = str_repeat("-", $level) . $node->getName();
		} else {
			$values[$node->getId()]['label'] = $node->getName();
		}
    	foreach ($node->getChildren() as $child)
    	{
    		$values = $this->buildCategoriesMultiselectValues($child, $values, $level);
    	}
    
    	return $values;
    }
    
    public function load_tree()
    {
    	$store = Mage::app()->getFrontController()->getRequest()->getParam('store', 0);
    	$parentId = $store ? Mage::app()->getStore($store)->getRootCategoryId() : 1;  // Current store root category
    	$tree = Mage::getResourceSingleton('catalog/category_tree')->load();
    	$root = $tree->getNodeById($parentId);
    	$collection = Mage::getModel('catalog/category')->getCollection()
    	->setStoreId($store)
    	->addAttributeToSelect('name')
    	->addAttributeToSelect('is_active');
    
    	$tree->addCollectionData($collection, true);
		
			return $this->buildCategoriesMultiselectValues($root, array());
		
    }
    public function getSuggestUrl()
    {
        return $this->_getUrl('ajaxsearch/suggest/result', array(
            '_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()
        ));
    }
  
    public function getQuery() {
        $query = Mage::app()->getRequest()->getParam('q');
        return $query;  
    }
	public function getSearchcategories() {
        $productids = Mage::app()->getRequest()->getParam('search_categories');
		foreach ($productids as $k => $cat) { $i++;
					$Searchcategories .= 'search_categories[]='.$cat.'&';
				}
        return $Searchcategories;  
    }
    public function getStyle()
    {       
        $width_meta_data = Mage::getStoreConfig('ajaxsearch/settings/width') - Mage::getStoreConfig('ajaxsearch/preview/image_width') - 25;
        $style='
        <style>
            .search-autocomplete { 
                width:  '. Mage::getStoreConfig('ajaxsearch/settings/width') .'px; 
                border-color: '. Mage::getStoreConfig('ajaxsearch/settings/border_color') .' ;
                border-width: '. Mage::getStoreConfig('ajaxsearch/settings/border_width') .'px;
                border-style: solid;
                                        
            }
                            
            .search-autocomplete .suggested a {
                color: '. Mage::getStoreConfig('ajaxsearch/suggest/suggest_color') .';
            }
            
            .search-autocomplete .suggested a span {
                color: '. Mage::getStoreConfig('ajaxsearch/suggest/count_color') .';
            }
            
            .search-autocomplete .suggested a:hover {
                color: '. Mage::getStoreConfig('ajaxsearch/suggest/hover_color') .';
            }
            
            .search-autocomplete .ajaxsearch .preview .title {
                color: '. Mage::getStoreConfig('ajaxsearch/preview/pro_title_color') .';
            }
            
            .search-autocomplete .ajaxsearch .preview .product_cat {
                color: '. Mage::getStoreConfig('ajaxsearch/preview/pro_cat_color') .';
            }
            
            .search-autocomplete .ajaxsearch .preview .product_cat a:hover {
                color: '. Mage::getStoreConfig('ajaxsearch/preview/pro_cat_hover_color') .';
            }
            
            .search-autocomplete .ajaxsearch .preview .description {
                color: '. Mage::getStoreConfig('ajaxsearch/preview/pro_description_color') .';
            }
            
            .search-autocomplete .ajaxsearch .preview .price, .search-autocomplete .ajaxsearch .preview .special-price  {
                color: '. Mage::getStoreConfig('ajaxsearch/preview/pro_price_color') .';
                font-weight: normal;
            }
            
            .search-autocomplete .ajaxsearch .preview .regular-price, .search-autocomplete .ajaxsearch .preview .regular-price .price  {
                color: '. Mage::getStoreConfig('ajaxsearch/preview/pro_old_price_color') .';
            }
              
            .search-autocomplete .ajaxsearch img {
                border: '. Mage::getStoreConfig('ajaxsearch/preview/image_border_width') .'px solid '. Mage::getStoreConfig('ajaxsearch/preview/image_border_color') .';            
            }
            
            .search-autocomplete .category .preview .title {
                color: '. Mage::getStoreConfig('ajaxsearch/category/cat_title_color') .';
            }
            
            .search-autocomplete .category .preview .description {
                color: '. Mage::getStoreConfig('ajaxsearch/category/cat_description_color') .';    
            }
            
            .search-autocomplete .page_rs .preview .title {
                color: '. Mage::getStoreConfig('ajaxsearch/page/page_title_color') .';
            }
            
            .search-autocomplete .page_rs .preview .description {
                color: '. Mage::getStoreConfig('ajaxsearch/page/page_description_color') .';
            } 
              
            .search-autocomplete .no-results {
                color: '. Mage::getStoreConfig('ajaxsearch/settings/noti_color') .';    
            }
            
            .search-autocomplete .ajaxsearch .reviews_count { 
                color: '. Mage::getStoreConfig('ajaxsearch/preview/pro_countreviews_color') .';
            }   
                           
            .search-autocomplete span.highlight { font-size: '. Mage::getStoreConfig('ajaxsearch/settings/highlight_size') .'; color: '. Mage::getStoreConfig('ajaxsearch/settings/highlight_color') .';}
            .search-autocomplete li.title { background: '. Mage::getStoreConfig('ajaxsearch/settings/title_bg') .'; color: '. Mage::getStoreConfig('ajaxsearch/settings/title_color') .' }
            .search-autocomplete li.preview:hover, .search-autocomplete li.suggest:hover { background: '. Mage::getStoreConfig('ajaxsearch/settings/hover_bg') .';}
            .search-autocomplete .meta_data { width: '. $width_meta_data .'px}
            .ui-state-default,.ui-widget-content .ui-state-default,.ui-widget-header .ui-state-default{ background: '.Mage::getStoreConfig('ajaxsearch/searchbycategory/backgroundcategory').';}
			.ui-state-hover,.ui-widget-content .ui-state-hover,.ui-widget-header .ui-state-hover,.ui-state-focus,.ui-widget-content .ui-state-focus,.ui-widget-header .ui-state-focus, .ui-state-active {
				background: '.Mage::getStoreConfig('ajaxsearch/searchbycategory/backgroundcategoryhover').';
			}
			.ui-state-active,.ui-widget-content .ui-state-active,.ui-widget-header .ui-state-active,
			.ui-state-active a,.ui-state-active a:link,.ui-state-active a:visited{color:'.Mage::getStoreConfig('ajaxsearch/searchbycategory/colorstateactive').'}
			.ui-widget-header {background:'.Mage::getStoreConfig('ajaxsearch/searchbycategory/backgroundheader').'}

            </style>';
            
        return $style;
    }
    public function price($_product)
    {
        if ($_product->type_id != 'grouped') return Mage::helper('core')->currency($_product->getFinalPrice());
        else  return Mage::helper('core')->currency($_product->min_price);
    }
    
    public function priceOld($_product)
    {
        return Mage::helper('core')->currency($_product->getPrice());
    }
    
    public function highlight($string, $query, $limit) 
    {
        $string = strip_tags($string);
        if(stripos($string,$query) !== false) 
        {     
            
            $string_bf = stristr($string, $query,true) ;
            $string_af = trim(stristr($string, $query,false));     
            $string_before = substr($string_bf, -($limit/2)) ; 
            $string_after = substr($string_af, 0, ($limit/2)) ;           
            if(stripos($string_before," ") !== false && strlen($string_bf) > ($limit/2)){
    
                $new_string= stripos($string_before," ");    
                $string_before = '...'.substr($string_before,$new_string);
                
            }          
            if(strripos($string_after," ") !== false && strlen($string_af) > ($limit/2)){
    
                $new_string= strripos($string_after," ");    
                $string_after = substr($string_after, 0, $new_string).'...';               
            }            
            $string = $string_before.$string_after ;                
            return $string;                        
        }        
        else
        {            
            if(strlen($string) > ($limit/2) )
            {
                $string = substr($string,0,($limit/2));
                if(strripos($string," ") !== false)
                {
                    $string = substr($string,0,strripos($string," "));
                }
                $string = $string . '...';
                return $string;    
            }
            else
            {
                return $string;
            }
                
        }
        
    }
    
    const STR_HIGHLIGHT_SIMPLE = 1;    
    const STR_HIGHLIGHT_WHOLEWD = 2;    
    const STR_HIGHLIGHT_CASESENS = 4;    
    const STR_HIGHLIGHT_STRIPLINKS = 8;
    
    public function str_highlight($text, $needle, $options = null, $highlight = null)
    {
        // Default highlighting
        if ($highlight === null) {
            $highlight = '<span class="highlight">\1</span>';
        }
     
        // Select pattern to use
        if ($options & STR_HIGHLIGHT_SIMPLE) {
            $pattern = '#(%s)#';
            $sl_pattern = '#(%s)#';
        } else {
            $pattern = '#(?!<.*?)(%s)(?![^<>]*?>)#';
            $sl_pattern = '#<a\s(?:.*?)>(%s)</a>#';
        }
     
        // Case sensitivity
        if (!($options & STR_HIGHLIGHT_CASESENS)) {
            $pattern .= 'i';
            $sl_pattern .= 'i';
        }
     
        $needle = (array) $needle;
        foreach ($needle as $needle_s) {
            $needle_s = preg_quote($needle_s);
     
            // Escape needle with optional whole word check
            if ($options & STR_HIGHLIGHT_WHOLEWD) {
                $needle_s = '\b' . $needle_s . '\b';
            }
     
            // Strip links
            if ($options & STR_HIGHLIGHT_STRIPLINKS) {
                $sl_regex = sprintf($sl_pattern, $needle_s);
                $text = preg_replace($sl_regex, '\1', $text);
            }
     
            $regex = sprintf($pattern, $needle_s);
            $text = preg_replace($regex, $highlight, $text);
        }
     
        return $text;
    }
	 
}
    


