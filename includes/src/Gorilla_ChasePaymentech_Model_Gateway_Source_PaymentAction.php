<?php

/**
 * Paymentech Payment Action Dropdown source
 */
class Gorilla_ChasePaymentech_Model_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Gorilla_ChasePaymentech_Model_Gateway::ACTION_AUTHORIZE,
                'label' => Mage::helper('paygate')->__('Authorize Only')
            ),
            array(
                'value' => Gorilla_ChasePaymentech_Model_Gateway::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('paygate')->__('Authorize and Capture')
            ),
        );
    }
}
