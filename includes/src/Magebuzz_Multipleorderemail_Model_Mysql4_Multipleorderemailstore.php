<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Model_Mysql4_Multipleorderemailstore extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('multipleorderemail/multipleorderemailstore');
    }
}