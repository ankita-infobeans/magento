<?php

class ICC_Ecodes_EcodesloginController extends Mage_Core_Controller_Front_Action
{

    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function loginAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    public function forgotPasswordAction()
    {
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
        if (!$this->getRequest()->isPost()) {
// $this->_redirect('*/*/login');
            return;
        }

        $session = $this->_getSession();
        $post_data = $this->getRequest()->getPost();
        $email = $post_data['email'];
        $username = $post_data['username'];

        $customer_collection = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('email', mysql_real_escape_string($email));
        $error_message = $this->__('Sorry, but we could not match that email address and username. Please try again.');
        if (!$customer_collection->count()) {
            $session->addError($error_message);
// $this->_redirect('*/*/forgotPassword');
            return;
        }
        $customer = $customer_collection->getFirstItem();
        if ($customer->getEcodesMasterUser() != $username) {
            $session->addError($error_message . ' ' . $customer->getEcodesMasterUser() . ' and submitted: ' . $username);
//$this->_redirect('*/*/forgotPassword');
            return;
        }

        $helper = Mage::helper('ecodes');
        $password = $helper->decryptPassword($customer->getEcodesMasterPass());

        try {
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            $mail_template = Mage::getModel('core/email_template');
            $template_config_path = 'iccconnect_options/configfields/ecodes_forgot_password';
            $template = Mage::getStoreConfig($template_config_path, Mage::app()->getStore()->getId());

            $mail_template->setDesignConfig(array('area' => 'frontend', 'store' => Mage::app()->getStore()->getId()))
                ->sendTransactional(
                $template,
                Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, Mage::app()->getStore()->getId()),
                $customer->getEmail(),
                $customer->getName(),
                array(
                    'customer' => $customer,
                    'password' => $password
                )
            );
            $translate->setTranslateInline(true);
            $session->addSuccess($this->__('We successfully sent you your password. Please allow a few minutes for the email to arrive in your inbox. Check the spam folder if it does not appear shortly.'));

        } catch (Exception $e) {
            $session->addError($this->__('We were unable to send an email with your forgotten password. Please try again in a short while.'));
        }
// $this->_redirect('*/*/forgotPassword');
    }
}
