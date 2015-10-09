<?php
/**
* @author Amasty Team
* @copyright Amasty
* @package Amasty_Coupons
*/
class Amasty_Coupons_CheckoutController extends Mage_Core_Controller_Front_Action
{
    /**
     * Retrieve shopping cart model object
     *
     * @return Mage_Checkout_Model_Cart
     */
    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    /**
     * Get checkout session model instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current active quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }
    
    public function cancelCouponAction()
    {
        $codeToCancel = $this->getRequest()->getParam('amcoupon_code_cancel');
        $appliedCoupons = $this->_getQuote()->getAppliedCoupons();
        
        foreach ($appliedCoupons as $i => $coupon)
        {
            if ($coupon == $codeToCancel)
            {
                unset($appliedCoupons[$i]);
                try
                {
                    if ($this->_getQuote()->setCouponCode($appliedCoupons)->save())
                    {
                        $this->_getSession()->addSuccess($this->__('Coupon code %s was canceled.', $codeToCancel));
                    }
                }
                catch (Mage_Core_Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
                catch (Exception $e) {
                    $this->_getSession()->addError($this->__('Cannot cancel the coupon code.'));
                }
            }
        }
        
        $this->_redirect('checkout/cart');
        return $this;
    }
}