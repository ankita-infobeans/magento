<?php
class ICC_Volumelicense_Block_Checkout_Onepage_Shipping extends Mage_Checkout_Block_Onepage_Shipping
{
    /**
     * Initialize shipping address step
     */
    protected function _construct()
    {
       	$this->getCheckout()->setStepData('shipping', array(
            'label'     => Mage::helper('checkout')->__('Shipping Address'),
            'is_show'   => $this->isShow()
        ));

       // parent::_construct();
    }
}
?>