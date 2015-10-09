<?php
/**
 * Onepage checkout block
 *
 * @category   ICC
 * @package    ICC_Volumelicense
 */
class ICC_Premiumaccess_Block_Checkout_Onepage_Premiumaccess extends Mage_Checkout_Block_Onepage_Abstract
{
    protected function _construct()
    {
        $this->getCheckout()->setStepData('premiumaccess', array(
            'label'     => Mage::helper('checkout')->__('premiumACCESS Users'),
            'is_show'   => $this->isShow()
        ));

        parent::_construct();
    }
}