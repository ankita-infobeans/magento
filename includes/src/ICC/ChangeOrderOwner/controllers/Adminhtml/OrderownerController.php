<?php
class ICC_ChangeOrderOwner_Adminhtml_OrderownerController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction(){
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('icc_changeorderowner/adminhtml_form'));
        $this->renderLayout();
    }

    /**
     * TODO: create resource model and store data on orders which were moved there
     */
    public function savePostAction(){

        $data    = $this->getRequest()->getPost();
        $helper  = Mage::helper('icc_changeorderowner');
        $isValid = $helper->validate($data);
        if($isValid !== true){
            $this->_addErrorMessage($isValid);
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            $this->_redirect('*/*');
            return;
        }

        $customer = $helper->getNewOwner();
        $order    = $helper->getMovedOrder();

        $oldCustomerEmail = $order->getCustomerEmail();
	//echo "<pre>";print_r($order->getData());//die;
        $order->setCustomerId($customer->getId());
        $order->setCustomerFirstname($customer->getFirstname());
        $order->setCustomerLastname($customer->getLastname());
        $order->setCustomerEmail($customer->getEmail());
        $order->addStatusHistoryComment('The owner has been changed from customer: ' . $oldCustomerEmail .' to customer: ' . $customer->getEmail() . '. By user-initiated: ' . Mage::getSingleton('admin/session')->getUser()->getUsername());

        $items = $order->getAllItems();
        Mage::log('Start order #' . $order->getIncrementId() .' moving! User-initiated: ' .Mage::getSingleton('admin/session')->getUser()->getUsername(), true, 'order_owner.log', true);

        try{
            $order->save();
            if(Mage::helper('core')->isModuleEnabled('ICC_Volumelicense')){
                //add data in volumelicense reports
                if($order->getVolumeLicense()) {
                Mage::helper('volumelicense')->setReportsLog($order, TRUE);
                }
            }
		if($order->getPremiumAccess()) {
                Mage::helper('icc_premiumaccess')->setReportsLog($order, TRUE);
                }
            Mage::log('Order #' . $order->getIncrementId() .' was moved from customer: ' . $oldCustomerEmail . ' to customer: ' . $customer->getEmail(), true, 'order_owner.log', true);
            foreach($items as $item){
                if ($item->getProductType() == 'downloadable'){
                    $downloadableLinks = Mage::getModel('downloadable/link_purchased')
                            ->getCollection()
                            ->addFieldToFilter('order_item_id', $item->getItemId());

                    foreach ($downloadableLinks->getItems() as $link){
                        $link->setCustomerId($customer->getId());
                        $link->save();
                        Mage::log('Order\'s #' . $order->getIncrementId() . ' downloadable link (order item id: ' . $link->getOrderItemId() . '. purchased_id: ' .$link->getPurchasedId(). ') has been moved to customer: ' . $customer->getEmail(), true, 'order_owner.log', true);
                    }
                    
                    $childOrders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('parent_order_id',$order->getId())
                                                                                //->addFieldToFilter('future_email',array('neq'=>''))
                                                                                ->addFieldToFilter('customer_email',array('eq'=>$oldCustomerEmail));
                   // echo "<pre>"; print_r($childOrders->getSelect().''); die;
                    if(count($childOrders)>0):
                        foreach($childOrders as $childOrder):
                            $corder=Mage::getModel('sales/order')->load($childOrder->getId());
                            $corder->setCustomerId($customer->getId());
                            $corder->setCustomerFirstname($customer->getFirstname());
                            $corder->setCustomerLastname($customer->getLastname());
                            $corder->setCustomerEmail($customer->getEmail());
                            $citems = $corder->getAllItems();
                            try{
                                $corder->save();
                                if(Mage::helper('core')->isModuleEnabled('ICC_Volumelicense')){
                                    //add data in volumelicense reports
                                     Mage::helper('volumelicense')->setReportsLog($corder, TRUE);
                                }
                                Mage::log('Order #' . $corder->getIncrementId() .' was moved from customer: ' . $oldCustomerEmail . ' to customer: ' . $customer->getEmail(), true, 'order_owner.log', true);
                                foreach($citems as $citem){
                                    if ($citem->getProductType() == 'downloadable'){
                                        $downloadableLinks = Mage::getModel('downloadable/link_purchased')
                                                ->getCollection()
                                                ->addFieldToFilter('order_item_id', $citem->getItemId());

                                        foreach ($downloadableLinks->getItems() as $clink){
                                            $clink->setCustomerId($customer->getId());
                                            $clink->save();
                                            Mage::log('Order\'s #' . $corder->getIncrementId() . ' downloadable link (order item id: ' . $clink->getOrderItemId() . '. purchased_id: ' .$clink->getPurchasedId(). ') has been moved to customer: ' . $customer->getEmail(), true, 'order_owner.log', true);
                                        }
                                    }
                                }
                            }
                            catch (Exception $e) {
                                Mage::log('Error occurred while moving the order #' . $corder->getIncrementId() . '! with message: ' . $e->getMessage() . PHP_EOL . debug_backtrace(), true, 'order_owner.log', true);
                                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                                //Mage::getSingleton('adminhtml/session')->setFormData($data);
                            }
                        endforeach;
                    endif;  
                }
                else {
		      $childOrders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('parent_order_id',$order->getId())
                                                                                ->addFieldToFilter('customer_email',array('eq'=>$oldCustomerEmail));
		      if(count($childOrders)>0):
                        foreach($childOrders as $childOrder):
                            $corder=Mage::getModel('sales/order')->load($childOrder->getId());
                            $corder->setCustomerId($customer->getId());
                            $corder->setCustomerFirstname($customer->getFirstname());
                            $corder->setCustomerLastname($customer->getLastname());
                            $corder->setCustomerEmail($customer->getEmail());
                            $citems = $corder->getAllItems();
                            try{
                                $corder->save();
                                    //add data in icc_premiumaccess reports
                                     Mage::helper('icc_premiumaccess')->setReportsLog($corder, TRUE);
                                Mage::log('Order #' . $corder->getIncrementId() .' was moved from customer: ' . $oldCustomerEmail . ' to customer: ' . $customer->getEmail(), true, 'order_owner.log', true);
                            }
                            catch (Exception $e) {
                                Mage::log('Error occurred while moving the order #' . $corder->getIncrementId() . '! with message: ' . $e->getMessage() . PHP_EOL . debug_backtrace(), true, 'order_owner.log', true);
                                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                                //Mage::getSingleton('adminhtml/session')->setFormData($data);
                            }
                        endforeach;
                    endif;  
                }
                
                
            }
            
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('cms')->__('Order #' . $order->getIncrementId() .' was successfully moved from customer: ' . $oldCustomerEmail .' to customer: ' . $customer->getEmail()));
            Mage::getSingleton('adminhtml/session')->setFormData(false);
        } catch (Exception $e) {
            Mage::log('Error occurred while moving the order #' . $order->getIncrementId() . '! with message: ' . $e->getMessage() . PHP_EOL . debug_backtrace(), true, 'order_owner.log', true);
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
        }
        Mage::log('End Order #' . $order->getIncrementId() .' moving!', true, 'order_owner.log', true);
        $this->_redirect('*/*');

        return;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/sales/changeOwner');
    }

    protected function _addErrorMessage($msg){
        if (is_array($msg)){
            foreach($msg as $item){
                Mage::getSingleton('adminhtml/session')->addError($item);
            }
        }else{
            Mage::getSingleton('adminhtml/session')->addError($msg);
        }
    }
}