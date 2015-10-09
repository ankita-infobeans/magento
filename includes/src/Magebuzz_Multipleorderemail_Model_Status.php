<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Model_Status extends Varien_Object
{
    const STATUS_ENABLED	 = 1;
    const STATUS_DISABLED = 2;

    static public function getOptionArray()
    {
        return array(
            ''=> Mage::helper('multipleorderemail')->__('Select Status'),       
            self::STATUS_ENABLED => Mage::helper('multipleorderemail')->__('Enabled'),
            self::STATUS_DISABLED => Mage::helper('multipleorderemail')->__('Disabled')
        );
    }
}