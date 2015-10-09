<?php

class Gorilla_Paymentech_AccountController extends Mage_Core_Controller_Front_Action {

    /**
     * Only logged in users can use this functionality,
     * this function checks if user is logged in before all other actions
     *
     */
    public function preDispatch() {
        parent::preDispatch();

        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession() {
        return Mage::getSingleton('customer/session');
    }

    public function cardsAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->loadLayoutUpdates();

        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $headBlock->setTitle($this->__('My Credit Cards'));
        }

        $this->renderLayout();
    }

    public function addAction() {
        $data = $this->_getSession()->getCustomerCardFormData(true);
        Mage::register('form_data', $data);

        $msg = $this->_getSession()->getMessages(true);
        $this->loadLayout();
        $this->getLayout()->getMessagesBlock()->addMessages($msg);
        $this->_initLayoutMessages('customer/session');

        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $headBlock->setTitle($this->__('Add a Credit Card'));
        }

        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('paymentech/account/cards');
        }

        $this->getLayout()->getBlock('cards_form')->setCimMode('Add');
        $this->getLayout()->getBlock('cards_form')->setMyAccountHeader('Add a Credit Card');

        $this->renderLayout();
    }

    public function editAction() {
        $session = $this->_getSession();
        $session->setEscapeMessages(true); // prevent XSS injection in user input
        $token_id = $this->getRequest()->getParam('id', false);
        $paymentProfile = Mage::getModel('paymentech/profile')
                ->getCustomerPaymentProfile($token_id);

        if (!$paymentProfile) {
            $session->addError($this->__('Invalid payment profile requested.'));
            $this->_redirectError(Mage::getUrl('*/*/cards', array('_secure' => true)));
            return;
        }

        //print_r($paymentProfile);

        foreach ($paymentProfile as $k => $v) {
            $dta = $v;
            $id = $k;
        }
        $form_data = array();
        $form_data['paymentech_cc_customer_ref_num'] = $dta->customerRefNum;
        $form_data['paymentech_cc_name'] = $dta->customerName;
        $form_data['paymentech_cc_billing_address1'] = $dta->customerAddress1;
        $form_data['paymentech_cc_billing_address2'] = $dta->customerAddress2;
        $form_data['paymentech_cc_city'] = $dta->customerCity;
        $form_data['paymentech_cc_region_id'] = $dta->customerState;
        $form_data['paymentech_cc_zip'] = $dta->customerZIP;
        $form_data['paymentech_cc_number'] = $dta->ccAccountNum;
        $form_data['paymentech_expiration'] = $dta->ccExp;
        $form_data['paymentech_expiration_yr'] = $dta->ccExp;

        //// print_r($form_data);

        Mage::register('form_data', $form_data);

        $msg = $this->_getSession()->getMessages(true);
        $this->loadLayout();
        $this->getLayout()->getMessagesBlock()->addMessages($msg);
        $this->_initLayoutMessages('customer/session');

        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $headBlock->setTitle($this->__('Update Credit Card'));
        }

        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('paymentech/account/cards');
        }

        $formBlock = $this->getLayout()->getBlock('cards_form');
        // $formBlock->setCimMode('Edit')->setMyAccountHeader('Edit a Credit Card')->setCcGatewayId($token_id);

        $this->renderLayout();
    }

    public function deleteAction() {
        $session = $this->_getSession();
        $session->setEscapeMessages(true); // prevent XSS injection in user input
        $token_id = $this->getRequest()->getParam('id', false);
        $paymentProfile = Mage::getModel('paymentech/profile');


        if (!$paymentProfile) {
            $session->addError($this->__('Invalid payment profile requested.'));
            $this->_redirectError(Mage::getUrl('*/*/cards', array('_secure' => true)));

            return;
        }

        $cards = $paymentProfile->getCustomerPaymentProfile($token_id);
        $dta = "";
        $id = "";


        foreach ($cards as $k => $v) {
            $dta = $v;
            $id = $k;
        }



        // Perform the Delete
        $result = Mage::getModel('paymentech/profile')->deleteProfile($dta->customerRefNum);

        $session->addSuccess($this->__('Card successfully deleted.'));

        $paymentProfile->deleteProfileFromDatabase($id);
        $this->_redirectError(Mage::getUrl('*/*/cards', array('_secure' => true)));
        return $this;
    }

    /**
     * Save CC data
     */
    public function saveAction() {
        $session = $this->_getSession();
        $session->setEscapeMessages(true); // prevent XSS injection in user input
        $data = $this->getRequest()->getPost('payment');

        //print_r($data);
        $profileModel = Mage::getModel('paymentech/profile');
        $profileReturn = $profileModel->createProfile($data);
        if (!$profileReturn) {
            $this->_getSession()->addError($profileModel->error);

            $this->_redirectError(Mage::getUrl('*/*/edit/id/' . $card_customer->getPaymentProfile()->getGatewayId(), array('_secure' => true)));
            return;
        }
        Mage::getSingleton('customer/session')->isLoggedIn();
        $session = Mage::getSingleton('customer/session');

        $cid = $session->getId();

        $profileModel->setCustomerId($cid);
        $profileModel->setCustomerRefNum($profileReturn);
        $profileModel->save();

        $session->addSuccess($this->__('Card successfully saved.'));
        $this->_redirectSuccess(Mage::getUrl('*/*/cards', array('_secure' => true)));


        return;



        $session->addSuccess($this->__('Card successfully saved.'));
        $this->_redirectSuccess(Mage::getUrl('*/*/cards', array('_secure' => true)));

        $this->_getSession()->addError($error_message);

        $this->_redirectError(Mage::getUrl('*/*/edit/id/' . $card_customer->getPaymentProfile()->getGatewayId(), array('_secure' => true)));
        return;
    }

    /**
     * Check to make sure the customer has no open orders before deleting the
     * payment profile.
     * 
     * @param type $payment_profile_id
     * @return bool 
     */
    protected function _canDeletePaymentProfile($payment_profile_id) {
        $ordersCollection = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('customer_id', array('eq' => $this->_getSession()->getCustomer()->getId()))
                ->addFieldToFilter('status', array('nin' => array('complete', 'canceled')));

        foreach ($ordersCollection as $order) {
            if ($order->getPayment()->getPaymentechPaymentId() == $payment_profile_id) {
                return false;
                break;
            }
        }

        return true;
    }

    protected function _getStateById($id) {
        return Mage::getModel('directory/region')->load($id)->getCode();
    }

}