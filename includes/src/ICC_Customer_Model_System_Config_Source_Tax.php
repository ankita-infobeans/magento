<?php
/**
 * Created by Ariel Allon @ Gorilla
 *    aallon@gorillagroup.com
 * Creation date: 9/20/12 11:16 AM
 */

class ICC_Customer_Model_System_Config_Source_Tax
{
    protected $_options;

    public function __construct()
    {
        $this->_options = array(
            array(
                'value' => ICC_Customer_Model_Customer::CUSTOMER_TAX_CLASS_BY_CUSTOMER_GROUP,
                'label' => Mage::helper('tax')->__('Customer\'s Group\'s Tax Class (default)')
            ),
            array(
                'value' => ICC_Customer_Model_Customer::CUSTOMER_TAX_CLASS_BY_AVECTRA_EXEMPT,
                'label' => Mage::helper('tax')->__('Tax Exempt Status from Avectra')
            ),
        );
    }

    public function toOptionArray()
    {
        return $this->_options;
    }
}
