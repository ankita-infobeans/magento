<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Model_Session extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('multipleorderemail');
    }
}