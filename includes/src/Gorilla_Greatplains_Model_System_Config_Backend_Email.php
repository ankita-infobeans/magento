<?php

class Gorilla_Greatplains_Model_System_Config_Backend_Email extends Mage_Core_Model_Config_Data
{

    public function save()
    {
        // Validate email. Do not allow invalid emails
        if (!Zend_Validate::is($this->getValue(), 'EmailAddress')) {
            Mage::throwException("Invalid email format. Recipient and sender emails must be valid emails");
        }
        return parent::save();
    }

}