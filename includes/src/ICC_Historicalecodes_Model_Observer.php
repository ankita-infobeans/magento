<?php
class ICC_Historicalecodes_Model_Observer extends Mage_Core_Model_Abstract {
	
	public function modelLoadAfter($observer) {
		// Mage::Log(get_class($observer->object));
		if (get_class ( $observer->object ) == "ICC_Orderfiltering_Model_Sales_Order") {
			//Mage::Log ( get_class ( $observer->object ) . " " . $observer->value );
			//
			
			// $order_id = $observer->object->entity_id;
			// $order = $observer->object;
			// $order = Mage::getSingleton('sales/order')->load($order_id);
			// $order->setOldOrderIdA("asdfASDFasdfASDF");
			// $order->setOldOrderIdB("thisisatest");
			// $order->save();
			// Mage::Log(print_r($order->debug(),true));
		}
	
	}
	public function collectionLoad($observer) {
		
		$collection = $observer->collection;
		if (get_class ( $collection ) == "Mage_Sales_Model_Resource_Order_Grid_Collection") {
			
			$filter = Mage::app ()->getRequest ()->getParams ();
			$doSearch = false;
			$filtervaluea = "";
			$filtervalueb = "";
			
			if (isset ( $filter ['filter'] )) {
				$filter = $filter ['filter'];
				$filter_data = Mage::helper ( 'adminhtml' )->prepareFilterString ( $filter );
				if (isset ( $filter_data ['old_order_id_a'] ) || isset ( $filter_data ['old_order_id_b'] )) {
					
					if(isset($filter_data ['old_order_id_a']))
					{
						$filtervaluea = $filter_data ['old_order_id_a'];
					}
					if(isset($filter_data ['old_order_id_b']))
					{
						$filtervalueb =$filter_data ['old_order_id_b'];
					}
					$doSearch = true;
				}
			}
			
			$newCollection = new Mage_Sales_Model_Resource_Order_Grid_Collection ();
			
			foreach ( $collection as $c ) {

				$id = $c->getEntityId ();

				$order = Mage::getModel ( 'sales/order' )->load ( $id );
				$c->setOldOrderIdA ( $order->getOldOrderIdA () );
				$c->setOldOrderIdB ( $order->getOldOrderIdB () );
				
				$keep = true;
				
				$check = $c->getOldOrderIdA();

				if (stripos ( $check, $filtervaluea ) === false) {
				}else{
					$keep = true;
				}
				
				$check = $c->getOldOrderIdB();
				if (stripos ( $check, $filtervalueb ) === false) {
				}else{
					$keep = true;
				}
				
				if (! $keep && $doSearch) {
					$collection->removeItemByKey ( $id );
				}
			}
		}
	}
	
	public function addFilter($observer) {
		
		//$type = $observer->getEvent ()->getBlock ()->getType ();
		//if ($type == "adminhtml/sales_order_grid") {
	//		$block = $observer->getEvent ()->getBlock ();
//			$block->addColumn ( 'old_order_id_a', array ('header' => 'Older Order Id A', 'index' => 'old_order_id_a', 'type' => 'text', 'width' => '150px' ) );
//			$block->addColumn ( 'old_order_id_b', array ('header' => 'Older Order Id B', 'index' => 'old_order_id_b', 'type' => 'text', 'width' => '150px' ) );
//		}
	}

}