<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ICC_Premiumaccess_Adminhtml_PremiumuserController extends Mage_Adminhtml_Controller_action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('customer/premiumcustomers')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Customer  Users PremiumACCESS Manager'), Mage::helper('adminhtml')->__('Item Manager'));
                  
		return $this;
	}   
 
        /**
         * This method used to render layout for premium access user. 
         */
	public function indexAction() { 
                $_premium_access_id  = $this->getRequest()->getParam('id');
                $_session_premium_access_id = Mage::getSingleton("adminhtml/session")->getData('premium_access_id');
                if(isset($_premium_access_id) && $_premium_access_id>0){
                    Mage::getSingleton("adminhtml/session")->setData('premium_access_id', $_premium_access_id);
                }elseif(!($_session_premium_access_id >0)){                     
                    $this->_redirect('*/premiumcustomers/index');
                }           
                
		$this->_initAction()
			->renderLayout();
	}   
        
        
        public function giftAction(){             
            $this->_initAction()
			->renderLayout();
        }
        
        public function purchaseAction(){   
         
            $this->_initAction()
			->renderLayout();
        }
        
        
        /**
         * This method used for render layout for reassign customer for given premium access id
         */
        public function editAction() {
            
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel('icc_premiumaccess/registry')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('premiumuser_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('customer/premiumcustomers');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Customer User PremiumACCESS Manager'), Mage::helper('adminhtml')->__('Customer User PremiumACCESS Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Customer User PremiumACCESS Manager'), Mage::helper('adminhtml')->__('Customer User PremiumACCESS Manager'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('icc_premiumaccess/adminhtml_premiumuser_edit'))
				->_addLeft($this->getLayout()->createBlock('icc_premiumaccess/adminhtml_premiumuser_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ecodes')->__('Customer does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function newAction() {
		$this->_forward('edit');
	}
 
	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {
                             
                        $email = $this->getRequest()->getParam('email');   
                       
                        $flag_validation = true; 
                        $error_msg = "";
                        $registry_data = array();
                        if(isset($email) && $email!='' && Zend_Validate::is($email, 'EmailAddress') ) {
                            
                            $_session_premium_access_id = Mage::getSingleton("adminhtml/session")->getData('premium_access_id');
                            if(isset($_session_premium_access_id) && $_session_premium_access_id > 0 ) {
                                
                                $premiim_access     = Mage::getModel('icc_premiumaccess/premiumaccess')->load($_session_premium_access_id);
                                $parent_customer_id = $premiim_access->getCustomerId();
                                $seats_total        = $premiim_access->getSeatsTotal();
                                $registered_count   = $premiim_access->getRegisteredCount();
                                $product_name       = $premiim_access->getProductName();
                                $expiration         = $premiim_access->getExpiration();  
                                $customer_name      = '';
                            } else {
                                
                                $flag_validation = false;
                                $error_msg = "PremiumACCESS is not selected";
                                
                            }
                            if($flag_validation) {
                                
                                $customerCollection = Mage::getModel('customer/customer')->getCollection()
                                                        ->addFieldToFilter('email' , array('eq'=> $email));                                
                                $customer = $customerCollection->getFirstItem();                                 
                                $assign_customer_id = $customer->getEntityId();  
                                if(isset($parent_customer_id) && $parent_customer_id>0){
                                  $customer_data         = Mage::getModel('customer/customer')->load($parent_customer_id);                                        
                                  $cData                 = $customer_data->getData();                                  
                                  if(is_array($cData) && count($cData)>0){
                                     $owner_customer_name   = $cData['firstname'] . ' ' . $cData['lastname'];
                                    
                                  }  else {
                                      $owner_customer_name = '';
                                  }
                                } 
                                if(($seats_total - $registered_count)>0){ 
                                     
                                 if(isset($assign_customer_id) && $assign_customer_id>0) {  
                                        if(isset($assign_customer_id) && $assign_customer_id>0){
                                            $customer_data = Mage::getModel('customer/customer')->load($assign_customer_id);                                        
                                            $cData         = $customer_data->getData();       
                                            if(is_array($cData) && count($cData)>0){
                                               $customer_name   = $cData['firstname'] . ' ' . $cData['lastname'];
                                            }  else {
                                               $customer_name = $email;
                                            }
                                        }                              
                                        $premiumaccess_registry = Mage::getModel('icc_premiumaccess/registry')->getCollection()
                                                                 ->addFieldToFilter('subscription_id' , array('eq'=> $_session_premium_access_id))                                    
                                                                 ->addFieldToFilter('assign_customer_id' , array('eq'=> $assign_customer_id))
                                                                 ->addFieldToFilter('status' , array('neq'=> ICC_Premiumaccess_Helper_Data::DELETE));
                                        $registry_row    = $premiumaccess_registry->getFirstItem();
                                        if($registry_row->getId()>0){
                                            $flag_validation = false;
                                            $error_msg = "Customer already exists";
                                        }else{
                                            $parent_customer_id = $parent_customer_id == $assign_customer_id ? 0 : $parent_customer_id; 
                                            $registry_data = array(
                                                                'subscription_id'       => $_session_premium_access_id,
                                                                'assign_customer_id'    => $assign_customer_id,
                                                                'parent_customer_id'    => $parent_customer_id,
                                                                'assign_customer_email' => $email, 
                                                                'status'                => ICC_Premiumaccess_Helper_Data::ACTIVE
                                                             );                            
                                        }
                                  }else{
                                        
                                       $assign_customer_id = 0;
                                       $registry_data = array(
                                           'subscription_id'       => $_session_premium_access_id,
                                           'assign_customer_id'    => $assign_customer_id,
                                           'parent_customer_id'    => $parent_customer_id,
                                           'assign_customer_email' => $email, 
                                           'status'                => ICC_Premiumaccess_Helper_Data::PENDING
                                        );  
                                  }  
                                }else{
                                     
                                   $flag_validation = false;
                                    $error_msg = "All PremiumACCESS gifts are already allocated. No PremiumACCESS gift available";   
                                }
                            }
                        }                    
			  	
                        if($flag_validation) {
                            $helper=Mage::helper('icc_premiumaccess');
                            
                            $model = Mage::getModel('icc_premiumaccess/registry');		
                            $model->setData($registry_data)
                                    ->setId($this->getRequest()->getParam('id'));

                            try {
                                    if ($model->getCreatedTime() == NULL || $model->getUpdateTime() == NULL) {
                                            $model->setCreatedTime(now())
                                                    ->setUpdateTime(now());
                                    } else {
                                            $model->setUpdateTime(now());
                                    }	

                                    $model->save();
                                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ecodes')->__('Item was successfully saved'));
                                    Mage::getSingleton('adminhtml/session')->setFormData(false);
                                    
                                    //UPDATE registered_count IN 
                                    $this->_updatePremiumAccessRegisteredCount($_session_premium_access_id); 
                                     
                                    $helper->premiumAccessShareEmail($email,$customer_name,$owner_customer_name,$product_name,$seats_total,$expiration);
                                    
                                    if ($this->getRequest()->getParam('back')) {
                                            $this->_redirect('*/*/edit', array('id' => $model->getId()));
                                            return;
                                    }
                                    $this->_redirect('*/*/');
                                    return;
                                      
                                } catch (Exception $e) {
                                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                                    Mage::getSingleton('adminhtml/session')->setFormData($data);
                                    $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                                    return;
                                }
                                
                        } else {
                            Mage::getSingleton('adminhtml/session')->addError($error_msg);
                            Mage::getSingleton('adminhtml/session')->setFormData($data);
                            $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                            return;
                        }        
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ecodes')->__('Unable to find item to save'));
        $this->_redirect('*/*/');
	}
        
        /**
         * This method used to delete in bulk users.
         */
        public function massDeleteAction() {
        $_session_premium_access_id = Mage::getSingleton("adminhtml/session")->getData('premium_access_id');    
        if(isset($_session_premium_access_id) && $_session_premium_access_id > 0 ) {
            $registryIds = $this->getRequest()->getParam('registry');
            if(!is_array($registryIds)) {
                            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
            } else {
                try {

                    foreach ($registryIds as $registryId) {
                        $ecodes = Mage::getModel('icc_premiumaccess/registry')->load($registryId);
                        //$ecodes->delete();
                        $ecodes->setStatus(ICC_Premiumaccess_Helper_Data::DELETE);
                        $ecodes->save();
                    }
                    
                    $this->_updatePremiumAccessRegisteredCount($_session_premium_access_id);                    

                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__(
                            'Total of %d record(s) were successfully deleted', count($registryIds)
                        )
                    );
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }
        }else{
            Mage::getSingleton('adminhtml/session')->addError("PremiumACCESS customer does not exist");
        }    
        $this->_redirect('*/*/index');
    }
    
    
    
    /**
     * This method used used to update registry count for given premium access id 
     * @param type $_premium_access_sub_id
     */
    protected function _updatePremiumAccessRegisteredCount($_premium_access_sub_id) {
        if(isset($_premium_access_sub_id) && $_premium_access_sub_id>0){
            $premiumaccess_registry  = Mage::getModel('icc_premiumaccess/registry')->getCollection()
            ->addFieldToFilter('subscription_id' , array('eq'=> $_premium_access_sub_id));                                  
            Mage::helper('icc_premiumaccess')->updatePremiumAccessRegisteredCount($_premium_access_sub_id);
        }
    }
	
         
}