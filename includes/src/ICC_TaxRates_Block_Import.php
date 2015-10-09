<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of import
 *
 * @author sdunagan
 */
class ICC_TaxRates_Block_Import
    extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('taxrates/import.phtml');
    }
    
}

?>
