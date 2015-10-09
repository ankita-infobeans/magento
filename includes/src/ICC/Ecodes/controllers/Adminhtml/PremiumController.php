<?php

class ICC_Ecodes_Adminhtml_PremiumController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Main index action
     *
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('customer');
        $this->renderLayout();
    }
    
    public function gridAction()
    {   
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function saveAction ()
    {
        if($data = $this->getRequest()->getPost()) {
            $this->_getSession()->setFormData($data);
            $model = Mage::getModel('ecodes/premiumsubs');
            $id = (int) $this->getRequest()->getParam('id');
            $session = $this->_getSession();
            try { //print_r($data); die;
                if($id) {
                    $model->load($id);
                }
                if( ! empty($data['new_pass']) )
                {
                    $helper_core = Model::helper('core');
                    $helper_ecodes = Mage::helper('ecodes');
                    $validation_error = $helper_ecodes->validate($data['new_pass'], $data['user'], $data['firstname'], $data['lastname']);
                    if($validation_error)
                    {
                        $session->addError($validation_error);
                        $this->_redirect('*/*/edit', array('id', $id));
                        exit; // ensure no further processing
                    }
                    $enc_pass = $helper->encrypt($data['new_pass']);
                    $data['pass'] = $enc_pass;
                }
                $model->addData($data);
                $model->save();
                
                $this->_getSession()->addSuccess(
                        $this->__('PremiumACCESS Subscription was successfully saved.')
                );
                $this->_getSession()->setFormData(false);
                
                //if($this->getRequest()->getParam('back')) { // save and continue button check
                    $customer = Mage::getModel('customer/customer')->load($model->getCustomerId());
                    $params = array('id' => $customer->getId());
                    $this->_redirect( '*/customer/edit', $params);
                //} else {
                //    $this->_redirect('*/*/list');
                //}
            }
            catch(Exception $e)
            {
                $this->_getSession()->addError($e->getMessage());
                if($model && $model->getId()) {
                    $this->_redirect('*/*/edit', array(
                      'id' => $model->getId()
                    ));
                } else {
                    $this->_redirect('*/customer');
                }
            }
            return;
        } // end if the request object has data to return to us
        
        // if there is no data
        $this->_getSession()->addError($this->__('No data found to save'));
        $this->_redirect('*/customer'); // forward to the controller index action
    }
    
    public function editAction()
    {   
        $model = Mage::getModel('ecodes/premiumsubs');
        if ($id = (int) $this->getRequest()->getParam('id'))
        {
            $model->load($id);
        }
        //Mage::log($model->debug(), null, 'prem-controller-model.log');
        Mage::register('current_premiumsubs', $model);

//        Mage::register('_current_template', $model);
        $this->_title('Edit PremiumACCESS Subscription');
        $this->loadLayout();
        
        // die('almost done');
        $this->_setActiveMenu('customer/ecodes');
        $this->renderLayout();
        /* */
        
        
    }
    
    public function deleteAction()
    {
        $premium_subs = Mage::getModel('ecodes/premiumsubs');
        $id = (int) $this->getRequest()->getParam('id');
        try{
            if($id)
            {
                $premium_subs->load($id);
                $name = $premium_subs->getProductName();
                // $customer = Mage::getModel('customer/customer')->load($premium_subs->getCustomerId());
                $premium_user_subs = Mage::getModel('ecodes/premiumsubusers')  //->findOneByParams( array('subs_id'=>$id, 'user_id'=>$customer->getId() ));
                        ->getCollection()
                        ->addFieldToFilter('subs_id', $id)
                        ->addFieldToFilter('user_id', $premium_subs->getCustomerId() ); //$customer->getId());
                $premium_user_subs->delete(); // = $premium_user_subs->getFirstItem();
                $premium_subs->delete();
                $this->_getSession()->addSuccess($this->__('"%s" was successfully deleted.', $name));
                $this->_redirectReferer();
            }
        }
        catch(Exception $e)
        {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectReferer();
        }
    }

    protected function _initCustomer($idFieldName = 'id')
    {
        $this->_title($this->__('Customers'))->_title($this->__('Manage Customers'));

        $customerId = (int) $this->getRequest()->getParam($idFieldName);
        $customer = Mage::getModel('customer/customer');

        if ($customerId) {
            $customer->load($customerId);
        }

        Mage::register('current_customer', $customer);
        return $this;
    }

    public function customersAction()
    {
        $this->_initCustomer();
        $this->getResponse()->setBody($this->getLayout()->createBlock('ecodes/adminhtml_customer_edit_tab_premium')->toHtml());
    }
    
    /**
     * Check ACL permissions
     */
    public function _isAllowed()
    {
        return true; // Mage::getSingleton('admin/session')->isAllowed('ecodes/ecodes');
    }
    
}