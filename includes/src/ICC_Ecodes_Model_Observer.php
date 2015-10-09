<?php

class ICC_Ecodes_Model_Observer extends Mage_Core_Model_Abstract
{

	public function associateSubscriptionsEvent($observer) {
		$invoice = $observer['invoice'];
		$this->assoicateSubscriptions($invoice);
                $this->updateRenewals($invoice);
	}

	public function assoicateSubscriptions($invoice) {
            $order = Mage::getModel('sales/order')->load($invoice->getOrderId());
            $configurableEcodeIds = array();
            foreach($order->getAllItems() as $item) {
                if ($item->getProductType() == 'virtual' ) { // && $item->getData('has_children')) {
                    //Check to make sure we haven't already entered this subscription
                    $subTest =  Mage::getModel('ecodes/premiumsubs')->getCollection()->getByOrderLineItemId($item->getItemId());  // do we just renew date instead?
                    if (sizeof($subTest) === 0) {
                        $product = Mage::getModel('catalog/product')->load($item->getProductId());
                        //949 = "premium_access"
                        if ($product->getItemType() == ICC_Ecodes_Helper_Data::ECODES_ITEM_TYPE || $product->getItemType() == 949) {
                            $configurableEcodeIds[] = $item->getItemId();
                            $expiration  = 0;
                            $options = $item->getProductOptions();

                             if(isset($options['attributes_info'])) {
                                $options = $options['attributes_info'];
                                foreach($options as $opt) {
                                    if ($opt['label'] == 'Duration') {
                                        $expiration = date('m/d/y', $this->getExpriationFromDurationValue($opt['value']));
                                    }
                                }
                            } else {
                                $expiration = date('m/d/y', $this->getExpriationFromDurationValue($product->getAttributeText("subscription_duration")));                          
                            }
                            $sub = Mage::getModel('ecodes/premiumsubs');
                            $sub->setCustomerId($order->getCustomerId());
                            $sub->setProductId($item->getProductId());
                            $sub->setOrderItemId($item->getItemId());
                            $sub->setOrderNumber($order->getIncrementId());
                            $sub->setProductName($item->getName());
                            $sub->setSku($item->getSku());
                            $sub->setExpiration($expiration);
                            $sub->setSeatsTotal(1);
                            $sub->setRegistered(1);
                            $sub->setCreatedAt(date('m/d/y h:i:s', time()));
                            $sub->save();
                            
                        }
                    }
                }
            }
    }

	public function registerSubscription($subscription) {
		$helper = Mage::helper('ecodes');
		$customer = Mage::getModel('customer/customer')->load($subscription->getCustomerId());

        $session = Mage::getSingleton('customer/session');
        $login_data = $session->getData('login_info');
        if($login_data) {
            $mUsername = $login_data['login'];
            $mPassword = $login_data['password'];
        }
        else {
    		$mUsername = $customer->getEcodesMasterUser();
    		$mPassword = $helper->decryptPassword($customer->getEcodesMasterPass());
        }
                        
		if ($customer) {
                    if(empty($mUsername)) // customer was able to get through checkout without a master user because ICC Connect was down
                    {
                        $q = Mage::getModel('gorilla_queue/queue')->addToQueue(
                                    'ecodes/apiQueue', // magento path to process class
                                    'processNotifiyUserCreateAccountQueueItem', // function to run
                                    array(
                                        'customer_id' => $customer->getId(),
                                        'subscription_sku' => $subscription->getSku(), 
                                        'subscription_expiration' => $subscription->getExpiration(),
                                    ),
                                    'customer_id_' . $customer->getId() // in the "code" section of the queue store the customer ID so we can retrieve it with a query
                                )->setShortDescription( 'Connection error with ICC Connect. Customer (with Magento ID ' . $subscription->getCustomerId() . ') could not create a master user.' )
                                ->save();
                    }
			$result = Mage::getModel('ecodes/api')->appendProduct($mUsername, $mPassword, $subscription->getSku(), $subscription->getExpiration());
			if ($result['success']) {
				$subscription->setRegistered(1);
				$subscription->save();

//                Mage::log(var_export($subscription,true),null,"ecode_api.log");

                $user = Mage::getModel('ecodes/premiumusers')->getCollection()->getByUsername($mUsername);

                if ($user->getId() == 0) {
                    $user = Mage::getModel('ecodes/premiumusers');
                    $user->setFirstname($customer->getFirstame());
                    $user->setLastname($customer->getLastname());
                    $user->setEmail($customer->getEmail());
                    $user->setUser($mUsername);
                    $user->setPass(Mage::helper('ecodes')->encryptPassword($mPassword));
                    $user->setCreatedAt(date('m/d/y h:i:s', time()));
                    $user->save();
                }


//                Mage::log(var_export($user,true),null,"ecode_api.log");

                $subUser = Mage::getModel('ecodes/premiumsubusers');
                $subUser->setSubsId($subscription->getId());
                $subUser->setUserId($user->getId());
                $subUser->setCreatedAt(date('m/d/y h:i:s', time()));
                $subUser->save();

//                Mage::log(var_export($subUser,true),null,"ecode_api.log");

			} else {
                $q = Mage::getModel('gorilla_queue/queue')->addToQueue(
                            'ecodes/apiQueue', // magento path to process class
                            'processApi', // function to run
                            array(
                                'MUser' => $mUsername,
                                'MPass' => $mPassword,
                                'Code' => $subscription->getSku(),
                                'ExpireDate' => $subscription->getExpiration()
                            ),
                            'customer_id_' . $customer->getId() // in the "code" section of the queue store the customer ID so we can retrieve it with a query
                        )->setShortDescription( 'Connection error with ICC Connect. Product: ' . $subscription->getSku() . ' could not be appended to ICC Connect' )
                        ->save();
				$session->addError("There is a problem executing your requested action. Please contact ICC’s Electronic Media Division by e-mail at support@ecodes.biz or at 1-888-422-7233 x 33822.");
			}
		} else {
			$session->addError("You must be registered user of the ICC website to qualify for this. If you have any questions or concerns, please contact ICC’s Electronic Media Division by e-mail at support@ecodes.biz or at 1-888-422-7233 x 33822.");
		}
	}

	protected function getExpriationFromDurationValue($v) {
		return strtotime(date("Y-m-d", time()) . " +" . $v);
	}    


    // catalog_controller_product_init
    public function ensureCustomerAccessRenewalProducts($observer)
    {
        $product = $observer['product'];
        
        $renewal = Mage::getModel('ecodes/renewal');
        if( ! $renewal->isRenewalProduct($product) )
        {
            return; // not a renwal
        }
        
        $customer_session = Mage::getSingleton('customer/session');
        if( ! $customer_session->isLoggedIn() )
        {
            $response = Mage::app()->getResponse();
            $response->setRedirect('customer/account/login/');
            return false;
        }

        
        $customer = $customer_session->getCustomer();        
        if(empty($customer) || ! $renewal->hasAccessToRenewal($product, $customer) )
        {
            $customer_session->addError(Mage::helper('ecodes')->__('Sorry, but you can not purchase the renewal product at this time.') );
            $customer_session->addError('Sorry, but you can not purchase the renewal product at this time.' );
            $response = Mage::app()->getResponse();
           // $response->setRedirect('customer/account');
          //  return false;
        }
        
        if( ! $customer->hasEcodesMasterUser() || ! $customer->hasEcodesMasterPass() )
        {
            $customer_session->addError(Mage::helper('ecodes')->__('Sorry, but you can not purchase the renewal product at this time. It seems that you do not have a master user account set.') );
            // $session->addError('Sorry, but you can not purchase the renewal product at this time.' );
            $response = Mage::app()->getResponse();
           // $response->setRedirect('customer/account');
           // return false;
        }

        if($product->getSubscriptionDuration() == "150") {
            $customer_session->addError(Mage::helper('ecodes')->__('Sorry, but you can not purchase the renewal product at this time. 6 month subscriptions are not eligible for renewal') );
            // $session->addError('Sorry, but you can not purchase the renewal product at this time.' );
            $response = Mage::app()->getResponse();
            $response->setRedirect('customer/account');
            return false;
        }
        
        // good to go
        return true;
    }
    
    
   
    
    
    public function updatePremiumsubExpiration($ps)
    {
        $helper = Mage::helper('ecodes');
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $ps->getSku());
        $new_expire = $helper->addDateFromDurationId( $ps->getExpiration(), $product->getSubscriptionDuration() );
        $ps->setExpiration($new_expire);
        $ps->setEmailsSent(0);
        $ps->save();
        
        $icc_connect = Mage::getModel('ecodes/api');
        $customer = Mage::getModel('customer/customer')->load($ps->getCustomerId());
        $icc_connect->setAddToQueue(true); // if it fails it will just add to queue and try to repeatedly update the info - no need for human check
        $icc_connect->appendProduct($customer->getEcodesMasterUser(), $customer->getEcodesMasterPass(), $ps->getSku(), $ps->getExpiration());
    }
    
    public function updateRenewals($invoice)
    {
        $order = Mage::getModel('sales/order')->load($invoice->getOrderId());
        //$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        foreach($order->getAllItems() as $item) 
        {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $renewal = Mage::getModel('ecodes/renewal');
            if($renewal->isRenewalProduct($product))
            {
                $premiumsubs = Mage::getModel('ecodes/premiumsubs')->getCollection();
                $premiumsubs->addFieldToFilter('customer_id', $order->getCustomerId())
                        ->addFieldToFilter('sku', $product->getRenewParentSku());
                if($premiumsubs->count())
                {
                    $ps = $premiumsubs->getFirstItem();
                    $this->updatePremiumsubExpiration($ps);
                }
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return ICC_Ecodes_Model_Observer
     */
    public function isAllowedGuestCheckout(Varien_Event_Observer $observer)
    {
        $quote = $observer->getQuote();
        $result = $observer->getResult();
        if($quote instanceof Mage_Sales_Model_Quote)
        {
            if($this->_hasEcodeItems($quote))
            {
                $result->setIsAllowed(false);
            }
        }
        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    private function _hasEcodeItems(Mage_Sales_Model_Quote $quote)
    {
        foreach($quote->getAllItems() as $quoteItem)
        {
            if($this->_isEcodeItem($quoteItem))
            {
                return true;
            }
        }
        return false;
    }

    /**'
     * @param Mage_Sales_Model_Quote_Item $quoteItem
     * @return bool
     */
    private function _isEcodeItem(Mage_Sales_Model_Quote_Item $quoteItem)
    {
        if($this->_isDownloadableEcodeItem($quoteItem))
        {
            return true;
        }
        return false;
    }

    private function _isDownloadableEcodeItem(Mage_Sales_Model_Quote_Item $quoteItem)
    {
        $product = Mage::getModel('catalog/product')->load($quoteItem->getProductId());
        if($product->getSerialRequired())
        {
            return true;
        }
        return false;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @var Mage_Sales_Model_Order_Invoice $invoice
     */
    public function applySerialToOrderItem(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getInvoice();
        $order = Mage::getModel('sales/order')->load($invoice->getOrderId());
        if(!(($order->getVolumeLicense()!=0)&&($order->getParentOrderId()==NULL))){
            if($invoice instanceof Mage_Sales_Model_Order_Invoice)
            {
                $downloadableHelper = Mage::helper('ecodes/downloadable');
                foreach($invoice->getAllItems() as $invoiceItem)
                {
                    if(Mage::getModel('catalog/product')->load($invoiceItem->getProductId())->getSerialRequired())
                    {
                        $orderItem = Mage::getModel('sales/order_item')->load($invoiceItem->getOrderItemId());
                        $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection();
                        $downloadableCollection->assignSerials($orderItem);
                        $serialsAdded = $downloadableCollection->getInfo('serials_added');
                        $numSerialsNotAssigned = $downloadableCollection->getInfo('num_serials_not_assigned');
                        $message = $downloadableHelper->generateSerialsAssignedHistoryComment(
                            $serialsAdded,
                            $numSerialsNotAssigned,
                            true
                        );
                        if($message)
                        {
                            $this->getSession()->addSuccess($message);
                        }
                    }
                }
            }
        }
    }

    /**
     * Interface for the Queue.
     *
     * @param \Varien_Object $queueEvent
     * @return bool
     */
    public function assignSerialsFromQueue(Varien_Object $queueEvent)
    {
        $queueItem = $queueEvent->getQueueItem();
        if($queueItem->testAndSetLock())
        {
            $orderItem = Mage::getModel('sales/order_item')->load($queueEvent->getOrderItemId());
            $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection();
            $downloadableCollection->setInfo('queue_item', $queueItem);
            if($downloadableCollection->assignSerials($orderItem))
            {
                $queueItem->releaseLock(Gorilla_Queue_Model_Queue::STATUS_SUCCESS);
                return $this;
            }
            $queueItem->releaseLock(Gorilla_Queue_Model_Queue::STATUS_OPEN);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return ICC_Ecodes_Model_Observer
     */
    public function applySerialsToOrderBlock(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if($block instanceof Mage_Downloadable_Block_Adminhtml_Sales_Items_Column_Downloadable_Name)
        {
            if($order = Mage::registry('current_order'))
            {
                foreach($order->getAllItems() as $orderItem)
                {
                    $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
                    if($product->getSerialRequired())
                    {
                        Mage::helper('ecodes/downloadable')->renderSerialOptionOnOrderItem($orderItem);
                    }
                }
            }
        }
        return $this;
    }

    public function getSession()
    {
        if(!$this->getData('_session'))
        {
            $this->setData('_session', Mage::getSingleton('core/session'));
        }
        return $this->getData('_session');
    }

    /**
     * Get quote store identifier
     * @return int
     */
    public function getStoreId()
    {
        if (!$this->hasStoreId()) {
            return Mage::app()->getStore()->getId();
        }
        return $this->_getData('store_id');
    }

    /**
     * reset refund quantity on partial refunds before final save for downloadable products
     *
     * @param Varien_Event_Observer $observer
     * @return ICC_Ecodes_Model_Observer
     */
    public function resetPartialRefunds(Varien_Event_Observer $observer){
        $creditmemo = $observer->getEvent()->getCreditmemo();          
        $order      = $creditmemo->getOrder();
        
        foreach($order->getAllItems() as $_orderItem){
            //check if individual items are downloadable
            $isDownloadable = ($_orderItem->getProductType() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE);
            //check if individual items have been refunded
            $isPartiallyRefunded = ($_orderItem->getQtyRefunded() > 0);
            $itemId              = $_orderItem->getItemId();
            if($isDownloadable && $isPartiallyRefunded && $itemId) {          
                $links = Mage::getModel('downloadable/link_purchased_item')->getCollection()
                    ->addFieldToFilter('order_item_id', array('eq' => $itemId));                
                foreach ($links as $link_row){                   
                    $link = Mage::getModel('downloadable/link_purchased_item')->load($link_row->getItemId());                    
                    $dlink_id                   = $link_row->getLinkId();                    
                    $dlink                      = Mage::getModel('downloadable/link')->load($dlink_id);                     
                    $number_of_downloads        = $dlink->getNumberOfDownloads();            
                    $link->setNumberOfDownloadsUsed($link->getNumberOfDownloadsUsed()-$number_of_downloads*$_orderItem->getQtyRefunded());
                    $link->save();
                }
                
            }
        }
    }

    /**
     * change state for downloadable links to "refunded"
     *
     * @param Varien_Event_Observer $observer
     * @return ICC_Ecodes_Model_Observer
     */
    public function statusForDownloadableLinks(Varien_Event_Observer $observer){

        $creditmemo     = $observer->getEvent()->getCreditmemo();         
        $creditMemoItem =  $creditmemo->getAllItems();
        $refQ     = array();
        foreach($creditMemoItem as  $itemd){
            $refQ[$itemd->getProductId()] =  $itemd->getQty();
        }       
        
        $order = $creditmemo->getOrder();
        $orderItemIds = array();
        foreach($order->getAllItems() as $_orderItem){          
            $product_id     = $_orderItem->getProductId();
            $isDownloadable = ($_orderItem->getProductType() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE);
            //check if individual items have been refunded
            $isPartiallyRefunded = ($_orderItem->getQtyRefunded() > 0);
            $isFullyRefunded     = ($_orderItem->getQtyInvoiced() == $_orderItem->getQtyRefunded());

            $itemId = $_orderItem->getItemId();

            if ($isDownloadable && $isFullyRefunded && $itemId){
                $orderItemIds[] = $itemId;
            }else if($isDownloadable && $isPartiallyRefunded && $itemId) {
                
                $links = Mage::getModel('downloadable/link_purchased_item')->getCollection()
                    ->addFieldToFilter('order_item_id', array('eq' => $itemId));                
                foreach ($links as $link_row){
                    $link                   = Mage::getModel('downloadable/link_purchased_item')->load($link_row->getItemId());                    
                    $dlink_id               = $link_row->getLinkId();                    
                    $dlink                  = Mage::getModel('downloadable/link')->load($dlink_id);                     
                    $number_of_downloads    = $dlink->getNumberOfDownloads();  
                    
                    if(is_array($refQ) && array_key_exists($product_id,$refQ)){                       
                       $no_of_download_used = $link->getNumberOfDownloadsUsed()+ ($number_of_downloads*$refQ[$product_id]);
                       $no_of_download_used = $no_of_download_used >0 ? $no_of_download_used : $link->getNumberOfDownloadsBought(); 
                       $link->setNumberOfDownloadsUsed($no_of_download_used); 
                    }else{                       
                       $link->setNumberOfDownloadsUsed($link->getNumberOfDownloadsBought()+ ($number_of_downloads* $_orderItem->getQtyRefunded()));
                    }
                    $link->save();
                }
                
            }
        }

        if (!empty($orderItemIds)){
            $linksPurchased = Mage::getModel('downloadable/link_purchased_item')->getCollection()
                    ->addFieldToFilter('order_item_id', array('in' => $orderItemIds));

            foreach($linksPurchased->getItems() as $_link){
                $_link->setStatus(ICC_Ecodes_Helper_Downloadable::LINK_STATUS_REFUNDED);
                $_link->save();
            }
        }

        return $this;
    }

}