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
 * @package     Mage_Customer
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Customer account controller
 *
 * @category   Mage
 * @package    Mage_Customer
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Customer/controllers/AccountController.php';
class ICC_Ecodes_AccountController extends Mage_Customer_AccountController {

    public function productsAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Electronic Products'));
        $this->renderLayout(); 
    }   

	public function loginAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    public function forgotPasswordAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }
    
    public function doLoginAction()
    {       
        if( ! $this->getRequest()->isPost() )
        {
            $this->_redirect('*/*/login');
            return;
        }
        $session = $this->_getSession();
        $login_data = $this->getRequest()->getPost('login');
        $helper = Mage::helper('ecodes');
        $username = $login_data['username'];
        $password = $login_data['password'];

        $premiumuser = Mage::getModel('ecodes/premiumusers')->load($username, 'user');
//        if($premiumuser->isEmpty() || $helper->decryptPassword($premiumuser->getPass()) != $password) {
//            $session->addError('Sorry, but we could not match that username and password. Please try again.');
            $this->_redirect('*/*/login');
//            return;
//        }
            
        $icc_connect = Mage::getModel('ecodes/api');
        if( ! $icc_connect->hasConnection())
        {
            $session->addError('Sorry, but we could not establish a connection to log you in. Please return and try again at a later time. We appologize for any inconvenience.');
            $this->_redirect('*/*/login');
            return;
        }
        $sid = $icc_connect->createSid($login_data['username'], $login_data['password']);
        
        if( ! $sid ) // failed login
        {
            $session->addError('Sorry, but we could not match that username and password. Please try again.');
            $this->_redirect('*/*/login');
            return;
        }
        // http://beta-dotnet.citation.com/cgi-exe/cpage.dll?sid=
//        Mage::log(Mage::getStoreConfig('iccconnect_options/configfields/ecodeloginurl'));
// Mage::log(__CLASS__ . "|" . __METHOD__ . "|" . __FUNCTION__  . "|" . __LINE__,null,"rb_debug.log");
        $this->_redirectUrl(Mage::getStoreConfig('iccconnect_options/configfields/ecodeloginurl') . "?sid=" . $sid);
    }
    
    public function doForgotPasswordAction() 
    {
        if( ! $this->getRequest()->isPost() )
        {
            $this->_redirect('*/*/login');
            return;
        }
        
        $session = $this->_getSession();
        $post_data = $this->getRequest()->getPost();
        $email = $post_data['email'];
        $username = $post_data['username'];
        
        $customer_collection = Mage::getModel('customer/customer')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('email', mysql_real_escape_string($email) );
        $error_message = $this->__('Sorry, but we could not match that email address and username. Please try again.');
        if( ! $customer_collection->count() )
        {
            $session->addError( $error_message );
            $this->_redirect('*/*/forgotPassword');
            return;
        }
        $customer = $customer_collection->getFirstItem();
        if($customer->getEcodesMasterUser() != $username)
        {
            $session->addError( $error_message . ' ' . $customer->getEcodesMasterUser() . ' and submitted: ' . $username  );
            $this->_redirect('*/*/forgotPassword');
            return;
        }
        
        $helper = Mage::helper('ecodes');
        $password = $helper->decryptPassword( $customer->getEcodesMasterPass() );
        
        try{
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            $mail_template = Mage::getModel('core/email_template');
            $template_config_path = 'iccconnect_options/configfields/ecodes_forgot_password';
            $template = Mage::getStoreConfig($template_config_path, Mage::app()->getStore()->getId());

            $mail_template->setDesignConfig( array('area' => 'frontend', 'store' => Mage::app()->getStore()->getId() ))
                ->sendTransactional(
                        $template,
                        Mage::getStoreConfig( Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, Mage::app()->getStore()->getId() ),
                        $customer->getEmail(),
                        $customer->getName(),
                        array(
                            'customer'  => $customer,
                            'password'  => $password
                        )
                );
            $translate->setTranslateInline(true); 
            $session->addSuccess($this->__('We successfully sent you your password. Please allow a few minutes for the email to arrive in your inbox. Check the spam folder if it does not appear shortly.' ));        
   
        } catch (Exception $e) {
            $session->addError($this->__('We were unable to send an email with your forgotten password. Please try again in a short while.' ));
        }
        $this->_redirect('*/*/forgotPassword');
    }

    public function usersAction() {

        $subscriptionId = (int)$this->getRequest()->getParam('sid');

		if ($subscriptionId) {
        	$subscription = Mage::getModel('ecodes/premiumsubs')->load($subscriptionId);
        	Mage::register('current_subscription', $subscription);
			$this->loadLayout();
        	$this->_initLayoutMessages('customer/session');
			$this->getLayout()->getBlock('head')->setTitle($this->__('PremiumACCESS Subscription Users'));
			$this->renderLayout(); 
		} else {
			$this->_redirect('/ecodes/accounts/products');
		}
    }

	public function adduserAction() {
        if ($this->getRequest()->isPost()) {
        	$session = $this->_getSession();
			$helper = Mage::helper('ecodes');

            $subscriptionId = $this->getRequest()->getPost('subscriptionId');
        	$subscription = Mage::getModel('ecodes/premiumsubs')->load($subscriptionId);

			$customer = Mage::getModel('customer/customer')->load($subscription->getCustomerId());
			if ($customer->getId() != Mage::getSingleton('customer/session')->getCustomer()->getId()) {
				throw new Exception('Attempt to add user to subscription not owned by customer');
			}

            $firstname = $this->getRequest()->getPost('firstname');
            $lastname = $this->getRequest()->getPost('lastname');
            $email = $this->getRequest()->getPost('email');
            $login = $this->getRequest()->getPost('username');
            $password = $this->getRequest()->getPost('password');
            $confirmPassword = $this->getRequest()->getPost('confirmation');
			$error = $helper->validateLogin($login);
			$userCreated = false;

			if (!$error) {	
				if ($password) {
					$error = $helper->validatePassword($password, $login, $firstname, $lastname);	
					if (!$error) {
						if ($password != $confirmPassword) {
							$error = 'Please make sure your passwords match. ERR 901'; 
						}
						
						if (!$error) {
							$usertest = Mage::getModel('ecodes/premiumusers')->getCollection()->getByUsername($login);
							if ($usertest->getId() != 0) {
								$error = 'This username is already in use';
							}

							if (!$error) {
								//create new user
								$result = Mage::getModel('ecodes/api')
											->createUser($customer->getEcodesMasterUser(), $helper->decryptPassword($customer->getEcodesMasterPass()), $login, $password, $firstname, $lastname, $email);
								if ($result['success']) {
									$user = $this->createUser($firstname, $lastname, $email, $login, $password, $helper);
									$userCreated = true;
								} else {
									$error = $result['message'];
								}
							}
						}
					}
				} else {
					$user = Mage::getModel('ecodes/premiumusers')->getCollection()->getByUsername($login);	
					if ($user->getId() == 0) {
						$error = 'This username was not found';
					}
					
					if ($error) {
						//confirm user exists
						$result = Mage::getModel('ecodes/api')->doesUserExist($login);
						if (!$result['success']) {
							$error = "There is a problem executing your requested action. Please contact ICC’s Electronic Media Division by e-mail at support@ecodes.biz or at 1-888-422-7233 x 33822.";
						}
					}
				}
	
				// at this point we should either have a $user object or an $error msessage
				if (!$error) {
					$result = Mage::getModel('ecodes/api')->appendUserProduct($user->getUser(), $subscription->getSku(), $subscription->getExpiration());
											
					if ($result['success']) {
						$subUser = Mage::getModel('ecodes/premiumsubusers');
						$subUser->setSubsId($subscriptionId);
						$subUser->setUserId($user->getId());
						$subUser->setCreatedAt(date('m/d/y h:i:s', time()));
						$subUser->save();
						$session->addSuccess('The user has been added successfully');
					} else {
						$error = $result['message'];
					}
				}				
			}		

			if ($error) {
				if ($userCreated) $session->addSuccess('The user was created successfully, but there was an error assigning the subscription');
				$session->setCustomerFormData($this->getRequest()->getPost());
				$session->addError($error);
			}
			$this->_redirect('ecodes/account/users/sid/' . $subscriptionId);
		}
	}

	public function removeuserAction() {
echo 'ICC Connect remove user not yet implemented';
exit;
		$session = $this->_getSession();

		$subscriptionId = $this->getRequest()->getParam('sid');
		$userId = $this->getRequest()->getParam('uid'); 

		$subscription = Mage::getModel('ecodes/premiumsubs')->load($subscriptionId);

		$customer = Mage::getModel('customer/customer')->load($subscription->getCustomerId());
		if ($customer->getId() != Mage::getSingleton('customer/session')->getCustomer()->getId()) {
			throw new Exception('Attempt to remove user from subscription not owned by customer');
		}

		if (!$error) {
			//$result = Mage::getModel('ecodes/api')->removeProduct();
			$result['success'] = true;
			if ($result['success']) {
				//delete user-subscription relationship
				$subUser = Mage::getModel('ecodes/premiumsubusers')->getCollection()->getBySubscriptionAndUserId($subscriptionId, $userId);
				$subUser->delete();
				$session->addSuccess('The user has been removed from this subscription successfully');
			} else {
				$error = $result['message'];
			}
		}

		if ($error) $session->addError($error);
		$this->_redirect('ecodes/account/users/sid/' . $subscriptionId);
	}

	public function updateuserpasswordAction() {

        $userId = (int)$this->getRequest()->getParam('uid');
        $subscriptionId = (int)$this->getRequest()->getParam('sid');

		if ($userId && $subscriptionId) {
        	$user = Mage::getModel('ecodes/premiumusers')->load($userId);
        	Mage::register('current_ecodesuser', $user);
        	$subscription = Mage::getModel('ecodes/premiumsubs')->load($subscriptionId);
        	Mage::register('current_subscription', $subscription);
			$this->loadLayout();
        	$this->_initLayoutMessages('customer/session');
			$this->getLayout()->getBlock('head')->setTitle($this->__('PremiumACCESS Subscription Users'));
			$this->renderLayout(); 
		} else {
			$this->_redirect('/ecodes/accounts/products');
		}

	}

	public function saveupdateuserpasswordAction() {
        if ($this->getRequest()->isPost()) {
        	$session = $this->_getSession();
			$helper = Mage::helper('ecodes');

            $subscriptionId = $this->getRequest()->getPost('subscriptionId');
            $userId = $this->getRequest()->getPost('userId');
        	$user = Mage::getModel('ecodes/premiumusers')->load($userId);

			if ($user->getId() != $userId || $subscriptionId == 0) {
				$this->_redirect('/ecodes/accounts/products');
				return;
			}

            $currentPassword = $this->getRequest()->getPost('current_password');
            $password = $this->getRequest()->getPost('password');
            $confirmPassword = $this->getRequest()->getPost('confirmation');

			if ($currentPassword != $helper->decryptPassword($user->getPass())) {
				$error = 'The current password is incorrect';
			}
			if (!$error) {
				$error = $helper->validatePassword($password, $user->getUser(), $user->getFirstname(), $user->getLastname());
				if (!$error) {
					if ($password != $confirmPassword) {
						$error = 'Please make sure the passwords match.'; 
					}
	
					if (!$error) {
						//try to create ICC Connect master user account				
						$result = Mage::getModel('ecodes/api')->updateSelf($user->getUser(), $currentPassword, $password);
//						$result['success'] = true;
	
						if (!$result['success']) {
							$error = $result['message'];
						} else {
							$user->setPass($helper->encryptPassword($password));
							$user->save();
							$session->addSuccess('The password has been updated successfully');
							$this->_redirect('ecodes/account/users/sid/' . $subscriptionId . '/');
							return;
						}
					}
				}
			}

			$session->addError($error);
			$this->_redirect('ecodes/account/updateuserpassword/uid/' . $userId . '/sid/' . $subscriptionId);
		}
	}

	protected function createUser($firstname, $lastname, $email, $login, $password, $helper) {
		$user = Mage::getModel('ecodes/premiumusers');
		$user->setFirstname($firstname);
		$user->setLastname($lastname);
		$user->setEmail($email);
		$user->setUser($login);
		$user->setPass($helper->encryptPassword($password));
		$user->setCreatedAt(date('m/d/y h:i:s', time()));
		$user->save();
		return $user;
	}
        
        public function saveCdRomAction()
        {
            if( ! $this->getRequest()->isPost())
            {
                $this->_redirect('*/*/products');
                return;
            }
//            $session = $this->_getSession(); // 
            $session = Mage::getSingleton('customer/session');
            $customer = $session->getCustomer();
            $new_product_name = $this->getRequest()->getParam('product_name');
            $new_serial_number = $this->getRequest()->getParam('serial_number');
            
            $json_encoded_cd_roms = $customer->getEcodesCdSerials();
            if(empty($json_encoded_cd_roms))
            {
                $codes_serial_numbers = array();
            }
            else 
            {
                $ecodes_serial_numbers = Zend_Json_Decoder::decode( $json_encoded_cd_roms );
            }
            
            $info_array = array(
                'product_name' => $new_product_name,
                'serial_number' => $new_serial_number,
            );
            
            $ecodes_serial_numbers[] = $info_array; // push this new array onto the end of the array
            $json_encoded_cd_roms = Zend_Json_Encoder::encode($ecodes_serial_numbers);
            
            $customer->setEcodesCdSerials($json_encoded_cd_roms);
            $customer->save();
            
            $session->addSuccess($this->__('Successfully saved new serial number.'));
            $this->_redirect('*/*/products?my-cd-roms=1');
         //array_values($threads);   
        }
        
        public function deleteCdRomAction()
        {
//            $session = $this->_getSession(); // 
            $session = Mage::getSingleton('customer/session');
            $customer = $session->getCustomer();
            $delete_product_id = (int) $this->getRequest()->getParam('id');
            
            if( empty($delete_product_id) && $delete_product_id !== 0)
            {
                $this->_redirect('*/*/products?my-cd-roms=1'); // no id to delete
                return;
            }
            
            $json_encoded_cd_roms = $customer->getEcodesCdSerials();
            if(empty($json_encoded_cd_roms))
            {
                $this->_redirect('*/*/products?my-cd-roms=1'); // hmm we don't seem to have any
                return;
            }
            else 
            {
                $ecodes_serial_numbers = Zend_Json_Decoder::decode( $json_encoded_cd_roms );
            }
            
            unset($ecodes_serial_numbers[ $delete_product_id ]); // remove that item from the array
            
            $reindexed_ecodes_serials = array_values($ecodes_serial_numbers);
            
            $json_encoded_cd_roms = Zend_Json_Encoder::encode($reindexed_ecodes_serials);
            
            $customer->setEcodesCdSerials($json_encoded_cd_roms);
            $customer->save();
            
            $session->addSuccess($this->__('Successfully removed serial number.'));
            $this->_redirect('*/*/products?my-cd-roms=1');
            
        }
        
        
        
    public function updategpskufromprodidAction()
    {
        $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection();
        $downloadableCollection->updateGpSkusFromProductId();
    }
}
