<?php

/**
 *
 */
class Gorilla_ChasePaymentech_Model_Gateway_Source_ValidationMode
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Gorilla_ChasePaymentech_Model_Gateway::VALIDATION_MODE_NONE,
                'label' => Mage::helper('paygate')->__('None')
            ),
            array(
                'value' => Gorilla_ChasePaymentech_Model_Gateway::VALIDATION_MODE_TEST,
                'label' => Mage::helper('paygate')->__('Test')
            ),
            array(
                'value' => Gorilla_ChasePaymentech_Model_Gateway::VALIDATION_MODE_LIVE,
                'label' => Mage::helper('paygate')->__('Live')
            ),
        );
    }
}
