<?php
/**
 * Created by Ariel Allon @ Gorilla
 *    aallon@gorillagroup.com
 * Creation date: 9/24/12 10:03 AM
 */
class ICC_Debugging_Model_Pagecache_Container_Ip extends Enterprise_PageCache_Model_Container_Abstract
{
    protected function _getCacheId()
    {
        $id         = $this->_placeholder->getAttribute('cache_id');
        $container  = $this->_placeholder->getAttribute('container');
        $block      = $this->_placeholder->getAttribute('block');
        $template   = $this->_placeholder->getAttribute('template');

        if ($id) {
            $key = 'DEBUGGING_IP';
            $key.= ' container="'.$container.'"';
            $key.= ' block="'.$block.'"';
            $key.= ' cache_id="'.$id.'"';
            $key.= ' template="'.$template.'"';

            $key = 'DEBUGGING_IP_'.md5($key);
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

        return $block->toHtml();
    }
}
