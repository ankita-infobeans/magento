<?php

class Gorilla_Report_Block_Adminhtml_Order_Renderer_Discount extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Currency
{
    public function render(Varien_Object $row)
    {
        $data = abs($row->getData($this->getColumn()->getIndex()));
        $currency_code = $this->_getCurrencyCode($row);

        if (!$currency_code) {
            return $data;
        }

        $data = floatval($data) * $this->_getRate($row);
        $data = sprintf("%f", $data);
        $data = Mage::app()->getLocale()->currency($currency_code)->toCurrency($data);
        return $data;
    }
}