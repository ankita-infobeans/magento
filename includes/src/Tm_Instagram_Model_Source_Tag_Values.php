<?php

class Tm_Instagram_Model_Source_Tag_Values
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'store_title', 'label'=>Mage::helper('adminhtml')->__('Store Title')),
            array('value' => 'product_name', 'label'=>Mage::helper('adminhtml')->__('Product Name')),
            array('value' => 'product_sku', 'label'=>Mage::helper('adminhtml')->__('Product Tag')),
        );
    }
}