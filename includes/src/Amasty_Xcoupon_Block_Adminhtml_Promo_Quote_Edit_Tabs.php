<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */ 
class Amasty_Xcoupon_Block_Adminhtml_Promo_Quote_Edit_Tabs extends Mage_Adminhtml_Block_Promo_Quote_Edit_Tabs
{
    protected function _beforeToHtml()
    {
        if (version_compare(Mage::getVersion(), '1.4.1.0') < 0) { 
            return parent::_beforeToHtml();
        }
        
        $this->addTab('coupons', array(
            'label'     => Mage::helper('amxcoupon')->__('Coupons List'),
            'class'     => 'ajax',
            'url'       => $this->getUrl('amxcoupon/adminhtml_coupon/index', array('_current' => true)),
        ));
        $this->addTab('import', array(
            'label'     => Mage::helper('amxcoupon')->__('Import Coupons'),
            'content'   => $this->getLayout()->createBlock('amxcoupon/adminhtml_import')->toHtml(), 
        ));
        
        $this->_updateActiveTab();
        return parent::_beforeToHtml();
    }
    
    protected function _updateActiveTab()
    {
    	$tabId = $this->getRequest()->getParam('tab');
    	if ($tabId) {
    		$tabId = preg_replace("#{$this->getId()}_#", '', $tabId);
    		if ($tabId) {
    			$this->setActiveTab($tabId);
    		}
    	}
    	else {
    	   $this->setActiveTab('main'); 
    	}
    }

}