<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2010-2011 Amasty (http://www.amasty.com)
* @package Amasty_Pgrid
*/
class Amasty_Acart_Block_Adminhtml_History_Grid_Renderer_Reason extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $hlp =  Mage::helper('amacart'); 
        
        $types = $hlp->getReasonsTypes();
        
        
        return isset($types[$row->getReason()]) ? $types[$row->getReason()] : 'undefined';
    }
}