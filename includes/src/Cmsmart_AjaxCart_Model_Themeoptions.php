<?php
/**
 * @copyright  Copyright (c) 2010 Amasty (http://www.amasty.com)
 */  
class Cmsmart_AjaxCart_Model_Themeoptions extends Varien_Object
{
	 public function toOptionArray()
	 {
	    $hlp = Mage::helper('ajaxcart');
		return array(
	 		array('value' => 'default', 'label' => $hlp->__('Default Skin').'<br/>')
	// 		array('value' => 'blue', 'label' => $hlp->__('Blue Skin').'<br/>'),
	// 		array('value' => 'gray', 'label' => $hlp->__('Gray Skin').'<br/>'),
	// 		array('value' => 'orange', 'label' => $hlp->__('Orange Skin').'<br/>'),
	// 		array('value' => 'pink', 'label' => $hlp->__('Pink Skin').'<br/>'),
	// 		array('value' => 'red', 'label' => $hlp->__('Red Skin').'<br/>')
		);
	 }
}