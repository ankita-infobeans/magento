<?php

class Gorilla_Report_Block_Adminhtml_Customer_Renderer_Fullname extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $result[] = $row->getData('customer_firstname');
        $result[] = $row->getData('customer_lastname');

        return implode(' ',$result);
    }
}