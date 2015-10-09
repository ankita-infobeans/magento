<?php

class Gorilla_Greatplains_Model_System_Config_Backend_Attempts extends Mage_Core_Model_Config_Data
{

    public function save()
    {
        // Validate number. Should be a whole number greater than 0.
        if (!ctype_digit((string)$this->getValue()) || (int)$this->getValue() <= 0) {
            Mage::throwException("Invalid entry for attempts. Must be a positive whole number, such as 20");
        }
        return parent::save();
    }

}