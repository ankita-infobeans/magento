<?php
class Amasty_Acart_Model_Source_Import extends Mage_Core_Model_Config_Data
{
    public function _afterSave()
    {
        Mage::getResourceModel('amacart/blist')->uploadAndImport($this);
    }
}