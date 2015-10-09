<?php
class Gorilla_Placeholder_Model_Welcome_Container extends Enterprise_PageCache_Model_Container_Abstract
{

    private $_cachePrefix = 'ICC_Placeholder_Welcome_';
    /**
     * Redirect to content processing on new message
     *
     * @param string $content
     * @return bool
     */
    public function applyWithoutApp(&$content)
    {
        return false;
    }


    /**
     * Render block content
     *
     * @return string
     */
    protected function _renderBlock()
    {
        $header = Mage::app()->getLayout()->getBlock('header');
        if(false === $header){
            $header = Mage::app()->getLayout()->createBlock('page/html_header');
        }
        return $header->getWelcome();

    }
}