<?php
class ICC_TableRateMixed_Block_Shipping_Carrier_Tablerate_Grid extends Mage_Adminhtml_Block_Shipping_Carrier_Tablerate_Grid
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function _prepareColumns()
    {
        $this->addColumnAfter(
            'calculation_type',
            array(
                'header'    => Mage::helper('adminhtml')->__('Calculation Type'),
                'index'     => 'calculation_type',
                'default'   => 'fixed',
            ),
            'price'
        );

        return parent::_prepareColumns();
    }
}