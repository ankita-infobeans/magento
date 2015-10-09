<?php

class Cmsmart_Ajaxsearch_Model_Processor
{
    const CACHE_TAG      = 'BLOCK_HTML';
    const CACHE_LIFETIME = 604800;

    protected $_cacheId = null;

    public function __construct()
    {
        $key = false;
        if (!empty($_SERVER['REQUEST_URI'])) {
            $key.= $_SERVER['REQUEST_URI'];
        }

        if ($key) {
            if (isset($_COOKIE['store'])) {
                $key = $key.'_'.$_COOKIE['store'];
            }
            if (isset($_COOKIE['currency'])) {
                $key = $key.'_'.$_COOKIE['currency'];
            }
        }

        $this->_cacheId  = 'SEARCHAUTOCOMPLETE_'.md5($key);
    }


    public function extractContent()
    {
        $content = Mage::app()->loadCache($this->_cacheId);

        return $content;
    }

    public function cacheResponse(Varien_Event_Observer $observer)
    {
        $frontController = $observer->getEvent()->getFront();
        $request = $frontController->getRequest();
        
        if ($request->getControllerModule() == 'Cmsmart_Ajaxsearch') {
            $response = $frontController->getResponse();

            $content = Mage::app()->saveCache($response->getBody(), $this->_cacheId, array(self::CACHE_TAG), self::CACHE_LIFETIME);
        }
    }
}