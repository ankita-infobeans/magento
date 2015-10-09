<?php

require_once 'Mage/Customer/controllers/AccountController.php';
class ICC_Customer_AccountController extends Mage_Customer_AccountController 
{
    private $__sharepoint_login = null; // 'https://www.iccsafe.org/Pages/default.aspx';
    private $__sharepoint_register = null; // 'https://www.iccsafe.org/Pages/register.aspx'; // http://www.iccsafe.org/_layouts/iccsafe/storelogin.aspx
    private $__get_var = null; // 'ReturnPath';
   
    private function getSharepointLogin()
    {
        if( is_null( $this->__sharepoint_login )) $this->__sharepoint_login = Mage::getStoreConfig('customer/avectra/login_redirect_url');
        return $this->__sharepoint_login;
    }
    
    private function getReturnPathGetParam()
    {
        if( is_null( $this->__get_var )) $this->__get_var = Mage::getStoreConfig('customer/avectra/redirect_get_var');
        return $this->__get_var;
    }
    
    private function getSharepointRegister()
    {
        if( is_null( $this->__sharepoint_register )) $this->__sharepoint_register = Mage::getStoreConfig('customer/avectra/register_redirect_url');
        return $this->__sharepoint_register;
    }
    /**
    * Dispatch Event
    *
    * @param Mage_Customer_Model_Customer $customer
    */
   protected function _dispatchRegisterSuccess($customer)
   {
       Mage::dispatchEvent('customer_register_success',
           array('account_controller' => $this, 'customer' => $customer)
       );
   }
    
    public function landingAction()
    {
        $redirect_path = urldecode( $this->getRequest()->getParam( $this->getReturnPathGetParam() ));
        $this->_redirectUrl($redirect_path);
        return; // make sure we do no further processing
        // die(' thanks for coming back');
    }
    
    public function loginAction()
    {   
        if ( Mage::getStoreConfig('customer/avectra/login_hook') == 1 )
        {
            $referer = ($this->_getRefererUrl() && ! preg_match('/customer\/account\/logoutSuccess/', $this->_getRefererUrl()) )?($this->_getRefererUrl()):('https://' . $_SERVER['SERVER_NAME']);
            $return_url = preg_replace( '/^http:/', 'https:', $referer );        
           
            $return_url = urlencode($return_url);
            $this->_redirectUrl( $this->getSharepointLogin() . '?' . $this->getReturnPathGetParam() . '=' . $return_url, '200'  );
        } else {
            parent::loginAction();
        }
    } 
    
    public function createAction()
    {
        if (Mage::getStoreConfig('customer/avectra/login_hook') == 1) {
            $referer = ($this->_getRefererUrl())?($this->_getRefererUrl()):('https://' . $_SERVER['SERVER_NAME']);
            $return_url = preg_replace( '/^http:/', 'https:', $referer );        
           
            $return_url = urlencode($return_url);
            Mage::log($return_url, null, 'cust-account-controller.log');
            Mage::log($this->getSharepointRegister() . '?' . $this->getReturnPathGetParam() . '=' . $return_url, null, 'cust-account-controller.log');
            $this->_redirectUrl( $this->getSharepointRegister() . '?' . $this->getReturnPathGetParam() . '=' . $return_url );
        } else {
            parent::createAction();
        }
    }
    
    public function saveDemographicsAction()
    {
        // make sure someone didn't accidentally post this form
        $update_info = $this->getRequest()->getPost();
        foreach($update_info as $key => $value) {
            if(trim($value) == '') {
                unset($update_info[$key]);
            }
        }
        $session = Mage::getSingleton('core/session');
        if(count($update_info) === 0) {
            $session->addError('Sorry, we could not update the information about yourself. Please select from the "Tell Us About Yourself" drop down menus.');
            $this->_redirect('customer/account');
            return;
        }  else {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $update_info['key'] = $customer->getAvectraKey();
            $account = Mage::getModel('icc_avectra/account');
            $ave_demo = $account->updateAvectraDemographics($update_info);
            if($account->hasAvectraConnection() && $ave_demo) {
                $customer->setHasUpdatedDemo(true); // has_updated_demo 
                $customer->save();
            } else {
                $session->addError('Sorry, we could not update the information about yourself. Please try again at a later time.');
                $this->_redirect('customer/account');
                return;                
            }
        }
        //$this->_redirect('*/*/edit');
        $session->addSuccess('Thank you. We have successfully updated this information.');
        //$this->_redirectUrl($_SERVER['HTTP_REFERER']);
        $this->_redirect('customer/account');
    }
    
    public function resetDemoTestAction()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customer->setHasUpdatedDemo(false); // has_updated_demo 
        $customer->save();
        echo 'done updating';
    }	

        public function createecodesaccountAction() {
            if ($this->getRequest()->isPost()) {
                $session = $this->_getSession();
                $customer = $session->getCustomer();

                $login = $this->getRequest()->getPost('username');
                $password = $this->getRequest()->getPost('password');
                $confirmPassword = $this->getRequest()->getPost('confirmation');

                $result = $customer->createEcodesMasterAccount($login, $password, $confirmPassword);

                if ($result['success']) {
                    $session->addSuccess($result['message']);
                } else {
                    $session->setCustomerFormData($this->getRequest()->getPost());
                    $session->addError($result['message']);
                }
            }
            $this->_redirect('customer/account/edit/');
        }


	public function updateecodespasswordAction() {
            if ($this->getRequest()->isPost()) {
                $session = $this->_getSession();
                $customer = $session->getCustomer();
                $helper = Mage::helper('ecodes');

                $currentPassword = $this->getRequest()->getPost('current_password');
                $password = $this->getRequest()->getPost('password');
                $confirmPassword = $this->getRequest()->getPost('confirmation');

                $error = $helper->validatePassword($password, $customer->getEcodesMasterUser(), $customer->getFirstname(), $customer->getLastname());
                if (!$error) {
                    if ($password != $confirmPassword) {
                        $error = 'Please make sure your passwords match. ERR 903'; 
                    }

                    if (!$error) {
                        //try to create ICC Connect master user account				
                        $icc_api = Mage::getModel('ecodes/api');
                        $result = $icc_api->updateSelf($customer->getEcodesMasterUser(), $currentPassword, $password);

                        if (!$result['success']) {
                            // if the result fails because ICC Connect is down - add it to the queue
                            if( ! $icc_api->testConnection() )
                            {
                                $q = Mage::getModel('gorilla_queue/queue');
                                $q->addToQueue('ecodes/apiQueue', 'processUpdateSelfQueueItem', array('current_password'=>$currentPassword, 'password'=>$password, 'master_user'=>$customer->getEcodesMasterUser()), 'create_master_user' )->setShortDescription( $result['message'] )->save();
                            }
                            $error = $result['message'];
                        } else {
                            $customer->setEcodesMasterPass($helper->encryptPassword($password));
                            $customer->save();
                            $session->addSuccess('Your password has been updated successfully');
                            $this->_redirect('customer/account/edit/');
                            return;
                        }
                        $error = $result['message'];
                    } else {
                        $customer->setEcodesMasterPass($helper->encryptPassword($password));
                        $customer->save();
                        $session->addSuccess('Your password has been updated successfully');
                        $this->_redirect('customer/account/edit/');
                        return;
                    }
                }

                $session->setCustomerFormData($this->getRequest()->getPost());
                $session->addError($error);
                $this->_redirect('customer/account/edit/');
            }
	}

    public function editAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $block = $this->getLayout()->getBlock('customer_edit');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }

        $data = $this->_getSession()->getCustomerFormData(true);

        $customer = $this->_getSession()->getCustomer();
        if (!empty($data)) {
            $customer->addData($data);
        }

        if ($this->getRequest()->getParam('changepass')==1){
            $customer->setChangePassword(1);
        } else if ($data['ecodes_change_password']==1){
            $customer->setChangeEcodePassword(1);
        }

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }
    
    public function logoutAction() 
    {
        $this->_getSession()->logout()
            ->setBeforeAuthUrl(Mage::getUrl());
        $observer =  Mage::getModel('icc_avectra/observer');
        $observer->deleteSharePointSsoCookie();
        if (Mage::getStoreConfig('customer/avectra/login_hook') == 1)
            $this->_redirectUrl('http://www.iccsafe.org/logout.aspx?ReturnURL=http://' . $_SERVER['SERVER_NAME'] . '/customer/account/logoutSuccess/');
        else
            $this->_redirectUrl(Mage::helper('icc_avectra')->getLogoutLink());
    }
    
}
