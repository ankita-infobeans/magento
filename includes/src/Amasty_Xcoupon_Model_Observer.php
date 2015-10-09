<?php
/**
 * @copyright  Copyright (c) 2010 Amasty (http://www.amasty.com)
 */  
class Amasty_Xcoupon_Model_Observer
{
    public function handleCheckoutCartSaveAfter($observer)
    {
	$code = Mage::getSingleton('customer/session')->getCoupon();
	if (!$code)
	    return $this;
        
        $quote = $observer->getCart()->getQuote();
        try {
            $cnt = $quote->getItemsQty()*1;
            if ($cnt){
                $quote->setCouponCode($code)
                    ->collectTotals()
                    ->save();
            }
        }
        catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError('Cannot apply the coupon code, %s', $e->getMessage());
        }

        
        if (strpos($quote->getCouponCode(), $code) !== false){
            
    	    Mage::getSingleton('customer/session')->setCoupon(null);
        }
        
	    return $this;          
    }
    
	public function handleControllerActionPredispatch()
	{
	    if (isset($_GET['coupon'])){
	        $code = $_GET['coupon'];
	        $_GET['coupon'] = null;
	        
            $modelCoupon = Mage::getModel('salesrule/coupon')->load($code, 'code');
            if ($modelCoupon->getRuleId()){
                Mage::getSingleton('customer/session')->setCoupon($code);                
            }
	    }
	    return $this;   
	}

    public function handleSalesruleRuleDeleteAfter($observer)
    {
        if (version_compare(Mage::getVersion(), '1.4.1.0') < 0)
            return $this;  
            
        // there is no  key in the CE version, so we have to delete manually
        $rule = $observer->getEvent()->getRule(); 
        $table = Mage::getSingleton('core/resource')->getTableName('salesrule/coupon');
        $db    = Mage::getSingleton('core/resource')->getConnection('amxcoupon_write'); 
        $db->delete($table, 'rule_id = ' . intVal($rule->getId())); 
        
        return $this;   
    } 
    
    public function handleSalesruleRuleSaveAfter($observer)
    {
        if (version_compare(Mage::getVersion(), '1.4.1.0') < 0)
            return $this;          
        
        $rule = $observer->getEvent()->getRule();
        $this->_deleteCoupons($rule);
        if (!$this->_validateParams($rule)){
            return $this;
        }
        $this->_importCoupons($rule);
        $this->_generateCoupons($rule);
        
        return $this;
    }	    
     
    protected function _deleteCoupons($rule)
    {
    	//Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON is not defined in old versions
        $noCoupon = ($rule->getCouponType() == 1);
        $clearImport = Mage::app()->getRequest()->getParam('import_clear');
        
        if (!$noCoupon && !$clearImport){  
            return false;
        }
            
        try {
            $table = Mage::getSingleton('core/resource')->getTableName('salesrule/coupon');
            $db    = Mage::getSingleton('core/resource')->getConnection('amxcoupon_write'); 
            
            // add 'times_used = 0' part ?
            $cond =  'rule_id = ' . intVal($rule->getId());
            if ($clearImport)
                $cond .= ' AND is_primary IS NULL';
                
            $db->delete($table, $cond);

            if ($clearImport) {           
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('amxcoupon')->__('Coupons have been successfully deleted.')
                );
            }                
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('amxcoupon')->__('Can not delete coupons. Error is: %s .', $e->getMessage())
            );
        }
        
        return true;
    }

    protected function _validateParams($rule)
    {
        $num      = Mage::app()->getRequest()->getParam('generate_num');
        $pattern  = Mage::app()->getRequest()->getParam('generate_pattern');
        $fileName = !empty($_FILES['import_file']['name']);
        
        $primary = $rule->getPrimaryCoupon();
        if ((!$primary || !$primary->getId()) && ($num || $pattern || $fileName)){
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('amxcoupon')->__('Please configure the primary coupon first.'));
            return false;             
        }
        
        return true;
    }     
    
    protected function _generateCoupons($rule)
    {
        $num      = abs(Mage::app()->getRequest()->getParam('generate_num'));
        $pattern  = Mage::app()->getRequest()->getParam('generate_pattern');
        
        if (!$num && !$pattern){
            return true; // no data, just skip
        }
            
        if (!$num || !$pattern){
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('amxcoupon')->__('Please specify number of coupons to generate as well as coupon code template.'));
            return false;  // invalid data, add error                    
        }
        
        $generator = Mage::getModel('amxcoupon/generator');
        try {
            $generator->validate($pattern);
        }
        catch (Exception $e){
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            return false;                    
        }
        
        $maxAttempts = 3333;
        $codes  = array();
        for ($i=0; $i < $num; ++$i){
            $code = $generator->getCode($pattern);
            for ($attempt = 0; $attempt < $maxAttempts; ++$attempt){
                if (isset($codes[$code])){
                    $code = $generator->getCode($pattern);
                } 
                else {
                    $codes[$code] = 1;
                    break;
                }
            }
        }
        $codes = array_keys($codes); 
        
        try { 
            $cnt = $this->_saveCodes($rule, $codes);
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('amxcoupon')->__('Can not generate all coupons. Please check the coupons list an try one more time. Error is: %s .', $e->getMessage()));
            return false;
        }         
        
        if ($cnt){
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('amxcoupon')->__('%d coupon(s) have been successfully generated.', $cnt));                
        }
        
        return true;
    }    
    
    protected function _importCoupons($rule)
    {
        if (empty($_FILES['import_file']['name']))
            return true; //ok, no data
            
        $fileName = $_FILES['import_file']['tmp_name'];
        
        ini_set('auto_detect_line_endings', 1); 
        $codes = @file($fileName);
        if (!$codes){ // smth wrong
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('amxcoupon')->__('Can not open file %s .', $fileName));
            return false;
        }
            
        for ($i=0, $n=count($codes); $i<$n; ++$i){
            $codes[$i] = str_replace(array("\r","\n","\t",'"', "'", ',', ';', ' '), '', $codes[$i]);
        }
        
        try { 
            $cnt = $this->_saveCodes($rule, $codes);
        }
        catch (Exception $e){
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('amxcoupon')->__('Can not import all coupons. Please check that the codes are unique. Error is: %s .', $e->getMessage()));
            return false;
        }

        
        if ($cnt){
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('amxcoupon')->__('%d coupon(s) have been successfully imported.', $cnt));                
        }        
        
        return true;

    } 
    
    protected function _saveCodes($rule, &$codes)
    {
        $primary = $rule->getPrimaryCoupon();
        $data = array(
            $rule->getId(), 
            '', 
            intVal($primary->getUsageLimit()), 
            intVal($primary->getUsagePerCustomer()), 
            'NULL',
        );
        
        $d = $primary->getExpirationDate();
        if ($d && $d != 'NULL' && $d != '0000-00-00 00:00:00')
            $data[4] = "'$d'";
            
        
        $codes = array_unique($codes);     
            
        $table = Mage::getSingleton('core/resource')->getTableName('salesrule/coupon');
        $sql = "INSERT IGNORE INTO `$table` (`rule_id`, `code`, `usage_limit`, `usage_per_customer`, `expiration_date`) VALUES "; 
        foreach ($codes as $code){
            if (!$code)
                continue;
                
            $data[1] =  "'" . $code . "'"; // add quoteInto?
            $sql .= '(' . implode(',', $data) . '),';   
            //@todo save each 1000, not all at once
        }
        
        $db    = Mage::getSingleton('core/resource')->getConnection('amxcoupon_write');
        
        $cntSql = 'SELECT COUNT(*) FROM `'.$table.'` WHERE rule_id=' . $rule->getId();
        $cnt = $db->fetchOne($cntSql);
        
        $db->raw_query(substr($sql, 0, -1));
        
        $cnt = $db->fetchOne($cntSql) - $cnt;

        return $cnt;          
    }
}