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

//require_once 'Mage/Checkout/controllers/OnepageController.php';
//class ICC_Checkout_OnepageController extends Mage_Checkout_OnepageController {

require_once 'Inferno/UspsAV/controllers/Checkout/OnepageController.php';

class ICC_Checkout_OnepageController extends Inferno_UspsAV_Checkout_OnepageController 
{

    public function hasEcodesStep() {
            return Mage::getSingleton('checkout/session')->getData('has_ecodes_step');
    }
    
    public function hasVolumelicenseStep()
    {
        return Mage::getSingleton('checkout/session')->getData('has_volumelicense_step');
    }
    
     public function hasPremiumaccessStep()
    {
        return Mage::getSingleton('checkout/session')->getData('has_premiumaccess_step');
    }
    /**
     * Perform address validation on checkout billing address from inferno, this used for address verification instead of the ICC billing, which in turn is called when the address verification is successfull or bypassed.
     */
    public function saveBillingAction()
    {        
        $this->_addressType = 'billing';

        if ($this->shouldCleansePostedAddress()) {
            $this->cleanseAddress();
        }

        if ($this->_error) {
            // Return response in JSON
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($this->_error));
        } else {
            $this->saveBillingActionICC();
        }
    }
    
    
    public function saveBillingActionICC()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('billing', array());
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                /* check quote for virtual */
                if ($this->getOnepage()->getQuote()->isVirtual()) {
					if ($this->hasEcodesStep()) {
						$result['goto_section'] = 'ecodes';
                                        }elseif($this->hasPremiumaccessStep()){
                                            $result['goto_section'] = 'premiumaccess';
                                        }elseif($this->hasVolumelicenseStep()){
                                            $result['goto_section'] = 'volumelicense';
                                        } 
                                        else {
						$result['goto_section'] = 'payment';
						$result['update_section'] = array(
							'name' => 'payment-method',
							'html' => $this->_getPaymentMethodsHtml()
						);
					}
                } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $result['goto_section'] = 'shipping_method';
                    $result['update_section'] = array(
                        'name' => 'shipping-method',
                        'html' => $this->_getShippingMethodsHtml()
                    );

                    $result['allow_sections'] = array('shipping');
                    $result['duplicateBillingInfo'] = 'true';
                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
    public function saveShippingMethodAction() {
        if ($this->_expireAjax()) {
            return;
        }
        
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping_method', '');
            $result = $this->getOnepage()->saveShippingMethod($data);
            /*
            $result will have error data if shipping method is empty
            */
            if(!$result) {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method',
                        array('request'=>$this->getRequest(),
                            'quote'=>$this->getOnepage()->getQuote()));
				if ($this->hasEcodesStep()) {
	                $result['goto_section'] = 'ecodes';
				} 
				elseif($this->hasPremiumaccessStep()){
                                            $result['goto_section'] = 'premiumaccess';
                                        }
				elseif($this->hasVolumelicenseStep()){
                                            $result['goto_section'] = 'volumelicense';
                                        }
				else {
					$result['goto_section'] = 'payment';
					$result['update_section'] = array(
						'name' => 'payment-method',
						'html' => $this->_getPaymentMethodsHtml()
					);
				}
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }


//	public function saveEcodesAction() {
//            
//        if ($this->_expireAjax()) {
//            return;
//        }
//		if ($this->getRequest()->isPost()) {
//			$data = $this->getRequest()->getPost('ecodes', '');
//			$result = $this->getOnepage()->saveEcodes($data);
//            Mage::log(print_r($result,1),null,"test.log");
////			if(!$result) {
//				Mage::dispatchEvent('checkout_controller_onepage_save_ecodes', array('request'=>$this->getRequest(), 'quote'=>$this->getOnepage()->getQuote()));
//				$this->getResponse()->setBody(Zend_Json::encode($result));
//	 
//				$result['goto_section'] = 'payment';
//				$result['update_section'] = array(
//					'name' => 'payment-method',
//					'html' => $this->_getPaymentMethodsHtml()
//				);
//	 
////			}                       
//			$this->getResponse()->setBody(Zend_Json::encode($result));
//		}
//	}

    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function savePaymentAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        try {
            if (!$this->getRequest()->isPost()) {
                $this->_ajaxRedirectResponse();
                return;
            }

            // set payment to quote
            $result = array();
            $data = $this->getRequest()->getPost('payment', array());
            $result = $this->getOnepage()->savePayment($data);
            if($data['method'] == 'icc_billmember' )
            { 
                $bill_member = Mage::getModel('icc_billmember/billMember');
                $bill_member_results = $bill_member->getResults();
                if($bill_member_results === false ) {
                   $result['error'] = $this->__('Sorry, we were unable to confirm your elegibility for the Bill Member payment method. Please choose another method.');                    
                } else {
                    $billing = $this->getOnepage()->getQuote()->getBillingAddress();
                    $avectra_key = Mage::getModel('customer/session')->getCustomer()->getAvectraKey();
                      /**** Infobeans ****/
                    //$helper = Mage::helper('icc_avectra');
                   // $helper->syncBillingAvectra($avectra_key, $billing );
                      /**** Infobeans ****/
                }
            }
            // get section and redirect data
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error']) && !$redirectUrl) {
                $this->loadLayout('checkout_onepage_review');
                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );
            }
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    
    public function saveDemographicsAction()
    {
        // make sure someone didn't accidentally post this form
        $update_info = $this->getRequest()->getPost();
        foreach($update_info as $key => $value)
        {
            if(trim($value) == '')
            {
                unset($update_info[$key]);
            }
        }
        $session = Mage::getSingleton('core/session');
        if(count($update_info) === 0)
        {
            $session->addError('Sorry, we could not update the information about yourself. Please select from the "Tell Us About Yourself" drop down menus.');
            $this->_redirect('customer/account');
            return;
        } else {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $update_info['key'] = $customer->getAvectraKey();
            $account = Mage::getModel('icc_avectra/account');
            $ave_demo = $account->updateAvectraDemographics($update_info);
            if($account->hasAvectraConnection() && $ave_demo)
            {
                $customer->setHasUpdatedDemo(true); // has_updated_demo 
                $customer->save();
            } else {
                $session->addError('Sorry, we could not update the information about yourself. Please try again at a later time.');
                $this->_redirect('customer/account');
                return;                
            }
        }
        $session->addSuccess('Thank you. We have successfully updated this information.');
        $this->_redirect('customer/account');
    }
    
    /**
     * Save Volume License  ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function saveVolumelicenseAction()
    {   
       
        if ($this->_expireAjax()) {
                return;
        }
        
        $data = $this->getRequest()->getPost('volumelicense', array());
        
        $duplicate = $this->checkDuplicate($data);
        
        $loginCustomer = Mage::getSingleton('customer/session')->getCustomer();
	$email_login_customer = $loginCustomer->getEmail();
        if ($this->getRequest()->isPost() && $duplicate == 0) {
          $data = $this->getRequest()->getPost('volumelicense', array());
          
          
          $serData = serialize($data);
          foreach ($data as $itemId => $value){
                $QuoteItem = Mage::getModel('sales/quote_item')->load($itemId);
                $QuoteItem->setVolumeLicense(true);
            }
            
          //echo "<pre>";print_r($serData);die;
          
          //$volumeLicenseData = array();
          
          /*foreach ($data as $itemId => $value){
                  $volumeusersemail = '';
                foreach($value as $emails){
		   if($emails['email'] == '') {
		   $volumeusersemail .= $email_login_customer.',';
		   }
		   else {
                   $volumeusersemail .= $emails['email'].','; 
                   }
                }
              $volumeLicenseData[] = array($itemId => rtrim($volumeusersemail,','));    
              
              
            
           // echo "<pre>";print_r($QuoteItem->getData());
           }
           */
           
            //die;
            $result = $this->getOnepage()->saveVolumelicense($serData);
            $session = Mage::getSingleton('checkout/session');
            $quoteId = $session->getQuoteId();
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $connection->beginTransaction();
            $__fields = array();
            $__fields['volume_license'] = true;
            $__fields['volume_users'] = $serData;
            $__where = $connection->quoteInto('entity_id =?', $quoteId);
            $connection->update('sales_flat_quote', $__fields, $__where);
            $connection->commit();
            
            
          
       
            
            
            
         //  echo "<pre>";print_r($QuoteItem->getData());die("=====");

           if (!isset($result['error'])) {
                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml()
                );
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }else {
            
           $result_arr = array();
           $result_arr['success'] = false;
           $result_arr['error'] = true;
           if($duplicate == 2){
               $result_arr['message'] = "You can not assign the product to yourself.";
           }else{
               $result_arr['message'] = "You have entered duplicate email address.";
           }
           
           echo json_encode($result_arr);
           
         // exit;
          // return $result_arr;
        }
    }
    
    
    public function savePremiumaccessAction() {

        if ($this->_expireAjax()) {
            return;
        }

        $data = $this->getRequest()->getPost('premiumaccess', array());
        $duplicate = $this->checkDuplicate($data);

        $loginCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $email_login_customer = $loginCustomer->getEmail();
        if ($this->getRequest()->isPost() && $duplicate == 0) {
            $data = $this->getRequest()->getPost('premiumaccess', array());
            $result = $this->getOnepage()->savePremiumaccess($data);
            if (!isset($result['error'])) {
                if($this->hasVolumelicenseStep()){
		    $result['goto_section'] = 'volumelicense';
		}  else {

                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                }
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {

            $result_arr = array();
            $result_arr['success'] = false;
            $result_arr['error'] = true;
            if ($duplicate == 2) {
                $result_arr['message'] = "You can not assign the product to yourself.";
            } else {
                $result_arr['message'] = "You have entered duplicate email address.";
            }

            echo json_encode($result_arr);

            // exit;
            // return $result_arr;
        }
    }

    /**
     * Save Premium ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function saveEcodesAction()
    {
                       
        if ($this->_expireAjax()) {
                return;
        }
        $preData = $this->getRequest()->getPost('premiumaccess', array());
        
        $duplicate = $this->checkDuplicate($preData);
               
        $customer = Mage::getModel('customer/customer');
        if ($this->getRequest()->isPost() && $duplicate == 0) {
          
           $data = $this->getRequest()->getPost('premiumaccess', array());
          
          foreach ($data as $itemId => $value){
                  $premiumusersemail = '';
                foreach($value as $emails){
                   $premiumusersemail .= $emails['email'].',';                    
                }
              $premiumAccessData[] = array($itemId => $premiumusersemail);                     
           }
          // echo "<pre>";print_r($premiumAccessData);exit;
           $result = $this->getOnepage()->saveEcodes($premiumAccessData);
            $session = Mage::getSingleton('checkout/session');
            $quoteId = $session->getQuoteId();
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $connection->beginTransaction();
            $__fields = array();
            $__fields['premium_users'] = json_encode($premiumAccessData);
            $__where = $connection->quoteInto('entity_id =?', $quoteId);
            $connection->update('sales_flat_quote', $__fields, $__where);
            $connection->commit(); 

           if (!isset($result['error'])) {
               $requiedVolumeStep = false;
                foreach (Mage::getModel('checkout/cart')->getQuote()->getAllVisibleItems() as $item) {
                    if ($item->getProduct()->getData('volume_license') && $item->getProduct()->getData('volume_license') == 1 && $item->getQty() > 1) {
                        $requiedVolumeStep = true;
                        break;
                    }
                }
                if($requiedVolumeStep){
                    $result['goto_section'] = 'volumelicense';                
                }else{
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                }
                
            }
          
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }else {
            
           $result_arr = array();
           $result_arr['success'] = false;
           $result_arr['error'] = true;
           if($duplicate == 2){
               $result_arr['message'] = "You can not assign the product to yourself.";
           }else{
               $result_arr['message'] = "You have entered duplicate email address.";
           }
           
           echo json_encode($result_arr);
           
         // exit;
          // return $result_arr;
        }
    }
    
    public function checkDuplicate($arrayDuplicate){
        
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $email = $customer->getEmail();// To get Email Id of a customer
        
        foreach ($arrayDuplicate as $array) {
            $duplicateEmail = Array();
            
            foreach ($array as $arrayDup) {
                
                if($arrayDup['email'] == $email && $arrayDup['email'] != ''){
                    return 2;
                }
                
                if($arrayDup['email'] != ''){
                    if (in_array($arrayDup['email'], $duplicateEmail)) {
                        return 1;
                    }
                }
                
                $duplicateEmail[] = $arrayDup['email'];
            }
        }
        //print_r($duplicateEmail);exit;
        return 0;
    }
}
