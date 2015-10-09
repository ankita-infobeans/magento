<?php

class ICC_Ecodes_Block_Email_Downloadable_Order_Serials extends Mage_Core_Block_Template
{
    const DEFAULT_MISSING_SERIAL_MESSAGE = 'There are currently no serial numbers available for this product, please check your account soon.';

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        $this->addSerialsToEmailOrderItems();
    }

    /**
     * Add serials to the order email rendering.  This block either needs to be added in the email template before the
     * order items layout update or added immediately to the update handle throught the layout XML.
     */
    public function addSerialsToEmailOrderItems()
    {
        foreach($this->getOrder()->getAllItems() as $orderItem)
        {
            $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
            if($product->getSerialRequired())
            {
                Mage::helper('ecodes/downloadable')
                    ->renderSerialOptionOnOrderItem($orderItem, self::DEFAULT_MISSING_SERIAL_MESSAGE);
            }
        }
    }
}