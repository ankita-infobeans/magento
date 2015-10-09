<?php
class ICC_Premiumaccess_Adminhtml_PremiumassignController extends Mage_Adminhtml_Controller_Action {

    public function editAction() {
        $id = $this->getRequest()->getParam("id");
        $this->_title($this->__("premiumACCESS"));
        
        $model = Mage::getModel('sales/order') ->load($id);
        $orderNumber = $model->getIncrementId();
       
       $this->_title($this->__("Reassign Order#: ".$orderNumber));
        $parent = $this->getRequest()->getParam("parent");
        if ($model->getId()) {
            Mage::register("assign_data", $model);
            $this->loadLayout();
            $this->_setActiveMenu("icc_premiumaccess/assign");
            $this->_addBreadcrumb(Mage::helper("adminhtml")->__("Assign Manager"), Mage::helper("adminhtml")->__("Reassign Order"));
            $this->getLayout()->getBlock("head")->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock("icc_premiumaccess/adminhtml_assign_edit"))->_addLeft($this->getLayout()->createBlock("icc_premiumaccess/adminhtml_assign_edit_tabs"));
            $this->renderLayout();
        } else {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("icc_premiumaccess")->__("Child Order does not exist."));
            Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$parent)));
        }
    }

    public function saveAction() {
        $post_data = $this->getRequest()->getPost();
        $helper  = Mage::helper('icc_premiumaccess');
        $order =  Mage::getModel('sales/order')->loadByIncrementId($this->getRequest()->getPost('increment_id'));
        
        $orderedItems = $order->getAllVisibleItems();
        foreach ($orderedItems as $item) {
            $product_id = $item->getData('product_id');
            break;
        }
        
        $parent_order_id = $order->getParentOrderId();
        $parentOrderData = Mage::getModel('sales/order')->load($parent_order_id);
        $parentEmail = $parentOrderData->getCustomerEmail();
        $parentCustName = $parentOrderData->getCustomerFirstname().' '.$parentOrderData->getCustomerLastname();
        $storeId = $order->getStoreId();
        $oldCustomerEmail = $order->getCustomerEmail();
        $parentOrderProductName = Mage::getModel('sales/order_item')->load($order->getParentOrderItemId())->getName();
        $customerRegisterUrl = Mage::getUrl('customer/account/create');
        $myEcodeUrl = Mage::getUrl('ecodes/account/products');
        $future_email = false;
        $child_emails = $helper->getChildEmailIds($parent_order_id, $product_id);
        
        if ($post_data) {
            if(in_array($this->getRequest()->getPost('customer_email'), $child_emails)){
                Mage::getSingleton("adminhtml/session")->addError("An order is already assigned to ".$this->getRequest()->getPost('customer_email').'.');
                Mage::getSingleton("adminhtml/session")->setAssignData($this->getRequest()->getPost());
                $this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id"), "parent" => $parent_order_id));
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
                   $history = $order->addStatusHistoryComment('The owner has been reassigned from customer: ' . $order->getCustomerEmail() . ' to customer: ' . $newCustomerEmail . ' by user ' . Mage::getSingleton('admin/session')->getUser()->getUsername()
                           .". This order will be assigned to ".$this->getRequest()->getPost('customer_email')." in future when the customer will register and login in ICC Store.");
                   $history->setIsCustomerNotified(true);
                   $purchasingAgentMailData = "<strong>Dear ".$this->getRequest()->getPost('customer_email')." </strong>,<br/><p> <b> $parentCustName </b> have assigned you premiumACCESS product $parentOrderProductName.</p> <p>You are not registered with ICC StoreFront.Please click here for registeration <a href='".$customerRegisterUrl."'>click here </a>.</p>";
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
                    $history = $order->addStatusHistoryComment('The owner has been changed from customer: ' . $oldCustomerEmail . ' to customer: ' . $customer->getEmail() . ' by user ' . Mage::getSingleton('admin/session')->getUser()->getUsername());
                    $history->setIsCustomerNotified(true);
                    $purchasingAgentMailData = "<strong>Dear ".$customerData->getFirstname().' '.$customerData->getLastname()." </strong>,<br/><p><b> $parentCustName </b> have assigned you premiumACCESS product $parentOrderProductName.</p> <p> To View the item <a href='".$myEcodeUrl."'>click here </a></p>";
                    $sendEmail = $customer->getEmail();
                }
	    $order->save();
	    $helper->setReportsLog($order,TRUE);
	    $helper->premiumAccessShareEmail($sendEmail,$purchasingAgentMailData,$parentEmail);
            if(!$future_email){
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('cms')->__($successMsg));
            }else{
                 Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('cms')->__($successMsg));
            }
                Mage::getSingleton('adminhtml/session')->setFormData(false);
            if ($this->getRequest()->getParam("back")) {
                $this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id"), "parent" => $parent_order_id));
                return;
            }
            Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$parent_order_id)));
            return;
            } catch (Exception $e) {
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                Mage::getSingleton("adminhtml/session")->setAssignData($this->getRequest()->getPost());
                $this->_redirect("*/*/edit", array("id" => $this->getRequest()->getParam("id"), "parent" => $parent_order_id));
                return;
            }
        }
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$parent_order_id)));
    }

}
