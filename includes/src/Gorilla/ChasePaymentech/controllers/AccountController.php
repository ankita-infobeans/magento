<?php

class Gorilla_ChasePaymentech_AccountController extends Mage_Core_Controller_Front_Action
{
    /**
     * Only logged in users can use this functionality,
     * this function checks if user is logged in before all other actions
     *
     */
    public function preDispatch()
    {
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
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Cards view action
     */
    public function cardsAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->loadLayoutUpdates();
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Credit Cards'));
        $this->renderLayout();
    }

    /**
     * Add new card action
     */
    public function addAction()
    {
        $this->loadLayout();
        $this->getLayout()->getMessagesBlock()->addMessages($this->_getSession()->getMessages(true));

        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('Add a Credit Card'));
        $this->getLayout()->getBlock('customer_account_navigation')->setActive('chasepaymentech/account/cards');

        $this->getLayout()->getBlock('cards_form')
            ->setMyAccountHeader('Add a Credit Card')
            ->setCcFormData($this->_getSession()->getCustomerCardFormData(true));

        $this->renderLayout();
    }

    /**
     * @TODO Test
     *
     * @return mixed
     */
    public function editAction()
    {
        $session = $this->_getSession();
        $session->setEscapeMessages(true); // prevent XSS injection in user input

        if (!$profileId = $this->getRequest()->getParam('id', false)) {
            $this->_redirectError(Mage::getUrl('*/*/cards', array('_secure' => true)));
            return;
        }

        $profile = Mage::getModel('chasepaymentech/profile')->load($profileId);
        $response = Mage::getModel('chasepaymentech/profile')->getCustomerPaymentProfile($profile->getCustomerRefNum());

        if (!$response) {
            $session->addError($this->__('Invalid payment profile requested.'));
            $this->_redirectError(Mage::getUrl('*/*/cards', array('_secure' => true)));
            return;
        }

        $formData = array(
            'chasepaymentech_cc_customer_ref_num' => $response->customerRefNum,
            'chasepaymentech_cc_name' => $response->customerName,
            'chasepaymentech_cc_billing_address1' => $response->customerAddress1,
            'chasepaymentech_cc_billing_address2' => $response->customerAddress2,
            'chasepaymentech_cc_billing_city' => $response->customerCity,
            'chasepaymentech_cc_billing_region_id' => $response->customerState,
            'chasepaymentech_cc_billing_zip' => $response->customerZIP,
            'chasepaymentech_cc_number' => $response->ccAccountNum,
            'chasepaymentech_expiration' => $response->ccExp,
            'chasepaymentech_expiration_yr' => $response->ccExp,
        );

        $msg = $this->_getSession()->getMessages(true);
        $this->loadLayout();
        $this->getLayout()->getMessagesBlock()->addMessages($msg);
        $this->_initLayoutMessages('customer/session');

        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $headBlock->setTitle($this->__('Update Credit Card'));
        }

        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('chasepaymentech/account/cards');
        }

        //$formBlock = $this->getLayout()->getBlock('cards_form');
        $this->getLayout()->getBlock('cards_form')->setIsEditMode(true)
            ->setMyAccountHeader('Edit a Credit Card')
            ->setCcFormData($formData);

        $this->renderLayout();
    }

    /**
     * Delete card action
     */
    public function deleteAction()
    {
        $session = $this->_getSession();
        $session->setEscapeMessages(true); // prevent XSS injection in user input

        if ($profileId = $this->getRequest()->getParam('id', false)) {
            // Make sure this is a valid profile, we don't want customers deleting
            // other peoples' info just by knowing the Id
            $profile = Mage::getModel('chasepaymentech/profile')->load($profileId);

            if (!$profile->getId()) {
                $session->addError($this->__('Invalid payment profile requested.'));
                $this->_redirectError(Mage::getUrl('*/*/cards', array('_secure' => true)));
                return;
            }

            // Check to see if we can delete the card
            if (!$this->_canDeletePaymentProfile($profile->getId())) {
                $session->addError($this->__('The card you requested to delete is currently in use on one or more order. Please try again once your orders have been completed.'));
                $this->_redirectError(Mage::getUrl('*/*/cards', array('_secure' => true)));
                return;
            }

            if ($card = $profile->getCustomerPaymentProfile($profile->getCustomerRefNum())) {
                // Perform the Delete
                if ($profile->deleteCustomerPaymentProfile($profile->getCustomerRefNum())) {
                    $profile->delete();
                    $session->addSuccess($this->__('Card successfully deleted.'));
                } else {
                    $session->addError($this->__('Unable to delete card at this time.'));
                }
            } else {
                $profile->delete();
                $session->addError($this->__('Credit card is no longer on file.'));
            }
        }

        $this->_redirectSuccess(Mage::getUrl('*/*/cards', array('_secure' => true)));
    }

    /**
     * Save CC data
     */
    public function saveAction()
    {
        $session = $this->_getSession();
        $session->setEscapeMessages(true); // prevent XSS injection in user input

        if ($this->getRequest()->isPost()) {
            $profile = Mage::getModel('chasepaymentech/profile');

            /**
             * @TODO: Validation
             * There should be a validation method here...
             */

            /**
             * If saving an existing credit card profile proceed else, create new one
             * @todo complete existing profile edit feature...
             */
            if ($profileId = $this->getRequest()->getParam('id')) {
                $profile->load($profileId);
                //$data = $this->_prepareCustomerForGateway($this->getRequest()->getPost('payment'));
                //Mage::log($data);
                //die('dead saving');
                //$results = $profile->createCustomerPaymentProfile(new Varien_Object($this->getRequest()->getPost('payment')));
                $results = null;

                if ($results) {
                    // set as default
                    if ($this->getRequest()->getParam('cc_default_card', false)) {
                        $profile->setFlagAsDefault(true)->save();
                    }

                    $session->addSuccess($this->__('Card successfully saved.'));
                    $this->_redirectSuccess(Mage::getUrl('*/*/cards', array('_secure' => true)));
                    return;
                } else {
                    foreach ($profile->getResponseMessages() as $code => $message) {
                        $this->_getSession()->addError($code . ": " . $message);
                    }
                    $this->_redirectError(Mage::getUrl('*/*/edit/id' . $profile->getId(), array('_secure' => true)));
                    return;
                }
            } else {
                $data = $this->_prepareCustomerForGateway($this->getRequest()->getPost('payment'));
                $results = $profile->createCustomerPaymentProfile($data);
                if ($results) {
                    $profile->setData(array(
                        'customer_id' => $this->_getSession()->getId(),
                        'customer_ref_num' => $results->customerRefNum
                    ));

                    if (isset($data['cc_default_card'])) {
                        $profile->setFlagAsDefault(true);
                    }

                    $profile->save();
                    $session->addSuccess($this->__('Card successfully saved.'));
                    $this->_redirectSuccess(Mage::getUrl('*/*/cards', array('_secure' => true)));
                    return;
                } else {
                    foreach ($profile->getResponseMessages() as $code => $message) {
                        $this->_getSession()->addError($code . ": " . $message);
                    }
                    $this->_redirectError(Mage::getUrl('*/*/add', array('_secure' => true)));
                    return;
                }
            }
        }

        $this->_redirect(Mage::getUrl('*/*/cards', array('_secure' => true)));
    }

    /**
     * Format the data properly for the Gateway post
     *
     * @param type $customer_data
     * @return Varien_Object $customer
     */
    protected function _prepareCustomerForGateway($customer_data)
    {
        $state = $customer_data['cc_billing_state'];
        if (isset($customer_data['cc_billing_state_id']) && !empty($customer_data['cc_billing_state_id'])) {
            $state = $this->_getStateById($customer_data['cc_billing_state_id']);
        }

        $customer = new Varien_Object($customer_data);
        $customer->setCcBillingState($state);
            //->setEmail(Mage::getModel('customer/session')->getCustomer()->getEmail())
            //->setId(Mage::getModel('customer/session')->getCustomer()->getId())
            //->setDescription('Magento Customer ID: ' . Mage::getModel('customer/session')->getCustomer()->getId())

        return $customer;
    }

    /**
     * Check to make sure the customer has no open orders before deleting the
     * payment profile.
     *
     * @param integer $paymentProfileId
     * @return bool
     */
    protected function _canDeletePaymentProfile($paymentProfileId)
    {
        $ordersCollection = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('customer_id', array('eq' => $this->_getSession()->getCustomer()->getId()))
                ->addFieldToFilter('status', array('nin' => array('complete', 'canceled')));

        foreach ($ordersCollection as $order) {
            if ($order->getPayment()->getChasePaymentechRefNum() == $paymentProfileId) {
                return false;
                break;
            }
        }

        return true;
    }

    /**
     * Returns state code
     *
     * @param $id
     * @return mixed
     */
    protected function _getStateById($id)
    {
        return Mage::getModel('directory/region')->load($id)->getCode();
    }
}
