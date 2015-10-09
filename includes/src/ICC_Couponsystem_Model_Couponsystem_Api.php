<?php
/**
 * ICC_Couponsystem extension
 * 
 * @category       ICC
 * @package        ICC_Couponsystem
 */
class ICC_Couponsystem_Model_Couponsystem_Api extends Mage_Api_Model_Resource_Abstract
{
    /**
     * get rule list
     * @access public
     * @param mixed $filters
     * @return json
     */
    public function items($createdDate){
        $collection = Mage::getModel('salesrule/rule')->getCollection()
		    ->addFieldToFilter('used_for_coupon_system', array('eq'=> 1))
                    ->addFieldToFilter('is_active', array('eq'=> 1));
        try {
            if ($createdDate) {
                $collection->getSelect()->columns(
                array(
                    'coupon_count' => new Zend_Db_Expr("(SELECT count(coupon_id) FROM salesrule_coupon WHERE rule_id = main_table.rule_id and created_at >= '".$createdDate."' )"
                )));
                $collection->getSelect()->where(new Zend_Db_Expr("(SELECT count(coupon_id) FROM salesrule_coupon WHERE rule_id = main_table.rule_id and created_at >= '".$createdDate."' ) >= 1"));
                Mage::log('select'.$collection->getSelect(), null, 'coupon.log' );
                $result = array();
                $i = 1;
                foreach ($collection as $couponsystem) {
                    $result['rules']['rule'][$i]['rule_id'] = $couponsystem->getRuleId();
                    $result['rules']['rule'][$i]['rule_name'] = $couponsystem->getName();
                    $result['rules']['rule'][$i]['no_of_coupons'] = $couponsystem->getCouponCount();
                    $i++;
                }
                return json_encode($result);
            } else {
                 throw new Exception("Date is invalid");
            }
        } catch(Exception $e) {
            throw new Exception("Date is invalid");
        } 
    }

    /**
     * get coupon list
     * @access public
     * @param int $ruleId
     * @return json
     */
    public function info($ruleId, $createdDate)
    {
        $result = array();
        try {
        if ($createdDate) {
        $coupons = Mage::getResourceModel('salesrule/coupon_collection')
            ->addFieldToFilter('rule_id', $ruleId)
            ->addFieldToFilter('created_at',  array(
                'created_at' => array(
                    'from' => $createdDate,
                )));
        Mage::log('select'.$coupons->getSelect(), null, 'coupon.log' );
        $result['rule_id'] = $ruleId;
        $coupon = Mage::getModel('salesrule/rule')->load($ruleId);
        $sku = ''; $name = '';
        $data = Mage::helper('amcoupons')->arrayDepth(unserialize($coupon->getConditionsSerialized()));
        if ($data['attribute'] == 'sku') {
	    $sku = $data['value'];
        }
        if ($sku == '') {
            $data = Mage::helper('amcoupons')->arrayDepth(unserialize($coupon->getActionsSerialized()));
            if ($data['attribute'] == 'sku') {
                $sku = $data['value'];
            }
        }
        $productDetails = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        if ($productDetails) {
            $name = $productDetails->getName();
        }
        $result['book_sku'] = $sku;
        $result['book_title'] = $name;
        $result['no_of_coupons'] = count($coupons);
        
        $i = 1;
        foreach ($coupons as $couponsystem) {
            $result['coupons']['coupon'][$i]['coupon_sku'] = $couponsystem->getCode();
            $result['coupons']['coupon'][$i]['no_of_uses'] = $couponsystem->getUsageLimit();
            $result['coupons']['coupon'][$i]['no_of_customers'] = $couponsystem->getUsagePerCustomer();
            $result['coupons']['coupon'][$i]['date'] = $couponsystem->getCreatedAt();
            $i++;
        } 
        return json_encode($result);
          }  else {
             throw new Exception("Date is invalid");
        }
        } catch(Exception $e) {
            throw new Exception("Date is invalid");
        } 
    }
}