<?php
    class ICC_Volumelicense_Model_Mysql4_Links_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
    {

	public function _construct(){
		$this->_init("volumelicense/links");
	}

	public function getRegistryRecord($rid) 
        {
		$this->getSelect()->where('registry_id = ' . (int)$rid);
		return $this->getFirstItem();
        }
        public function getRegistryId($rid) 
        {
		$this->getSelect()->where('registry_id = ' . (int)$rid);
		return $this;
        }

    }
	 
