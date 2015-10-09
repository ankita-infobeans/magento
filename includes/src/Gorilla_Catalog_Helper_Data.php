<?php
 
class Gorilla_Catalog_Helper_Data extends Mage_Core_Helper_Abstract {
	const ALL_GROUPS = Mage_Customer_Model_Group::CUST_GROUP_ALL;
	const MEMBER_GROUP = 2; 

	public function buildSubscriptionArray($configProd) {
		$children = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$configProd); 
		$results = array();

		$attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'subscription_seats'); 
		$seatsArray = $attribute->getSource()->getAllOptions(true, true);

		$attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'subscription_duration'); 
		$durationArray = $attribute->getSource()->getAllOptions(true, true);

		foreach($children as $child) {
			$child = Mage::getModel('catalog/product')->load($child->getId());
			$tierPrices = $child->getData('tier_price');

			$nonMemberPrice = '';
			$memberPrice = '';
			if (sizeof($tierPrices)) {
				$nonMemberPrice = $this->findPrice($tierPrices, self::ALL_GROUPS, 1);
				$memberPrice = $this->findPrice($tierPrices, self::MEMBER_GROUP, 1);
			}
			if ($nonMemberPrice == '') $nonMemberPrice = $child->getPrice();
			if ($memberPrice == '') $memberPrice = $child->getPrice();

			$seats = $child->getAttributeText('subscription_seats');
			$seatsNdx = $this->getAttributeSort($seatsArray, $child->getSubscriptionSeats());
			$duration = $child->getAttributeText('subscription_duration');
			$durationNdx = $this->getAttributeSort($durationArray, $child->getSubscriptionDuration());

				

			if (!array_key_exists($durationNdx, $results)) $results[$durationNdx] = array('title'=>$duration, 'children'=>array());
			
			$results[$durationNdx]['children'][$seatsNdx] = array(
				'title' => $seats,
				'non_member_price' => $nonMemberPrice,
				'member_price' => $memberPrice,
				'savings_price' => $nonMemberPrice - $memberPrice
			);
		}

		ksort($results);
		foreach($results as $key => $value) {
			ksort($results[$key]['children']);
		}
	
		return $results;
	}

	private function findPrice($tierPrices, $group, $qty) {
		foreach($tierPrices as $tierPrice) {
			if ($tierPrice['cust_group'] == $group && $tierPrice['price_qty'] == $qty) {
				return $tierPrice['website_price'];
			}
		}
	}

	private function getAttributeSort($attributes, $id) {
		for($i = 0; $i < sizeof($attributes); $i++)
			if ($attributes[$i]['value'] == $id) 
				return $i;	
	}
}