<?php
class Cmsmart_AjaxCart_Helper_AjaxCart extends Mage_Core_Helper_Abstract
{
    const XML_PATH_QUICK     = 'ajaxcart/viewsetting/enableview';

	public function getAjaxCart()
    {
        return Mage::getStoreConfig(self::XML_PATH_QUICK);
    }
}