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
 * One page checkout processing model
 */
class ICC_Checkout_Model_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{
    /**
     * Save billing address information to quote
     * This method is called by One Page Checkout JS (AJAX) while saving the billing information.
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  Mage_Checkout_Model_Type_Onepage
     */
    public function saveBilling($data, $customerAddressId)
    {
        //$session = $this->getCustomerSession();
        //$customer = $session->getCustomer();
        $quote = $this->getQuote();
        
        $email = !empty($data['email']) ? $data['email'] : null;
        try {
            //Mage::getModel('icc_purchases/link_purchases')->canBuy($quote, $email);
        } catch (Exception $e) {
            return array('error' => -1, 'message' => $e->getMessage());
        }
        
        return parent::saveBilling($data, $customerAddressId);
    }
    
    public function saveShippingMethod($shippingMethod)
    {
        if (empty($shippingMethod)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid shipping method.'));
        }
        $rate = $this->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if (!$rate) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid shipping method.'));
        }
        $this->getQuote()->getShippingAddress()
            ->setShippingMethod($shippingMethod);
        $this->getQuote()->collectTotals()
            ->save();

		$this->getCheckout()->setStepData('shipping_method', 'complete', true);

		if ($this->getCheckout()->getData('has_ecodes_step')) {
			$this->getCheckout()->setStepData('ecodes', 'allow', true);
		} else {
			$this->getCheckout()->setStepData('payment', 'allow', true);
		}
        return array();
    }

//	public function saveEcodes($ecodesData)	{
//			
//        if (empty($ecodesData)) {
//			return array('error' => -1, 'message' => $this->_helper->__('Invalid ecodes data.'));
//	 	}
//        $session = $this->getCustomerSession();
//		$customer = $session->getCustomer();
//        $login_info = array("login" => $ecodesData['username'],"password" => $ecodesData['password']);
//        $session->setData('login_info',$login_info);
//        $newUser = false;
//        if(array_key_exists('ecodes_change_password',$ecodesData)) {
//            $newUser = true;
//        }
//		$result = $customer->createEcodesMasterAccount($ecodesData['username'], $ecodesData['password'], $ecodesData['confirmation'], $newUser);
//        Mage::log("result in onepage: " . print_r($result,1),null,"system.log");
//        $result_arr = array();
//		if (!$result['success']) {
//            if(strstr($result['message']['message'],"Msg=Duplicate LongId found") !== false) {
//                return array('error' => -1, 'message' => "This login name already exists, please select another.  If you have any questions or concerns, please contact ICC’s Electronic Media Division by e-mail at support@ecodes.biz or at 1-888-422-7233 x 33822.");
//            }
//            elseif(strstr($result['message'],"Msg=Duplicate LongId found") !== false) {
//                return array('error' => -1, 'message' => "This login name already exists, please select another.  If you have any questions or concerns, please contact ICC’s Electronic Media Division by e-mail at support@ecodes.biz or at 1-888-422-7233 x 33822.");
//            }
//            elseif(strstr($result['message'],"User does not have access to this Portal") !== false) {
//                return array('error' => -1, 'message' => "Incorrect username or password, if you are trying to create a new user, please make sure to check the This is a New Account checkbox.  If you have any questions or concerns, please contact ICC’s Electronic Media Division by e-mail at support@ecodes.biz or at 1-888-422-7233 x 33822.");
//            }
//			return array('error' => -1, 'message' => $result['message']);
//		}
//        elseif(strstr($result['message'],"We were unable to create your master account") !== false) {
//            $result_arr = array('error' => -1, 'message' => $result['message']);
//        }
//
//        $user = Mage::getModel('ecodes/premiumusers')->getCollection()->getByUsername($ecodesData['username']);
//
//        if ($user->getId() == 0) {
//            $user = Mage::getModel('ecodes/premiumusers');
//            $user->setFirstname($customer->getFirstname());
//            $user->setLastname($customer->getLastname());
//            $user->setEmail($customer->getEmail());
//            $user->setUser($ecodesData['username']);
//            $user->setPass(Mage::helper('ecodes')->encryptPassword($ecodesData['password']));
//            $user->setCreatedAt(date('m/d/y h:i:s', time()));
//            $user->save();
//        }
//
//		$this->getCheckout()
//			->setStepData('ecodes', 'complete', true)
//			->setStepData('payment', 'allow', true);
//	 
//		return $result_arr;
//	}
     /**
     * Save Volume licesnse information to quote
     * This method is called by One Page Checkout JS (AJAX) while saving the billing information.
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  Mage_Checkout_Model_Type_Onepage
     */
    public function saveVolumelicense($data)
    {
        /*if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }*/

        $j = 1;
		$registerCustomer = $this->getQuote()->getBillingAddress();
		$duplicateEmail = Array();
        
        foreach ($data['product_id'] as $p_id) {
			if($registerCustomer->getEmail() != $data[$p_id][$j]['email']) {
				if (!in_array($data[$p_id][$j]['email'], $duplicateEmail)){
					$duplicateEmail[] = $data[$p_id][$j]['email'];
					$collection = Mage::getModel('customer/customer')->getCollection()->addFieldToFilter('email', $data[$p_id][$j]['email'])->getFirstItem();
					if (!$collection->getId()) {
						if (!isset($result['error'])) {
							//$result['error'] = -1;
							//$result['message'] = $data[$p_id][$j]['email']."<br />";
						} else {
							//$result['message'] .= $data[$p_id][$j]['email']."<br />";					
						}
					}
				}	
			}	
            $newUsers[] = array("product_id" => $p_id, "name" => $data[$p_id][$j]['firstname'], "lastname" => $data[$p_id][$j]['lastname'], "email" => $data[$p_id][$j]['email'], "item_id" => $data[$p_id][$j]['item_id']);
            
            $j++;
            
        }
            
        if (isset($result)) {
			

            return $result;
        }
        $i = 1;
        foreach ($data['product_id'] as $p_id) {
            $json_data[] = array("product_id" => $p_id, "name" => $data[$p_id][$i]['firstname'], "lastname" => $data[$p_id][$i]['lastname'], "email" => $data[$p_id][$i]['email'], "item_id" => $data[$p_id][$i]['item_id']);
            $i++;
            
        }
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $cartItems = $quote->getAllVisibleItems();
        foreach ($cartItems as $item) {
            $item->setVolumeUsers(json_encode($json_data));
            //Mage::log($item->getId().'-'.print_r($json_data,1), null, 'xxxx.log', true);
        }
        $this->getQuote()->setVolumeUsers(json_encode($json_data));	
        $this->getQuote()->collectTotals();
        $this->getQuote()->save();
        
        $requiedVolumeStep = false;
        foreach ($this->getQuote()->getAllVisibleItems() as $item) {
            if ($item->getProduct()->getData('volume_license') && $item->getProduct()->getData('volume_license') == 1 && $item->getQty() > 1) {
                $requiedVolumeStep = true; /* Chenge it to True */
                break;
            }
        }
        //echo $requiedVolumeStep;exit('hhhhhh');
        if($requiedVolumeStep){
            $this->getCheckout()
                ->setStepData('volumelicense', 'allow', true)
                ->setStepData('volumelicense', 'complete', true)
                ->setStepData('payment', 'allow', true);
        }else{
            $this->getCheckout()
			->setStepData('billing', 'complete', true)
			->setStepData('payment', 'allow', true);
        }
        

        return array();
    }
    
     /**
     * Save Premium Access address information to quote
     * This method is called by One Page Checkout JS (AJAX) while saving the billing information.
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  Mage_Checkout_Model_Type_Onepage
     */
    public function saveEcodes($data)
    {   
        if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }

        $j = 1;
		$registerCustomer = $this->getQuote()->getBillingAddress();
		$duplicateEmail = Array();
        
        foreach ($data['product_id'] as $p_id) {
			if($registerCustomer->getEmail() != $data[$p_id][$j]['email']) {
				if (!in_array($data[$p_id][$j]['email'], $duplicateEmail)){
					$duplicateEmail[] = $data[$p_id][$j]['email'];
					$collection = Mage::getModel('customer/customer')->getCollection()->addFieldToFilter('email', $data[$p_id][$j]['email'])->getFirstItem();
					if (!$collection->getId()) {
						if (!isset($result['error'])) {
							$result['error'] = -1;
							$result['message'] = $data[$p_id][$j]['email']."<br />";
						} else {
							$result['message'] .= $data[$p_id][$j]['email']."<br />";					
						}
					}
				}	
			}	
            $newUsers[] = array("product_id" => $p_id, "name" => $data[$p_id][$j]['firstname'], "lastname" => $data[$p_id][$j]['lastname'], "email" => $data[$p_id][$j]['email'], "item_id" => $data[$p_id][$j]['item_id']);
            
            $j++;
            
        }
            
        if (isset($result)) {

            return $result;
        }
        $i = 1;
        foreach ($data['product_id'] as $p_id) {
            $json_data[] = array("product_id" => $p_id, "name" => $data[$p_id][$i]['firstname'], "lastname" => $data[$p_id][$i]['lastname'], "email" => $data[$p_id][$i]['email'], "item_id" => $data[$p_id][$i]['item_id']);
            $i++;
            
        }
       
        $this->getQuote()->collectTotals();
        $this->getQuote()->save();

        $this->getCheckout()
        ->setStepData('ecodes', 'allow', true)
        ->setStepData('ecodes', 'complete', true)
        ->setStepData('volumelicense', 'allow', true);

        return array();
    }
}
