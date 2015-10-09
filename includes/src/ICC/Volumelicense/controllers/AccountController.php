<?php

/**
 * Customer account controller
 *
 */
require_once 'Mage/Customer/controllers/AccountController.php';
class ICC_Volumelicense_AccountController extends Mage_Customer_AccountController {
    public function usersAction() {
       $parentItemId = (int)$this->getRequest()->getParam('pid');
       $parentOrderId = (int)$this->getRequest()->getParam('order_id');
       $parentOrderProduct = Mage::getModel('sales/order_item')->load($parentItemId);
       $parentOrderData = Mage::getModel('sales/order')->load($parentOrderId);
       $customer = Mage::getModel('customer/customer')->load($parentOrderData->getCustomerId()); 
        if ($customer->getId() != Mage::getSingleton('customer/session')->getCustomer()->getId())
            {
              $this->_redirect('*/*/*/');
              //throw new Exception('Attempt to add user to volumelicense not owned by customer');
            }
       
       if($parentItemId) {
	    Mage::register('current_parent_order', $parentOrderData);
	    Mage::register('current_parent_product', $parentOrderProduct);
	    
	    $childOrders = Mage::getModel('sales/order')->getCollection();
	    $childOrders->addFieldToFilter('parent_order_item_id',array('eq'=>$parentItemId));
	    //echo "<pre>";print_r($childOrders->getData());die;
	    Mage::register('current_child_order', $childOrders);
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
    
		$child_order_id = (int)$this->getRequest()->getParam('child_order_id');
		$parent_order_id = (int)$this->getRequest()->getParam('parent_product_id');
		$customerId = Mage::getModel('sales/order')->load($parent_order_id)->getCustomerId(); 
		//echo $customer."=====".Mage::getSingleton('customer/session')->getCustomer()->getId();die;
		if ($customerId != Mage::getSingleton('customer/session')->getCustomer()->getId())
		{
		$this->_redirect('*/*/*/');
		//throw new Exception('Attempt to add user to volumelicense not owned by customer');
		}

		if ($child_order_id) {
        	$order = Mage::getModel('sales/order')->load($child_order_id);
        	Mage::register('current_order', $order);
        	Mage::register('parent_order_id', $parent_order_id);
        	
        	Mage::register('current_child_order_id', $child_order_id);
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
       
	$login_customer = Mage::getSingleton('customer/session')->getCustomer();
        $post_data = $this->getRequest()->getPost();
        $helper  = Mage::helper('volumelicense');
        $order =  Mage::getModel('sales/order')->load($this->getRequest()->getPost('current_order'));
        //echo "<pre>";print_r($order->getData());die;
        $parent_order_id = $order->getParentOrderId();
        $storeId = $order->getStoreId();
        $oldCustomerEmail = $order->getCustomerEmail();
        //$previousFutureEmail = $order->getFutureEmail();
        $oldCustomerName = $order->getCustomerFirstname().' '. $order->getCustomerLastname();
        $parentOrderProductName = Mage::getModel('sales/order_item')->load($order->getParentOrderItemId())->getName();
        $customerRegisterUrl = Mage::getUrl('customer/account/create');
        $myEcodeUrl = Mage::getUrl('ecodes/account/products');
        //echo "<pre>";print_r($order->getData());die;
        $future_email = false;
        $child_emails = $helper->getChildEmailIds($parent_order_id,$this->getRequest()->getPost('product_id'));
        $sendEmail = '';
        //echo "<pre>";print_r($post_data);print_r($child_emails);die;
        if ($post_data) {
            if(in_array($this->getRequest()->getPost('customer_email'), $child_emails)){
                Mage::getSingleton("customer/session")->addError("An order is already assigned to ".$this->getRequest()->getPost('customer_email').'.');
                Mage::getSingleton("customer/session")->setAssignData($this->getRequest()->getPost());
                //die("innnnn");
                $this->_redirect("*/*/edit", array("child_order_id" => $this->getRequest()->getPost('current_order'), "parent_product_id" => $parent_order_id));
                return;
            }
            try { 
                $customer = Mage::getModel('customer/customer')
                            ->getCollection()
                            ->addAttributeToFilter('email', $this->getRequest()->getPost('customer_email'))
                            ->addAttributeToFilter('store_id', $storeId)
                            ->getFirstItem();
                $customerData = Mage::getModel('customer/customer')->load($customer->getId());
                if (is_null($customer->getId())){
                
		   if( $order->getFutureEmail()!=null || $order->getFutureEmail() !='' ) {
                   $successMsg = "Volume License is assigned from ".$order->getFutureEmail()." to ".$this->getRequest()->getPost('customer_email').".";
                   }
                   else {
                   $successMsg = "Volume License is assigned from ".$order->getCustomerEmail()." to ".$this->getRequest()->getPost('customer_email').".";
                   }
                   $future_email = true;
                   $parent_order =  Mage::getModel('sales/order')->load($parent_order_id);
                   $newCustomerEmail = $parent_order->getCustomerEmail();
                   $order->setCustomerId($parent_order->getCustomerId());
                   $order->setFutureEmail($this->getRequest()->getPost('customer_email'));
                   $order->setCustomerEmail($newCustomerEmail);
                   //echo "<pre>";print_r($login_customer->getData());die;
                   //echo $login_customer->getFirstname();die("=====");
                   $history = $order->addStatusHistoryComment('The owner has been changed from customer: ' . $order->getCustomerEmail() . ' to customer: ' . $newCustomerEmail . ' by user ' . $login_customer->getFirstname(). ' '.$login_customer->getLastname()
                           .". This order will be assigned to ".$this->getRequest()->getPost('customer_email')." in future when the customer will register and login in ICC Store.");
                   $history->setIsCustomerNotified(true);
                   $purchasingAgentMailData = "<strong>Dear ".$this->getRequest()->getPost('customer_email')." </strong>,<br/><p> <b> $oldCustomerName </b> have assigned you Volume license product $parentOrderProductName.</p> <p>You are not registered with ICC StoreFront.Please click here for registeration <a href='".$customerRegisterUrl."'>click here </a>.</p>";
                   $sendEmail = $this->getRequest()->getPost('customer_email');
                   //echo $successMsg;die("innnn");
                } else {
                    if($customerData){
                        $order->setCustomerFirstname($customerData->getFirstname());
                        $order->setCustomerLastname($customerData->getLastname());
                    }
                    
		    if( $order->getFutureEmail()!=null || $order->getFutureEmail() !='' ) {
		    $successMsg = "Volume License is assigned from ".$order->getFutureEmail()." to ".$this->getRequest()->getPost('customer_email').".";
		    }
		    else {
		    $successMsg = "Volume License is assigned from ".$order->getCustomerEmail()." to ".$this->getRequest()->getPost('customer_email').".";
		    }
                    
                    $newCustomerEmail = $customer->getEmail();
                    $order->setCustomerId($customer->getId());
                    $order->setCustomerEmail($customer->getEmail());
                    $order->setFutureEmail(NULL);
                    //echo  $login_customer->getFirstname();die;
                    $history = $order->addStatusHistoryComment('The owner has been changed from customer: ' . $oldCustomerEmail . ' to customer: ' . $customer->getEmail() . ' by user ' . $login_customer->getFirstname(). ' '.$login_customer->getLastname());
                    $history->setIsCustomerNotified(true);
                    $purchasingAgentMailData = "<strong>Dear ".$customerData->getFirstname().' '.$customerData->getLastname()." </strong>,<br/><p><b> $oldCustomerName </b> have assigned you Volume license product $parentOrderProductName.</p> <p> To View the item <a href='".$myEcodeUrl."'>click here </a></p>";
                    $sendEmail = $customer->getEmail();
                    //echo $successMsg;die("outttt");
                }
                
                //echo $successMsg;die;
                //echo $purchasingAgentMailData;die("Stoppp");
             $items = $order->getAllItems();    
           try{ 
                $order->save();
                Mage::log('Order #' . $order->getIncrementId() .' was reassigned from customer: ' . $oldCustomerEmail . ' to customer: ' . $newCustomerEmail, true, 'order_owner.log', true);
                foreach($items as $item){
                    if ($item->getProductType() == 'downloadable'){
                        $downloadableLinks = Mage::getModel('downloadable/link_purchased')
                                ->getCollection()
                                ->addFieldToFilter('order_item_id', $item->getItemId());

                        foreach ($downloadableLinks->getItems() as $link){
                            $link->setCustomerId($customer->getId());
                            $link->save();
                            Mage::log('Order\'s #' . $order->getIncrementId() . ' downloadable link (order item id: ' . $link->getOrderItemId() . '. purchased_id: ' .$link->getPurchasedId(). ') has been moved to customer: ' . $newCustomerEmail, true, 'order_owner.log', true);
                        }
                    }

                }
            
            }catch (Exception $e){
                Mage::getSingleton("customer/session")->addError($e->getMessage());
                Mage::getSingleton("customer/session")->setAssignData($this->getRequest()->getPost());
                $this->_redirect("*/*/edit", array("child_order_id" => $this->getRequest()->getPost('current_order'), "parent_product_id" => $parent_order_id));
                return;
            
            }
            //echo $future_email."====".$oldCustomerEmail."====".$previousFutureEmail."====".$newCustomerEmail."=====".$this->getRequest()->getPost('customer_email');die;
            if(!$future_email){ //die("innnn");
                //Mage::getSingleton('customer/session')->addSuccess(Mage::helper('cms')->__('Order #' . $order->getIncrementId() .' was successfully reassigned from customer: ' . $oldCustomerEmail .' to customer: ' . $newCustomerEmail.'.'));
                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('cms')->__($successMsg));
            }else{ //die("outtt");
                 //Mage::getSingleton('customer/session')->addSuccess(Mage::helper('cms')->__('Order #' . $order->getIncrementId() .' was successfully reassigned from customer: ' . $oldCustomerEmail .' to customer: ' . $newCustomerEmail .''
                 Mage::getSingleton('customer/session')->addSuccess(Mage::helper('cms')->__($successMsg));
            }
            Mage::getSingleton('customer/session')->setFormData(false);
       
            //Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$parent_order_id)));
            //echo  $this->getRequest()->getPost('pid')."====".$this->getRequest()->getPost('order_id');die;
            $helper->setReportsLog($order,true);
            $helper->volumeLicenseShareEmail($sendEmail,$purchasingAgentMailData,$login_customer->getEmail());
            $this->_redirect("*/*/users", array("pid" => $this->getRequest()->getPost('pid'), "order_id" => $this->getRequest()->getPost('order_id')));
            //die("innnn");
            //$this->_redirect("*/*/edit", array("child_order_id" => $this->getRequest()->getPost('current_order'), "parent_product_id" => $parent_order_id));
            return;
            } catch (Exception $e) {
                Mage::getSingleton("customer/session")->addError($e->getMessage());
                Mage::getSingleton("customer/session")->setAssignData($this->getRequest()->getPost());
                //$this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id"), "parent" => $parent_order_id));
                $this->_redirect("*/*/edit", array("child_order_id" => $this->getRequest()->getPost('current_order'), "parent_product_id" => $parent_order_id));
                return;
            }
        }
        //die("innnn");
        //Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$parent_order_id)));
        
        $this->_redirect("*/*/users", array("pid" => $this->getRequest()->getPost('pid'), "order_id" => $this->getRequest()->getPost('order_id')));
       
       
       
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