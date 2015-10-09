<?php

/**
 * Customer account controller
 *
 */
require_once 'Mage/Customer/controllers/AccountController.php';
class ICC_Volumelicense_AccountController extends Mage_Customer_AccountController {
    public function usersAction() {
       $volumeid = (int)$this->getRequest()->getParam('vid');
       
        $volumedata = Mage::getModel('volumelicense/volumelicense')->load($volumeid);
        $customer = Mage::getModel('customer/customer')->load($volumedata->getCustomerId()); 
        if ($customer->getId() != Mage::getSingleton('customer/session')->getCustomer()->getId())
            {
              $this->_redirect('*/*/*/');
              //throw new Exception('Attempt to add user to volumelicense not owned by customer');
            }
		if ($volumeid) {
        	$volumelicense = Mage::getModel('volumelicense/volumelicense')->load($volumeid);
        	Mage::register('current_volumelicense', $volumelicense);
			$this->loadLayout();
        	$this->_initLayoutMessages('customer/session');
			$this->getLayout()->getBlock('head')->setTitle($this->__('Volume License Users'));
                        $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
                        if ($navigationBlock) {
                            $navigationBlock->setActive('ecodes/account/products');
                        }
			$this->renderLayout(); 
		} else {
			$this->_redirect('/ecodes/accounts/products');
		}
    }
    /**
     * This method add registry cunstomer for given volume license id and customer data. 
     * @param volumelicenseId , email_id = new customer emal id. 
     * @return render valuem license edit page. 
     */
    
    public function adduserAction() {
     if ($this->getRequest()->isPost()) 
         {
              $helper=Mage::helper('volumelicense');   
              $session = $this->_getSession();
              $volumelicenseId = $this->getRequest()->getPost('volumelicenseId');
              $volumedata = Mage::getModel('volumelicense/volumelicense')->load($volumelicenseId);
              $customer = Mage::getModel('customer/customer')->load($volumedata->getCustomerId()); 
              if ($customer->getId() != Mage::getSingleton('customer/session')->getCustomer()->getId())
                  {
                    throw new Exception('Attempt to add user to volumelicense not owned by customer');
                  }
              $email = $this->getRequest()->getPost('email_id');
              //echo $email; exit;
              $customerNew = Mage::getModel("customer/customer"); 
              $customerNew->setWebsiteId(Mage::app()->getWebsite()->getId()); 
              $customerNew->loadByEmail($email);
              $old_customer = Mage::getModel("customer/customer")->load($volumedata->getCustomerId()); 
              $old_customer_name = $old_customer->getName();
              //print_r($customer1->getData());die;
              if($customerNew->getId())
              {
               $result = Mage::getModel('volumelicense/registry')->getCollection()->getByVolumelicenseAndUserId($volumelicenseId, $customerNew->getId());
                if (!sizeof($result->getData())) 
                {
                    $volUser = Mage::getModel('volumelicense/registry');
                    $volUser->setVolumelicenseId($volumelicenseId);
                    $volUser->setAssignCustomerId($customerNew->getId());
                    
                    $cust_id=0;
                    if($customerNew->getId() == $volumedata->getCustomerId() ){
                        $cust_id = 0;
                    }else {
                            $cust_id = $volumedata->getCustomerId();
                    }
                    
                    $volUser->setParentCustomerId($cust_id);
                    $volUser->setAssignCustomerEmail($customerNew->getEmail());
                    $volUser->setAssignStatus(ICC_Volumelicense_Helper_Data::ACTIVE);
                    $volUser->setCreatedDate(now());
                     try{
                            $volUser->save();
                            $session->addSuccess('The user has been added successfully');
                         }
                         catch (Exception $msg){
                            throw $msg->getMessage();
                         }
                         $old_customer = Mage::getModel("customer/customer")->load($volumedata->getCustomerId()); 
                         $old_customer_name = $old_customer->getName();
                         $orderitemid = Mage::getModel('volumelicense/volumelicense')->load($volumelicenseId);
                         $orderitem_id=$orderitemid->getOrderItemId();
                         $purchased = Mage::getModel('downloadable/link_purchased_item')->getCollection()->addFieldToFilter('order_item_id',$orderitem_id); 
                        foreach($purchased as $link)
                           {
                           $linkmodel = Mage::getModel("volumelicense/links");
                           $linkmodel->setRegistryId($volUser->getId());
                           $linkmodel->setLinkId($link->getLinkId());
                           $linkmodel->setPurItemId($link->getItemId());
                           $downloadlink=Mage::getModel('downloadable/link')->load($link->getLinkId());
                           $linkmodel->setLinkDownloadLimit($downloadlink->getNumberOfDownloads());
                           $linkmodel->save();
                           }
                        Mage::helper('volumelicense')->updateVolumelicenseRegisteredCount($volumelicenseId);
                        $helper->volumeLicenseShareEmail($email,$customerNew->getName(),$old_customer_name,$volumedata->getProductName(),$volumedata->getSeatsTotal());
                }
                else 
                 {
                         $session->addError('The user is already added for this subscription.');
                 }
               }
               else
               { 
                $result = Mage::getModel('volumelicense/registry')->getCollection()->getByVolumelicenseAndUserEmail($volumelicenseId, $email);
                if (!sizeof($result->getData())) {
                         $volUser = Mage::getModel('volumelicense/registry');
                         $volUser->setVolumelicenseId($volumelicenseId);
                         $volUser->setAssignCustomerId($customerNew->getId());
                         $volUser->setParentCustomerId($volumedata->getCustomerId());
                         $volUser->setAssignCustomerEmail($email);
                         $volUser->setAssignStatus(ICC_Volumelicense_Helper_Data::PENDING);
                         $volUser->setCreatedDate(now());
                         try{
                            $volUser->save();
                            $session->addNotice("Entered Customer Not present in ICC Storefront,Notified him by email");
                         }
                         catch (Exception $msg){
                            throw $msg->getMessage();
                         }
                         
                            
                        $helper = Mage::helper('volumelicense'); 
                         
                        $helper->volumeLicenseShareEmail($email,$customerNew->getName(),$old_customer_name,$volumedata->getProductName(),$volumedata->getSeatsTotal());
                        Mage::helper('volumelicense')->updateVolumelicenseRegisteredCount($volumelicenseId);
                        $orderitemid = Mage::getModel('volumelicense/volumelicense')->load($volumelicenseId);
                        $orderitem_id=$orderitemid->getOrderItemId();
                        $purchased = Mage::getModel('downloadable/link_purchased_item')->getCollection()->addFieldToFilter('order_item_id',$orderitem_id); 
                        foreach($purchased as $link)
                           {
                           $linkmodel = Mage::getModel("volumelicense/links");
                           $linkmodel->setRegistryId($volUser->getId());
                           $linkmodel->setLinkId($link->getLinkId());
                           $linkmodel->setPurItemId($link->getItemId());
                           $downloadlink=Mage::getModel('downloadable/link')->load($link->getLinkId());
                           $linkmodel->setLinkDownloadLimit($downloadlink->getNumberOfDownloads());
                           $linkmodel->save();
                            //print_r($link);
                           }    
                        
                 } else {
                         $session->addError('The user is already added for this subscription');
                 }
          
               }
                $this->_redirect('volumelicense/account/users/vid/'.$volumelicenseId);
         }  
    }

    /**
     * This method used to removed share customer for given volume license id.
     * @param volumeid volume license id 
     * @retun return to current page with message. 
     */
    public function removeuserAction() {
        $session = $this->_getSession();
        $volumelicenseId = $this->getRequest()->getParam('volumeid');
        $vrid = $this->getRequest()->getParam('vrid'); 
        $volumedata = Mage::getModel('volumelicense/volumelicense')->load($volumelicenseId);
        $customer = Mage::getModel('customer/customer')->load($volumedata->getCustomerId());
        
        if ($customer->getId() != Mage::getSingleton('customer/session')->getCustomer()->getId()) {
                throw new Exception('Attempt to remove user from subscription not owned by customer');
        }
        $error=null;
        $result['success'] = true;
        if ($result['success']) {
                try{
                    $volumelicUser = Mage::getModel('volumelicense/registry')->load($vrid);
                    $volumelicUser->setUpdatedDate(now());
                    $volumelicUser->setAssignStatus(ICC_Volumelicense_Helper_Data::REMOVED);
                    $volumelicUser->save();
                }
                catch (Exception $msg){
                    throw $msg->getMessage();
                }
                Mage::helper('volumelicense')->updateVolumelicenseRegisteredCount($volumelicenseId);
                $session->addSuccess('The user has been removed from this subscription successfully.');
            } else {
                $error = $result['message'];
        }
        if ($error) $session->addError($error);
        $this->_redirect('volumelicense/account/users/vid/' . $volumelicenseId);
    }
    
    /**
     * This method used to render volume license edit page.
     */
    public function  editAction(){
       $registry_id = (int)$this->getRequest()->getParam('vrid');

		if ($registry_id) {
        	$registry = Mage::getModel('volumelicense/registry')->load($registry_id);
        	Mage::register('current_registry', $registry);
			$this->loadLayout();
        	$this->_initLayoutMessages('customer/session');
			$this->getLayout()->getBlock('head')->setTitle($this->__('Volume License Edit Users'));
                        $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
                        if ($navigationBlock) {
                            $navigationBlock->setActive('ecodes/account/products');
                        }
			$this->renderLayout(); 
		} else {
			$this->_redirect('/ecodes/accounts/products');
		}
    }
    
    /**
     * This method used to reassign the customer with remaining download limit.
     * @param volumelicese_id email_id
     */
    public function updateuserAction(){
        $helper = Mage::helper('volumelicense'); 
        $session = $this->_getSession();
        $volumelicenseId=$this->getRequest()->getParam('volumelicese_id');
        $volumedata = Mage::getModel('volumelicense/volumelicense')->load($volumelicenseId);
        $registry_id = $this->getRequest()->getParam('registry_id');
        $registry_old = Mage::getModel('volumelicense/registry')->load($registry_id);
        $email = $this->getRequest()->getParam('email_id');
        $customerNew = Mage::getModel("customer/customer"); 
        $customerNew->setWebsiteId(Mage::app()->getWebsite()->getId()); 
        $customerNew->loadByEmail($email);
        $old_customer = Mage::getModel("customer/customer")->load($volumedata->getCustomerId()); 
        $old_customer_name = $old_customer->getName();
              if($customerNew->getId())
              {
               $result = Mage::getModel('volumelicense/registry')->getCollection()->getByVolumelicenseAndUserId($volumelicenseId, $customerNew->getId());
                if (!sizeof($result->getData())) 
                {
                    $volUser = Mage::getModel('volumelicense/registry');
                    $volUser->setVolumelicenseId($registry_old->getVolumelicenseId());
                    $volUser->setAssignCustomerId($customerNew->getId());
                    
                    $cust_id=0;
                    if($customerNew->getId() == $registry_old->getCustomerId()){
                        $cust_id = 0;
                    }else {
                            $cust_id = $registry_old->getCustomerId();
                    }
                    
                    $volUser->setParentCustomerId($cust_id);
                    $volUser->setAssignCustomerEmail($email);
                    $volUser->setAssignStatus(ICC_Volumelicense_Helper_Data::ACTIVE);
                    $volUser->setCreatedDate(date('m/d/y h:i:s', time()));
                     try{
                            $volUser->save();
                            
                            $session->addSuccess('The user has been successfully added.');
                         }
                         catch (Exception $msg){
                            throw $msg->getMessage();
                         }
                         $registry_old->setReassignedTo($volUser->getId());
                         $registry_old->setAssignStatus(ICC_Volumelicense_Helper_Data::REMOVED);
                         $registry_old->save();
                         
                        $linkmodel_old = Mage::getModel("volumelicense/links")->getCollection()->getRegistryId($registry_old->getId());
                        foreach($linkmodel_old as $link)
                           {
                           $linkmodel = Mage::getModel("volumelicense/links");
                           $linkmodel->setRegistryId($volUser->getId());
                           $linkmodel->setLinkId($link->getLinkId());
                           $linkmodel->setPurItemId($link->getPurItemId());
                           $linkmodel->setLinkDownloadLimit($link->getLinkDownloadLimit());
                           $linkmodel->setNumberOfDownloads($link->getNumberOfDownloads());
                           $linkmodel->save();
                           }    

                        $helper->volumeLicenseShareEmail($email,$customerNew->getName(),$old_customer_name,$volumedata->getProductName(),$volumedata->getSeatsTotal());
                        
                }
                else 
                 {
                         $session->addError('The user is already added for this subscription');
                 }
               }
               else
               { 
                $result = Mage::getModel('volumelicense/registry')->getCollection()->getByVolumelicenseAndUserEmail($volumelicenseId, $email);
                               
                if (!sizeof($result->getData())) {
                         $volUser = Mage::getModel('volumelicense/registry');
                         $volUser->setVolumelicenseId($registry_old->getVolumelicenseId());
                         $volUser->setAssignCustomerId(0);
                         $volUser->setParentCustomerId($registry_old->getParentCustomerId());
                         $volUser->setAssignCustomerEmail($email);
                         $volUser->setAssignStatus(ICC_Volumelicense_Helper_Data::PENDING);
                         $volUser->setCreatedDate(date('m/d/y h:i:s', time()));
                         try{
                            $volUser->save();
                            $session->addNotice("Entered Customer not present in ICC Storefront, notified him by email");
                         }
                         catch (Exception $msg){
                            throw $msg->getMessage();
                         }
                         
                         $registry_old->setReassignedTo($volUser->getId());
                         $registry_old->setAssignStatus(ICC_Volumelicense_Helper_Data::REMOVED);
                         $registry_old->save();
                         
                        $helper = Mage::helper('volumelicense'); 
                         
                        $helper->volumeLicenseShareEmail($email,$customerNew->getName(),$old_customer_name,$volumedata->getProductName(),$volumedata->getSeatsTotal());
                         $orderitemid = Mage::getModel('volumelicense/volumelicense')->load($volumelicenseId);
                         $orderitem_id=$orderitemid->getOrderItemId();
                       $linkmodel_old = Mage::getModel("volumelicense/links")->getCollection()->getRegistryId($registry_old->getId());
                        foreach($linkmodel_old as $link)
                           {
                           $linkmodel = Mage::getModel("volumelicense/links");
                           $linkmodel->setRegistryId($volUser->getId());
                           $linkmodel->setLinkId($link->getLinkId());
                           $linkmodel->setPurItemId($link->getItemId());
                           $linkmodel->setLinkDownloadLimit($link->getLinkDownloadLimit());
                           $linkmodel->setNumberOfDownloads($link->getNumberOfDownloads());
                           $linkmodel->save();
                           }    
                        
                 } else {
                         $session->addError('The user is already added for this subscription');
                 }
          
               }
                 $this->_redirect('volumelicense/account/users/vid/' . $volumelicenseId);
    }
    /**
     * Tempary action for check 2 days and 2 weeks notification mail.
     */
     public function notifyAction(){ // for test notification mail 
        $volumelicense = Mage::getModel('volumelicense/volumelicense');
        $volumelicense->notificationEmail();
        echo "hi";
    }
}