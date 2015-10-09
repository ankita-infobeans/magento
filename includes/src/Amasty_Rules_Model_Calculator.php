<?php
/**
* @author Amasty Team
* @copyright Copyright (c) Amasty (http://www.amasty.com)
* @package Amasty_Rules
*/
class Amasty_Rules_Model_Calculator extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('amrules/calculator');
    }
    
    public function getThisMonthTotal($customerId)
    {
        $from = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $to = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('t'), date('Y')));
        
        $conditions[] = array ('date'   => ' >= "' . $from . '"');
        $conditions[] = array ('date'   => ' <= "' . $to . '"');
        $conditions[] = array ('status' => ' = "complete"');
        return $this->_getTotals($customerId, $conditions);
    }
    
    public function getLastMonthTotal($customerId)
    {
        $y = date('Y');
        $m = date('m');
        if (0 == $m - 1) {
            $y = $y - 1 ;
            $m = 12;
        } else {
            $m = $m - 1;
        }
        $last = mktime(0, 0, 0, $m, 1, $y);
        
        $from = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', $last), 1, date('Y', $last)));
        $to = date('Y-m-d H:i:s', mktime(23, 59, 59, date('m', $last), date('t', $last), date('Y', $last)));
        
        $conditions[] = array ('date'   => ' >= "' . $from . '"');
        $conditions[] = array ('date'   => ' <= "' . $to . '"');
        $conditions[] = array ('status' => ' = "complete"');
        
        return $this->_getTotals($customerId, $conditions);        

    }
    
    public function getAllPeriodTotal($customerId)
    {
        $conditions[] = array ('status' => ' = "complete"');        
        return $this->_getTotals($customerId);
    }
    
    public function getSingleTotalField($customerId, $fieldName, $conditions, $conditionType)
    {
        $result = $this->_getTotals($customerId, $conditions, $conditionType);
        return $result[$fieldName];
    }   
     
     /**
     * Calculates aggregated order values for given customer
     *    
     * @param int $customerId
     * @param array $conditions  e.g. array( 0=> array('date'=>'>2013-12-04'),  1=>array('status'=>'>2013-12-04'))
     * @param string $conditionType "all"  or "any"
     */
    protected function _getTotals($customerId, $conditions=array(), $conditionType='all')
    {
        return $this->getResource()->getTotals($customerId, $conditions, $conditionType);
    }
    
    
}