<?php
//trial 1
//trial 2
//trial 3
class ICC_Catalogrequest_Block_Request extends Mage_Core_Block_Template {
	
	
	
	
	public function getSaveUrl()
	{
		return Mage::getUrl('catalogrequest/form/submit')."?___store";
		
		//return 'http://local.iccsafe.org/catalogrequest/form/submit';
	}
	
	
	//
	public function getCountryHtmlSelect($name, $id, $value = null, $class = '')
	{
		$options = $this->_getCountryOptions();
		return $this->getSelectHtml($name, $id, $options, "US", $class);
	}


	protected function _getCountryOptions()
	{
		$options  = false;
		$useCache = Mage::app()->useCache('config');
		if ($useCache) {
			$cacheId = 'DIRECTORY_COUNTRY_SELECT_STORE_' . Mage::app()->getStore()->getCode();
			$cacheTags = array('config');
			if ($optionsCache = Mage::app()->loadCache($cacheId)) {
				$options = unserialize($optionsCache);
			}
		}
	
		if ($options == false) {
			$options = $this->_getCountryCollection()->toOptionArray();
			if ($useCache) {
				Mage::app()->saveCache(serialize($options), $cacheId, $cacheTags);
			}
		}
		return $options;
	}
	
	
	private function _getCountryCollection()
	{
		return Mage::getSingleton('directory/country')->getResourceCollection()
		->loadByStore();
	}

	public function getSelectHtml($name, $id, $options = array(), $value = null, $class = '')
	{
		$select = $this->getLayout()->createBlock('core/html_select')
		->setName($name)
		->setId($id)
		->setClass('select ' . $class)
		->setValue($value)
		->setOptions($options);
		;
		return $select->getHtml();
	}

	
	
	
	

}