<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Multishipping checkout controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Checkout/controllers/MultishippingController.php';
class ICC_Checkout_MultishippingController extends Mage_Checkout_MultishippingController
{  
   
    private function getSharepointLogin()
    {
        return Mage::getStoreConfig('customer/avectra/login_redirect_url');
    }
    
    private function getReturnPathGetParam()
    {
        return Mage::getStoreConfig('customer/avectra/redirect_get_var');
    }
    
    private function getSharepointRegister()
    {
        return Mage::getStoreConfig('customer/avectra/register_redirect_url');
    }
    /**
     * Multishipping checkout login page
     */
    public function loginAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }

        if ( Mage::getStoreConfig('customer/avectra/login_hook') == 1 ) {
            $return_url = preg_replace( '/^http:/', 'https:', $this->_getRefererUrl() );            
            $return_url = urlencode($return_url);
            $return_path = urlencode( $this->_getRefererUrl() ); // urlencode($_SERVER['HTTP_REFERER']);
            $this->_redirectUrl( $this->getSharepointLogin() . '?' . $this->getReturnPathGetParam() . '=' . $return_url );
		} else {
            parent::loginAction();
		}
    }

    /**
     * Multishipping checkout login page
     */
    public function registerAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirectUrl($this->_getHelper()->getMSCheckoutUrl());
            return;
        }

        if ( Mage::getStoreConfig('customer/avectra/login_hook') == 1 ) {
            $return_url = preg_replace( '/^http:/', 'https:', $this->_getRefererUrl() );            
            $return_url = urlencode($return_url);
            $return_path = urlencode( $this->_getRefererUrl() ); // urlencode($_SERVER['HTTP_REFERER']);
            $this->_redirectUrl( $this->getSharepointRegister() . '?' . $this->getReturnPathGetParam() . '=' . $return_url );
		} else {
            parent::loginAction();
		}
    }
    
    public function overviewAction()
    {
        if (!$this->_validateMinimumAmount()) {
            return $this;
        }
        
        $this->_getState()->setActiveStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_OVERVIEW);

        try {
            $payment = $this->getRequest()->getPost('payment');
            $this->_getCheckout()->setPaymentMethod($payment);
            $this->_getState()->setCompleteStep(
                Mage_Checkout_Model_Type_Multishipping_State::STEP_BILLING
            );
/// added part to sync billing address
            if($payment['method'] == 'icc_billmember' )
            { 
                $bill_member = Mage::getModel('icc_billmember/billMember');
                $bill_member_results = $bill_member->getResults();
                if($bill_member_results === false ) {
                    $this->_getCheckoutSession()->addError($e->getMessage());
                    $this->_redirect('*/*/billing');
                } else {
                    $billing = $this->_getCheckout()->getQuote()->getBillingAddress();
                    $avectra_key = Mage::getModel('customer/session')->getCustomer()->getAvectraKey();
                    $helper = Mage::helper('icc_avectra');
                    $helper->syncBillingAvectra($avectra_key, $billing );
                }
            }            
// end of added            
            $this->loadLayout();
            $this->_initLayoutMessages('checkout/session');
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();
        }
        catch (Mage_Core_Exception $e) {
            $this->_getCheckoutSession()->addError($e->getMessage());
            $this->_redirect('*/*/billing');
        }
        catch (Exception $e) {
            Mage::logException($e);
            $this->_getCheckoutSession()->addException($e, $this->__('Cannot open the overview page'));
            $this->_redirect('*/*/billing');
        }
    }
    
}
