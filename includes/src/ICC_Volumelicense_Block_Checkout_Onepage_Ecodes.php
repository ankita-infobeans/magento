<?php
class ICC_Volumelicense_Block_Checkout_Onepage_Ecodes extends Mage_Checkout_Block_Onepage_Abstract
{
    protected function _construct()
    {
        $this->getCheckout()->setStepData('ecodes', array(
            'label'     => Mage::helper('checkout')->__('Premium Access Users1'),
            'is_show'   => $this->isShow()
        ));
        parent::_construct();
    }
}