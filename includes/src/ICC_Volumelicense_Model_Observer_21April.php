<?php
class ICC_Volumelicense_Model_Observer extends Mage_Core_Model_Abstract
{
    
   /**
    * When a pending customer registers we are assigning him the shared volume license. 
    * @param type $observer
    */
    public function assignVolumelicense($observer)
    {
       $event = $observer->getEvent();
       $customer = $event->getCustomer(); 
       $email = $customer->getEmail();
       $shareaccess=Mage::getModel('volumelicense/registry')->getCollection()->getBySharedEmail($email);
      // Mage::log(''.$shareaccess->getSelect(),null,'access.log');
       if (sizeof($shareaccess) != 0) {
            foreach($shareaccess as $share)
            {
               // Mage::log($share->getData(),null,'access.log');
                $sharemodel=Mage::getModel('volumelicense/registry')->load($share->getId());
                $sharemodel->setAssignStatus(1);
                $sharemodel->setAssignCustomerId($customer->getEntityId());
                $sharemodel->setUpdateTime(date('m/d/y h:i:s', time()));
                $sharemodel->save();
            }
       } 
    }
    
    
    public function createChildOrder($observer) {
    
       $helper=Mage::helper('volumelicense');
       $order = Mage::getModel('sales/order')->load($observer->getInvoice()->getOrderId());
       $storeId = Mage::app()->getStore()->getStoreId();
       Mage::log($order->debug(),null,'teste.log');
       
       foreach($order->getAllItems() as $item) {
      // echo "<pre>";print_r($item->getData());die;
            $obj = Mage::getModel('catalog/product');
            $_product = $obj->load($item->getProductId()); 
            //Mage::log($_product->getData(),null,'testae.log');
            //Mage::log($_product->getData('volume_license'),null,'testae.log');
            //if ($_product->getData('volume_license')) { // && $item->getData('has_children')) {
             $volume_license = $_product->getResource()->getAttribute('volume_license')->getFrontend()->getValue($_product);   
	      if (($item->getProductType() == 'downloadable') && ($item->getQtyOrdered() > 1 ) && ($volume_license == 'Yes')) {
		$purchasingAgentEmail = $order->getData('customer_email');
		$purchasingAgentDetail = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $purchasingAgentEmail)->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
                $emails = unserialize($order->getVolumeUsers());
                //Mage::log($order->getVolumeUsers(),null,'testae.log');
                //Mage::log($emails,null,'testae.log');
                
		//echo "<pre>=====";print_r($emails); die;
		//echo "<pre>=====";
		$email_array=$emails[$item->getQuoteItemId()];
		
                $customer = Mage::getModel("customer/customer"); 
                $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                foreach($email_array as $key=>$multipleEmail) {
		    foreach($multipleEmail as $key=>$value) {
		    
			if($value == '') {
			  $value = $purchasingAgentEmail;
			}
		    //echo $value;die("sssssss");
			$customer = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $value)->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
			if($customer->getData('entity_id')) {
			//echo "<pre>";print_r($item);die("==============___________");
			  $this->generateOrder($customer, $item,null,$order);    
			}
			else {
			  $customer = $purchasingAgentDetail;
			  $this->generateOrder($customer, $item,$value,$order);    
			} //die("second");
		    }
                }
	    }
       }

    }
    
    
    public function generateOrder($customer, $parentItem, $futureEmail,$parentOrder) {
	//Mage::log(print_r($customer,1),null,'ordere.log',true);
	//Mage::log($pid,null,'ordere.log',true);
	//Mage::log($futureEmail,null,'ordere.log',true);
	
	//echo "<pre>";print_r($customer);print_r($parentOrder->getData()); $pid."====";die;
	$parentCustomerDetail = $purchasingAgentDetail = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $parentOrder->getCustomerEmail())->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
	$parentOderId = $parentOrder->getId();
	$transaction = Mage::getModel('core/resource_transaction');
	$storeId = $customer->getStoreId();
	$reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);
	$order = Mage::getModel('sales/order')
	->setIncrementId($reservedOrderId)
	->setStoreId($storeId)
	->setQuoteId(0)
	->setGlobal_currency_code('USD')
	->setBase_currency_code('USD')
	->setStore_currency_code('USD')
	->setOrder_currency_code('USD');
	//Set your store currency USD or any other
	
	// set Customer data
	$order->setCustomer_email($customer->getEmail())
	->setCustomerFirstname($customer->getFirstname())
	->setCustomerLastname($customer->getLastname())
	->setCustomerGroupId($customer->getGroupId())
	//->setCustomer_is_guest(0)
	->setCustomer($customer);
	
	// set Billing Address
	$billingAddress = Mage::getModel('sales/order_address')->load($parentOrder->getBillingAddressId());
	//echo "<pre>";print_r($billingAddress->getData());die;
	
	  $billingAddress = Mage::getModel('sales/order_address')
	  ->setStoreId($storeId)
	  ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
	  ->setCustomerId($customer->getId())
	  ->setCustomerAddressId($customer->getDefaultBilling())
	  ->setCustomer_address_id($billingAddress->getEntityId())
	  ->setPrefix($billingAddress->getPrefix())
	  ->setFirstname($billingAddress->getFirstname())
	  ->setMiddlename($billingAddress->getMiddlename())
	  ->setLastname($billingAddress->getLastname())
	  ->setSuffix($billingAddress->getSuffix())
	  ->setCompany($billingAddress->getCompany())
	  ->setStreet($billingAddress->getStreet())
	  ->setCity($billingAddress->getCity())
	  ->setCountry_id($billingAddress->getCountryId())
	  ->setRegion($billingAddress->getRegion())
	  ->setRegion_id($billingAddress->getRegionId())
	  ->setPostcode($billingAddress->getPostcode())
	  ->setTelephone($billingAddress->getTelephone())
	  ->setFax($billingAddress->getFax());
	  $order->setBillingAddress($billingAddress);
	
	  // set Shipping Address
	  
	  $shipping = '';
                    
          $shipping = Mage::getModel('sales/order_address')->load($parentOrder->getShippingAddressId());
	  $shippingAddress = Mage::getModel('sales/order_address')
	    ->setStoreId($storeId)
	    ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
	    ->setCustomerId($customer->getId())
	    ->setCustomerAddressId($customerAddressId);
	  
	  if($shipping->getEntityId()) {
	  $shippingAddress ->setCustomer_address_id(($shipping->getEntityId()!= '')?$shipping->getEntityId():"")
                            ->setPrefix(($shipping->getPrefix()!= '')?$shipping->getPrefix():'')
                            ->setFirstname(($shipping->getFirstname()!= '')?$shipping->getFirstname():$customer->getFirstname())
                            ->setMiddlename(($shipping->getMiddlename()!= '')?$shipping->getMiddlename():'')
                            ->setLastname(($shipping->getLastname()!= '')?$shipping->getLastname():$customer->getLastname())
                            ->setSuffix(($shipping->getSuffix()!= '')?$shipping->getSuffix():'')
                            ->setCompany(($shipping->getCompany()!= '')?$shipping->getCompany():'')
                            ->setStreet(($shipping->getStreet()!= '')?$shipping->getStreet():'')
                            ->setCity(($shipping->getCity()!= '')?$shipping->getCity():'')
                            ->setCountry_id(($shipping->getCountryId()!= '')?$shipping->getCountryId():'')
                            ->setRegion(($shipping->getRegion()!= '')?$shipping->getRegion():'')
                            ->setRegion_id(($shipping->getRegionId()!= '')?$shipping->getRegionId():'')
                            ->setPostcode(($shipping->getPostcode()!= '')?$shipping->getPostcode():'')
                            ->setTelephone(($shipping->getTelephone()!= '')?$shipping->getTelephone():'')
                            ->setFax(($shipping->getFax()!= '')?$shipping->getFax():'');
	  }
	  else {
                        $shippingAddress ->setCustomer_address_id('')
                            ->setPrefix('')
                            ->setFirstname($customer->getFirstname())
                            ->setMiddlename('')
                            ->setLastname($customer->getLastname())
                            ->setSuffix('')
                            ->setCompany('No Address Available')
                            ->setStreet('')
                            ->setCity('')
                            ->setCountry_id('')
                            ->setRegion('')
                            ->setRegion_id('')
                            ->setPostcode('')
                            ->setTelephone('')
                            ->setFax('');
                    }
	
	$order->setShippingAddress($shippingAddress)->setShipping_method('flatrate_flatrate');
	//echo "<pre>";print_r($order->getData());die;
	//you can set your payment method name here as per your need
	$orderPayment = Mage::getModel('sales/order_payment')
	->setStoreId($storeId)
	->setCustomerPaymentId(0)
	->setMethod('volume_license');
	
	// child order's comment's html that who has placed order for him
	  $orderBy = "<strong>Order By</strong><br />";
	  $orderBy .= 'Name : '.$parentCustomerDetail->getName().'<br />';
	  $orderBy .= 'Order# : '.$order->getIncrementId().'<br />';
	  $orderBy .= 'Email : '.$parentCustomerDetail->getEmail().'<br />';
	  $orderBy .= 'Captured Amount of $'.round($grandTotal, 2);

	
	
	$order->setPayment($orderPayment);
	//echo "<pre>";print_r($order);die;
	// let say, we have 1 product
	//check that your products exists
	//need to add code for configurable products if any
	$subTotal = 0;
	$products = array(
	    $parentItem->getProductId() => array(
	    'qty' => 1
	    )
	);
	
	foreach ($products as $productId=>$product) {
	$_product = Mage::getModel('catalog/product')->load($productId);
	$orderItem = Mage::getModel('sales/order_item')
	->setStoreId($storeId)
	->setQuoteItemId(0)
	->setQuoteParentItemId(NULL)
	->setProductId($productId)
	->setProductType($_product->getTypeId())
	->setQtyBackordered(NULL)
	->setTotalQtyOrdered($product['qty'])
	->setQtyOrdered($product['qty'])
	->setName($_product->getName())
	->setSku($_product->getSku())
	->setPrice(0)
	->setBasePrice(0)
	->setOriginalPrice(0)
	->setRowTotal(0)
	->setQtyInvoiced(count($product))
	->setBaseRowTotal(0);
	
	$subTotal = 0;
	$order->addItem($orderItem);
	}
	
	$order->setParentOrderId($parentOderId)
	->setParentOrderItemId($parentItem->getId())
	->setVolumeLicense(true)
	->setFutureEmail($futureEmail)
	->setSubtotal($subTotal)
	->setBaseSubtotal($subTotal)
	->setGrandTotal($subTotal)
	->setBaseGrandTotal($subTotal)
	->addStatusToHistory('complete', $orderBy, false)
	->setData('state', "complete")
	->setStatus("complete");
	
	//$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
	//echo "<pre>";print_r($order->getData());die("Stoppppp");
	$transaction->addObject($order);
	$transaction->addCommitCallback(array($order, 'place'));
	$transaction->addCommitCallback(array($order, 'save'));
	$transaction->save();
	
	
	
	/* ***********************************************    start for create downloadable links      ********************************************** */
	
	$childOrderId = $order->getIncrementId();
	
	//print_r($childOrderId);die;
                    $childOrder = Mage::getModel('sales/order')->loadByIncrementId($childOrderId);
                    foreach ($childOrder->getItemsCollection() as $item) {
                        if (!$item->getId()) {
                            //order not saved in the database
                            return $this;
                        }
                       
                        if (Mage::getModel('downloadable/link_purchased')->load($item->getId(), 'order_item_id')->getId()) {
                            return $this;
                        }
                        $product = Mage::getModel('catalog/product')
                            ->setStoreId($item->getOrder()->getStoreId())
                            ->load($item->getProductId());
                        if ($product->getTypeId() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE) {
                            $links = $product->getTypeInstance(true)->getLinks($product);
                            $linkIds = array();
                            foreach ($links as $key => $link) {
                                $linkIds[] = $key;
                            }
                            if ($linkIds) {
                                $linkPurchased = Mage::getModel('downloadable/link_purchased');
                                Mage::helper('core')->copyFieldset(
                                    'downloadable_sales_copy_order',
                                    'to_downloadable',
                                    $childOrder,
                                    $linkPurchased
                                );
                                Mage::helper('core')->copyFieldset(
                                    'downloadable_sales_copy_order_item',
                                    'to_downloadable',
                                    $item,
                                    $linkPurchased
                                );
                                $linkSectionTitle = (
                                    $product->getLinksTitle()?
                                    $product->getLinksTitle():Mage::getStoreConfig(Mage_Downloadable_Model_Link::XML_PATH_LINKS_TITLE)
                                );
                                $linkPurchased->setLinkSectionTitle($linkSectionTitle)
                                    ->save();
                                foreach ($linkIds as $linkId) {
                                    if (isset($links[$linkId])) {
                                        $linkPurchasedItem = Mage::getModel('downloadable/link_purchased_item')
                                            ->setPurchasedId($linkPurchased->getId())
                                            ->setOrderItemId($item->getId());

                                        Mage::helper('core')->copyFieldset(
                                            'downloadable_sales_copy_link',
                                            'to_purchased',
                                            $links[$linkId],
                                            $linkPurchasedItem
                                        );
                                        $linkHash = strtr(base64_encode(microtime() . $linkPurchased->getId() . $item->getId()
                                            . $product->getId()), '+/=', '-_,');
                                        $numberOfDownloads = $links[$linkId]->getNumberOfDownloads()*$item->getQtyOrdered();
                                        $linkPurchasedItem->setLinkHash($linkHash)
                                            ->setNumberOfDownloadsBought($numberOfDownloads)
                                            ->setCreatedAt($item->getCreatedAt())
                                            ->setUpdatedAt($item->getUpdatedAt());
                                        
                                            $linkPurchasedItem->setStatus('available');
                                                                              
                                        $linkPurchasedItem->save();
                                    }
                                }
                            }
                        }
                    }
	/* ***********************************************    end for create downloadable links      ********************************************** */
	return;
    }
     /**
     * When invoice is created one volumelicense is assigned to the purchaser
     * @param obj $observer
     */
  /*  public function associateVolumelicenseInformation($observer) 
    {
       $helper=Mage::helper('volumelicense');
       $order = Mage::getModel('sales/order')->load($observer->getInvoice()->getOrderId());
       $storeId = Mage::app()->getStore()->getStoreId();
       $configurableEcodeIds = array();
       foreach($order->getAllItems() as $item) {
            $obj = Mage::getModel('catalog/product');
            $_product = $obj->load($item->getProductId()); 
            $expiration = "";    
            $volume_license = $_product->getResource()->getAttribute('volume_license')->getFrontend()->getValue($_product);   
            if (($item->getProductType() == 'downloadable') && ($item->getQtyOrdered() > 1 ) && ($volume_license == 'Yes')) { // && $item->getData('has_children')) {
                $links=Mage::getModel('downloadable/link')->getCollection()->addFieldToFilter('product_id',array('eq'=>$item->getProductId()));
                
                //Check to make sure we haven't already entered this subscription
                $subTest =  Mage::getModel('volumelicense/volumelicense')->getCollection()->getByOrderLineItemId($item->getItemId());
                if (sizeof($subTest) === 0) {
                    $sub = Mage::getModel('volumelicense/volumelicense');
                    $sub->setCustomerId($order->getCustomerId());
                    $sub->setProductId($item->getProductId());
                    $sub->setOrderItemId($item->getItemId());
                    $sub->setOrderNumber($order->getIncrementId());
                    $sub->setProductName($item->getName());
                    $sub->setSku($item->getSku());
                    $sub->setMaxRegister($item->getQtyOrdered());
                    $sub->setStatus(ICC_Volumelicense_Helper_Data::ACTIVE);
                    $sub->setRegisteredCount(1);
                    $sub->setCreatedAt(date('m/d/y h:i:s', time()));
                    $sub->setUpdateTime(date('m/d/y h:i:s', time()));
                    try{
                        $sub->save();
                    }
                    catch (Exception $msg){
                        throw $msg->getMessage();
                    }
                    $customer_name= $order->getCustomerFirstname(). ' '.$order->getCustomerLastname();
                    $helper->volumeLicensePurchaseEmail($order->getCustomerEmail(),$customer_name,$item->getName(),$item->getQtyOrdered(),$expiration);
                    $registry = Mage::getModel('volumelicense/registry');
                    $registry->setVolumelicenseId($sub->getId());
                    $registry->setAssignCustomerId($order->getCustomerId());
                    $registry->setParentCustomerId('0');
                    $registry->setAssignCustomerEmail($order->getCustomerEmail());
                    $registry->setAssignStatus(ICC_Volumelicense_Helper_Data::ACTIVE);
                    $registry->setConsumedDownloads(0);
                    $registry->setCreatedDate(date('m/d/y h:i:s', time()));
                    $registry->setUpdateDate(date('m/d/y h:i:s', time()));
                    $registry->save();
                    
                    foreach($links as $link){
                       $linkmodel = Mage::getModel("volumelicense/links");
                       $linkmodel->setRegistryId($registry->getId());
                       $purchaseds = Mage::getModel('downloadable/link_purchased_item')->getCollection()->addFieldToFilter('order_item_id', $item->getItemId())->addFieldToFilter('link_id',$link->getLinkId());                        
                       $purchased = $purchaseds->getFirstItem();                                              
                       $linkmodel->setPurItemId($purchased->getItemId());
                       $linkmodel->setLinkId($link->getLinkId());
                       $linkmodel->setLinkDownloadLimit($link['number_of_downloads']);
                       $linkmodel->save();
                    }    
                   // $helper->volumeLicenseShareEmail($order->getCustomerEmail(),$customer_name,$customer_name,$item->getName(),$item->getQtyOrdered(),$expiration);
                }
            }
        }
    }
    */
    /**
     * Sets volume_license flag in sales flat order item table when order is placed, if the oder item has volume_license as "Yes"
     * @param type $event
     */
    public function isVolumeLicense($event){
        $order = $event->getOrder();
        $items = $order->getAllItems();
        foreach ($items as $number => $item) {
            $obj = Mage::getModel('catalog/product');
            $_product = $obj->load($item->getProductId()); 
           // Mage::log($_product->debug(),null,'hi.log');
            $volume_license = $_product->getResource()->getAttribute('volume_license')->getFrontend()->getValue($_product);   
            if (($item->getProductType() == 'downloadable') && ($item->getQtyOrdered() > 1 ) && ($volume_license == 'Yes')) {
                $orderItem = Mage::getModel('sales/order_item')->load($item->getItemId());
                $orderItem->setVolumeLicense(1);
                $orderItem->save();
            }
        } //foreach                                                                                                                                                                       
    }
    /**
     * Sets customner statuses as "REFUNDED" when a volumelicense customer is refunded.
     * @param event $observer
     */
    public function updateRefundedVolumeLicense($observer){
        $refundData = Mage::app()->getRequest()->getParam('creditmemo');
        foreach ($refundData['items'] as $k=>$v){
            foreach($v as $it=>$qt){
              if(is_array($qt)){
                   $refundData['items'][$k][$it] = array_sum($qt);
              }
            }
        }
 
        $expire_id =  Mage::app()->getRequest()->getParam('volume_orders');
        //array for registry table ids those subscription statuses need to be set 2 :)
        $item_ids = array();
        $expire_ids = $this->sanitizeArrray($expire_id);
        //gets refund data 
        foreach($refundData as $refund){
           //now loop through all items and quantity
           foreach($refund as $item=>$qty){ 
              
                //gets order item subscription id by order item id
                $access = Mage::getModel('volumelicense/volumelicense')->loadByOrderItemId($item);
                foreach($access as $subscriptionIds){
                    $sub = Mage::getModel('volumelicense/registry')->loadByVolumeLicenseId($subscriptionIds->getId());
                    foreach($sub as $subscr){
                        $subs = Mage::getModel('volumelicense/registry')->load($subscr->getId());
                        $subscription = Mage::getModel('volumelicense/volumelicense')->load($subs->getVolumelicenseId());
                        if($subscription->getMaxRegister() && (!in_array($item, $item_ids))){
                            $subscription->setMaxRegister($subscription->getMaxRegister() - $qty['qty']);
                            $item_ids[] = $item;
                            $subscription->save();
                        }     
                        
                        if(in_array($subscr->getId(),$expire_ids)){
                            try{
                                // print_r($subscr->getId()); die;
                                //2 for refund
                                $subs->setAssignStatus(ICC_Volumelicense_Helper_Data::REFUND);
                                $subs->save();
                                $subs->getId();
                                if($subscription->getRegisteredCount() > 0){
                                     Mage::helper('volumelicense')->updateVolumelicenseRegisteredCount($subs->getVolumelicenseId());
                                }
                                $subscription->save();
                            
                            }  catch (Exception $e){
                                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('volumelicense')->__('Error occured in refunding subscription of '.$subscr->getAssignCustomerId().'.'));
                            }
                        }
                    }  
                }

            }

        }
    }
    
   /**
    * Extracts customer ids for those statuses that needs to be set as refunded from the posted item array
    * @param type $expire_customer_ids
    * @return array
    */
    public function sanitizeArrray($expire_customer_ids){
        foreach ($expire_customer_ids as $id => $flag){
            foreach($flag as $k =>$v){
               if($v){
                    $refund_array[] = $k;
                } 
            }
            
        }
        return $refund_array;
    }
    
    public function afterCartSave($event)
    {
        //Standard on the Design and Construction of Log Structures: ICC 400 - 2007

        $types = array(); //types of product in the cart
	$requiedVolumeStep = false;
        $items = $event->getCart()->getQuote()->getAllItems();   //get all items of quote

        foreach ($items as $item) {
             if ($item->getProduct()->getData('volume_license') && $item->getProduct()->getData('volume_license') == 1 && $item->getQty() > 1) {
                $requiedVolumeStep = true; /* Chenge it to True */
                break;
            }
        } //foreach
//Mage::log("nikhil=====".$event->getCart()->getQuote()->getId()."=====".$requiedVolumeStep,null,'test.log');        
        if(!$requiedVolumeStep) {
	  $quote = Mage::getModel('sales/quote')->load($event->getCart()->getQuote()->getId());
	  $quote->setVolumeLicense(false);
	  $quote->setVolumeUsers(null);
	  $quote->save();
	  return;
        }
        
        
    }
        
}
