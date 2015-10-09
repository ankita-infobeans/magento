<?php

/**
 * Customer account controller
 *
 */
require_once 'Mage/Customer/controllers/AccountController.php';
class ICC_Premiumaccess_AccountController extends Mage_Customer_AccountController {
    
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
	    Mage::register('premium_current_parent_order', $parentOrderData);
	    Mage::register('premium_current_parent_product', $parentOrderProduct);
	    
	    $childOrders = Mage::getModel('sales/order')->getCollection();
	    $childOrders->addFieldToFilter('parent_order_item_id',array('eq'=>$parentItemId));
            $childOrders->addFieldToFilter('status',array('neq'=>'canceled'));
	    //echo "<pre>";print_r($childOrders->getData());die;
	    Mage::register('premium_current_child_order', $childOrders);
		  $this->loadLayout();
		  $this->_initLayoutMessages('customer/session');
		  $this->getLayout()->getBlock('head')->setTitle($this->__('premiumACCESS Users'));
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
     * This method used to render premium access edit page.
     */
    public function  editAction(){
    
		$child_order_id = (int)$this->getRequest()->getParam('child_order_id');
		$parent_order_id = (int)$this->getRequest()->getParam('parent_product_id');
		$customerId = Mage::getModel('sales/order')->load($parent_order_id)->getCustomerId(); 
		//echo $customer."=====".Mage::getSingleton('customer/session')->getCustomer()->getId();die;
		if ($customerId != Mage::getSingleton('customer/session')->getCustomer()->getId())
		{
		$this->_redirect('*/*/*/');
		//throw new Exception('Attempt to add user to premiumACCESS not owned by customer');
		}

		if ($child_order_id) {
        	$order = Mage::getModel('sales/order')->load($child_order_id);
        	Mage::register('premium_current_order', $order);
        	Mage::register('premium_parent_order_id', $parent_order_id);
        	
        	Mage::register('premium_current_child_order_id', $child_order_id);
			$this->loadLayout();
        	$this->_initLayoutMessages('customer/session');
			$this->getLayout()->getBlock('head')->setTitle($this->__('premiumACCESS Edit Users'));
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
     * This method used to reassign the customer .
     * @param email_id
     */
    public function updateuserAction(){
       
	$login_customer = Mage::getSingleton('customer/session')->getCustomer();
        $post_data = $this->getRequest()->getPost();
        $helper  = Mage::helper('icc_premiumaccess');
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
                   $successMsg = "premiumACCESS is assigned from ".$order->getFutureEmail()." to ".$this->getRequest()->getPost('customer_email').".";
                   }
                   else {
                   $successMsg = "premiumACCESS is assigned from ".$order->getCustomerEmail()." to ".$this->getRequest()->getPost('customer_email').".";
                   }
                   $future_email = true;
                   $parent_order =  Mage::getModel('sales/order')->load($parent_order_id);
                   $newCustomerEmail = $parent_order->getCustomerEmail();
                   $order->setCustomerId($parent_order->getCustomerId());
                   $order->setFutureEmail($this->getRequest()->getPost('customer_email'));
                   $order->setCustomerEmail($newCustomerEmail);
                   $history = $order->addStatusHistoryComment('The owner has been changed from customer: ' . $order->getCustomerEmail() . ' to customer: ' . $newCustomerEmail . ' by user ' . $login_customer->getFirstname(). ' '.$login_customer->getLastname()
                           .". This order will be assigned to ".$this->getRequest()->getPost('customer_email')." in future when the customer will register and login in ICC Store.");
                   $history->setIsCustomerNotified(true);
                   $purchasingAgentMailData = "<strong>Dear ".$this->getRequest()->getPost('customer_email')." </strong>,<br/><p> <b> $oldCustomerName </b> have assigned you premiumACCESS product $parentOrderProductName.</p> <p>You are not registered with ICC StoreFront.Please click here for registeration <a href='".$customerRegisterUrl."'>click here </a>.</p>";
                   $sendEmail = $this->getRequest()->getPost('customer_email');
                } else {
                    if($customerData){
                        $order->setCustomerFirstname($customerData->getFirstname());
                        $order->setCustomerLastname($customerData->getLastname());
                    }
                    
		    if( $order->getFutureEmail()!=null || $order->getFutureEmail() !='' ) {
		    $successMsg = "premiumACCESS is assigned from ".$order->getFutureEmail()." to ".$this->getRequest()->getPost('customer_email').".";
		    }
		    else {
		    $successMsg = "premiumACCESS is assigned from ".$order->getCustomerEmail()." to ".$this->getRequest()->getPost('customer_email').".";
		    }
                    
                    $newCustomerEmail = $customer->getEmail();
                    $order->setCustomerId($customer->getId());
                    $order->setCustomerEmail($customer->getEmail());
                    $order->setFutureEmail(NULL);
                    $history = $order->addStatusHistoryComment('The owner has been changed from customer: ' . $oldCustomerEmail . ' to customer: ' . $customer->getEmail() . ' by user ' . $login_customer->getFirstname(). ' '.$login_customer->getLastname());
                    $history->setIsCustomerNotified(true);
                    $purchasingAgentMailData = "<strong>Dear ".$customerData->getFirstname().' '.$customerData->getLastname()." </strong>,<br/><p><b> $oldCustomerName </b> have assigned you premiumACCESS product $parentOrderProductName.</p> <p> To View the item <a href='".$myEcodeUrl."'>click here </a></p>";
                    $sendEmail = $customer->getEmail();
                }
                
             $items = $order->getAllItems();    
           try{ 
                $order->save();
                Mage::log('Order #' . $order->getIncrementId() .' was reassigned from customer: ' . $oldCustomerEmail . ' to customer: ' . $newCustomerEmail, true, 'order_owner.log', true);
                
            
            }catch (Exception $e){
                Mage::getSingleton("customer/session")->addError($e->getMessage());
                Mage::getSingleton("customer/session")->setAssignData($this->getRequest()->getPost());
                $this->_redirect("*/*/edit", array("child_order_id" => $this->getRequest()->getPost('current_order'), "parent_product_id" => $parent_order_id));
                return;
            
            }
            
            if(!$future_email){
                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('cms')->__($successMsg));
            }else{
                 Mage::getSingleton('customer/session')->addSuccess(Mage::helper('cms')->__($successMsg));
            }
            Mage::getSingleton('customer/session')->setFormData(false);
       
            $helper->setReportsLog($order,true);
            $helper->premiumAccessShareEmail($sendEmail,$purchasingAgentMailData,$login_customer->getEmail());
            $this->_redirect("*/*/users", array("pid" => $this->getRequest()->getPost('pid'), "order_id" => $this->getRequest()->getPost('order_id')));
            return;
            } catch (Exception $e) {
                Mage::getSingleton("customer/session")->addError($e->getMessage());
                Mage::getSingleton("customer/session")->setAssignData($this->getRequest()->getPost());
                $this->_redirect("*/*/edit", array("child_order_id" => $this->getRequest()->getPost('current_order'), "parent_product_id" => $parent_order_id));
                return;
            }
        }
        $this->_redirect("*/*/users", array("pid" => $this->getRequest()->getPost('pid'), "order_id" => $this->getRequest()->getPost('order_id')));
    }
}