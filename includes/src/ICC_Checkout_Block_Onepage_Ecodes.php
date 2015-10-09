<?php
class ICC_Checkout_Block_Onepage_Ecodes extends Mage_Checkout_Block_Onepage_Abstract
{
    protected function _construct()
    {  
        $this->getCheckout()->setStepData('ecodes', array(
            'label'     => Mage::helper('checkout')->__('Premium Access Users'),
            'is_show'   => $this->isShow()
        ));
        parent::_construct();
    }
}