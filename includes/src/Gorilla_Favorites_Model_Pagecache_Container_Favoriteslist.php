<?php
class Gorilla_Favorites_Model_Pagecache_Container_Favoriteslist extends Enterprise_PageCache_Model_Container_Abstract
{
    protected function _getCacheId()
    {
        $id         = $this->_placeholder->getAttribute('cache_id');
        $container  = $this->_placeholder->getAttribute('container');
        $block      = $this->_placeholder->getAttribute('block');
        $template   = $this->_placeholder->getAttribute('template');
        $prod_count = $this->_placeholder->getAttribute('product_count');
        $col_count  = $this->_placeholder->getAttribute('column_count');
        $category   = $this->_placeholder->getAttribute('category');
        $category   = ($category==false) ? 'notcat' : $category;
        if ($id) {
            //$ip = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') ? '75.146.115.137' : $_SERVER['REMOTE_ADDR'];
            $key = 'FAVORITES_LIST';
            $key.= ' container="'.$container.'"';
            $key.= ' block="'.$block.'"';
            $key.= ' cache_id="'.$id.'"';
            $key.= ' template="'.$template.'"';
            $key.= ' prod_count="'.$prod_count.'"';
            $key.= ' col_count="'.$col_count.'"';
            $key.= ' category="'.$category.'"';
            $key = 'FAVORITES_LIST_'.md5($key);
            return $key;
        }
        return false;
    }
    
    protected function _saveCache($data, $id, $tags = array(), $lifetime = null) {
        return $this;
    }
 
    protected function _renderBlock() {
        //parent::_renderBlock();
        $blockClass = $this->_placeholder->getAttribute('block');
        $template = $this->_placeholder->getAttribute('template');
        
        $block = new $blockClass;
        $block->setTemplate($template);
        $block->setLayout(Mage::app()->getLayout());
        
        $block->setColumnCount($this->_placeholder->getAttribute('column_count'));
        $block->setProductCount($this->_placeholder->getAttribute('product_count'));
        $block->setCategoryId($this->_placeholder->getAttribute('category'));
        
        return $block->toHtml();
    }
    
    /**
     * Generate block content
     * @param $content
     */
//    public function applyInApp(&$content)
//    {
//        $block = $this->_placeholder->getAttribute('block');
//        $template = $this->_placeholder->getAttribute('template');
//        $block = new $block;
//        $block->setTemplate($template);
//        $blockContent = $block->toHtml();
//        $cacheId = $this->_getCacheId();
//        if ($cacheId) {
//            $this->_saveCache($blockContent, $cacheId);
//        }
//        $this->_applyToContent($content, $blockContent);
//        return true;
//    }
}
?>