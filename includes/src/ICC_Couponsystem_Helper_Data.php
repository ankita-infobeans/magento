<?php
/**
 * ICC_Couponsystem extension
 * 
 * @category       ICC
 * @package        ICC_Couponsystem
 */
class ICC_Couponsystem_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getUniqRulesNamesList($dateField='', $from='', $to='') {
		$time = time();
		$to = date('Y-m-d H:i:s', $time);
		$lastTime = $time - 86400; // 60*60*24
		$from = date('Y-m-d H:i:s', $lastTime);
	    $collection = Mage::getModel('sales/order')->getCollection()
			 ->addFieldToSelect('coupon_code')->distinct(true);
if ($type != '') {
			//$collection->addFieldToFilter('main_table.created_at', array('from' => '2011-12-03 00:00:00', 'to' => $to));
 $collection ->addFieldToFilter($dateField, array(
                        'from' => $from,
                        'to' => $to,
                        'date' => true
                    ));
}
			$collection->addFieldToFilter('main_table.coupon_code', array('notnull' => true));
		$collection->getSelect()
		    ->join(array('sc' => 'salesrule_coupon'), 'main_table.coupon_code=sc.code' , array());
		  $collection->getSelect()
		    ->join(array('ce3' => 'salesrule'), 'sc.rule_id=ce3.rule_id', array('name' => 'name'))
		    ->where('ce3.name != ""')
			 ->group('ce3.rule_id');
		 
		   $name = array();
		    foreach ($collection as $coll) {
			$name[] = $coll->getName();
		    }
return $name;
	
	}

}
