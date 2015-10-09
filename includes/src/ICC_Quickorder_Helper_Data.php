<?php

class ICC_Quickorder_Helper_Data extends Mage_Core_Helper_Abstract {
	
	public function addItemToQuickorder($sku, $qty) {
		$currentItems = $this->getQuickorderItems ();

        $sku = trim(strtolower($sku));

		if(!is_array($currentItems)) {
		    $currentItems = array();
		}

        // Add item if pre-existing
        foreach($currentItems as $i => $item) {
            if($item['sku'] == $sku) {
                $currentItems[$i]['qty'] += $qty;
                return $this->setQuickOrderItems ( $currentItems );
            }
        }

        $currentItems[] = array('sku' => $sku, 'qty' => $qty);

		return $this->setQuickOrderItems ( $currentItems );
	}
	
	public function setQuickOrderItems($data) {
		$data = Zend_Json::encode ( $data );
		Mage::getSingleton ( 'core/session' )->setQuickOrder ( $data );
		return $this;
	}
	
	public function getQuickorderItems() {
		$data = Mage::getSingleton ( 'core/session' )->getQuickOrder ();
		$newdata = Zend_Json::decode ( $data, Zend_Json::TYPE_ARRAY );
		return $newdata;
	}
	
	public function resetQuickorder() {
		Mage::getSingleton ( 'core/session' )->setQuickOrder ('[]');
	}
	
	public function removeQuickorderItem($ndx) {
		$data = Mage::getSingleton ( 'core/session' )->getQuickOrder ();
		$dataAry = Zend_Json::decode ( $data, Zend_Json::TYPE_ARRAY );
		unset ( $dataAry [$ndx] );
		$data = Zend_Json::encode ( $dataAry );
		Mage::getSingleton ( 'core/session' )->setQuickOrder ( $data );
	}
	
	public function prettyQuickorder() {
		$data = Mage::getSingleton ( 'core/session' )->getQuickOrder ();
		return $data;
	}

}

