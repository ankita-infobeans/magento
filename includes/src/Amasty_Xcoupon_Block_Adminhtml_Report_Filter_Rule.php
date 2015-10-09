<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */
class Amasty_Xcoupon_Block_Adminhtml_Report_Filter_Rule extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Select
{
    public function getCondition()
    {
        return $this->getValue() ? array('finset' => $this->getValue()) : null;
    }
}