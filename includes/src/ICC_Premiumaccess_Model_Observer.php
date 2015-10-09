 <?php

class ICC_Premiumaccess_Model_Observer extends Mage_Core_Model_Abstract
{
 
 
    /**
     * This method used to call assoicateSubscriptions() on invoice pay event
     * @param type $observer
     */
    public function associateSubscriptionsEvent($observer) {
        
       
            $invoice = $observer['invoice'];
            
            $this->associateSubscriptions($invoice);
    }
    
    public function associateSubscriptions($invoice) {
       
        $helper=Mage::helper('icc_premiumaccess');
        $order = Mage::getModel('sales/order')->load($invoice->getOrderId());
        $premiumOptions = $helper->getPremiumAccessType();
        $storeId = Mage::app()->getStore()->getStoreId();
        $uniqueEmailArray = array();
        $sendPAEmail = false;

        foreach ($order->getAllItems() as $item) {
            $obj = Mage::getModel('catalog/product');
            $_product = $obj->load($item->getProductId());
            
            if ((in_array($_product->getData('item_type'),$premiumOptions))) {
                $purchasingAgentEmail = $order->getData('customer_email');
                $purchasingAgentName = $order->getData('customer_firstname');
                $purchasingAgentDetail = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $purchasingAgentEmail)->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
                $emails = unserialize($order->getPremiumUsers());
                $email_array = $emails[$item->getQuoteItemId()];
                $customer = Mage::getModel("customer/customer");
                $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                $userCount = count($email_array);
                $item_name = $item->getName();
                $purchasingAgentMailData = '';
                $giftUserMailData = '';
                $purchasingAgentMailData .= "You have purchase $item_name premiumACCESS for $userCount users.<br />";
                $purchasingAgentMailData = $order->getIncrementId();
		$purchasingAgentCheck = 0;
		$myEcodeUrl = Mage::getUrl('ecodes/account/products');
                foreach ($email_array as $key => $multipleEmail) {
		      if (array_key_exists("bundle_flag",$multipleEmail))
		      {
		      $bundleFlag = $multipleEmail['bundle_flag'];
		      }
		      else
		      {
		      $bundleFlag = null;
		      }
		      
		      if (array_key_exists("bundle_flag",$multipleEmail))
		      {
			if(!($multipleEmail['bundle_flag'])) {
			  continue;
			  }
		      }
                        if ($multipleEmail['email'] == '') {
                            $value = $purchasingAgentEmail;
                            $purchasingAgentCheck = $purchasingAgentCheck + 1;
                        }
                        else {
			    $value = $multipleEmail['email'];
                        }
                        $customer = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $value)->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
                        if ($customer->getData('entity_id')) {
                            if ($customer->getData('email') != $purchasingAgentEmail) {
                                if (!array_key_exists($value, $uniqueEmailArray)) {
                                    $uniqueEmailArray[$value] = '';
                                }
                                $gufirstName = $customer->getData('firstname');
                                $uniqueEmailArray[$value] .= $item_name; 
                            }

                            $this->generateOrder($customer, $item, null, $order,$bundleFlag);
                        } else {
                            $customer = $purchasingAgentDetail;

                            if (!array_key_exists($value, $uniqueEmailArray)) {
                                $uniqueEmailArray[$value] = $item_name;
                            }
                            $this->generateOrder($customer, $item, $value, $order,$bundleFlag);
                        }
		      if($purchasingAgentCheck > 1) {
			$sendPAEmail = true;
		      }
                }
            }
        }
	if($sendPAEmail) {
          $helper->premiumAccessPurchaseEmail($purchasingAgentEmail, $purchasingAgentName, $purchasingAgentMailData);
        }

        /* Send Email To Purchasing Agent End */

        /* Send Email To Gifted User Start */

        foreach ($uniqueEmailArray as $key => $value) {

            $customer = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $key)->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
            if ($customer->getData('entity_id')) {
                $priductData = rtrim($value, ' and ');
                $gufirstName = $customer->getData('firstname');
                $purchasingAgentMailData = "<strong>Dear $gufirstName </strong>,<br/><p><b> $purchasingAgentName </b> have assigned you premiumACCESS product $priductData.</p> <p> To View the $item_name <a href='" . $myEcodeUrl . "'>click here </a></p>";
            } else {
                $priductData = rtrim($value, ' and ');
                $purchasingAgentMailData = "<strong>Dear $key </strong>,<br/><p> <b> $purchasingAgentName </b> have assigned you premiumACCESS product $priductData.</p> <p>You are not registered with ICC StoreFront.Please click here for registeration <a href='" . $customerRegisterUrl . "'>click here </a>.</p>";
            }
            $helper->premiumAccessShareEmail($key, $purchasingAgentMailData,$order->getCustomerEmail());
        }        
    }
    
    public function generateOrder($customer, $parentItem, $futureEmail,$parentOrder,$bundleFlag) {
	    // get logged in cusomer id
	    $parentCustomerDetail = $purchasingAgentDetail = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $parentOrder->getCustomerEmail())->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
	    $customerAccountNo = Mage::getModel('customer/session')->getCustomer()->getId();
	    $customerObj = $customer;
	    $quoteObj = Mage::getModel('sales/quote')->assignCustomer($customerObj);
	    $quoteObj = $quoteObj->setStoreId($customer->getStoreId());
	    
	    // product id
	    $productId = $parentItem->getProductId();
	    $productModel = Mage::getModel('catalog/product');
	    $productObj = $productModel->load($productId);
	    $productObj->setPrice(0);
	    
	    $params=array();
            
            if($productObj->getTypeId() == "bundle"){
                $params = array(
                    'product' => $productId,
                    'bundle_option'         => array(),
                    'bundle_option_qty'     => array(),
                    'qty'                    => 1
                );
                    $bundled_items = array();
                    $selectionCollection = $productObj->getTypeInstance(true)->getSelectionsCollection(
                    $productObj->getTypeInstance(true)->getOptionsIds($productObj), $productObj);

                    foreach($selectionCollection as $option)
                    {                        
                        $params['bundle_option'][(int)$option->option_id][] = (int)$option->selection_id;
                        $params['bundle_option_qty'][(int)$option->option_id][(int)$option->selection_id] = 1;
                    }
                $request = new Varien_Object();
                $request->setData($params);
                $quoteObj->addProduct($productObj,$request);
            }
            else{
                $quoteObj->addProduct($productObj,1);
            }
            	    
	    $billingAddress = Mage::getModel('sales/order_address')->load($parentOrder->getBillingAddressId());
	    $billingAddress = array
	    (
		'email' => $customerObj->getEmail(),
		'firstname' => $billingAddress->getFirstname(),
		'lastname' => $billingAddress->getLastname(),
		'telephone' => $billingAddress->getTelephone(),
		'street' => $billingAddress->getStreet(),
		'country_id' => $billingAddress->getCountryId(),
		'city' => $billingAddress->getCity(),
		'postcode' => $billingAddress->getPostcode(),
		'region_id' => $billingAddress->getRegionId(),
		'region' => $billingAddress->getRegion(),
		'company' => $billingAddress->getCompany(),
		'fax' => $billingAddress->getFax(),
		'customer_address_id' => NULL,
	    );
	    
	    
	    $quoteBillingAddress = Mage::getModel('sales/quote_address');
	    $quoteBillingAddress->setData($billingAddress);
	    $quoteObj->setBillingAddress($quoteBillingAddress);
	    
	    //if product is not virtual
	    if (!$quoteObj->getIsVirtual()) {
		$shippingAddress = $billingAddress;
		$quoteShippingAddress = Mage::getModel('sales/quote_address');
		$quoteShippingAddress->setData($shippingAddress);
		$quoteObj->setShippingAddress($quoteShippingAddress);
		// fixed shipping method
		$quoteObj->getShippingAddress()->setShippingMethod('flatrate_flatrate');
		$quoteObj->getShippingAddress()->setCollectShippingRates(true);
		$quoteObj->getShippingAddress()->collectShippingRates();
	    }
	    
	    $quoteObj->collectTotals();
            $quoteObj->save();
	    $transaction = Mage::getModel('core/resource_transaction');
	    if ($quoteObj->getCustomerId()) {
		$transaction->addObject($quoteObj->getCustomer());
	    }
	    $transaction->addObject($quoteObj);
            $quoteObj->reserveOrderId();
	    
	    $quotePaymentObj = $quoteObj->getPayment();
	    $quotePaymentObj->setMethod('volume_license');
	    $quoteObj->setPayment($quotePaymentObj);
	    
	    
	    $convertQuoteObj = Mage::getSingleton('sales/convert_quote');
	    if ($quoteObj->getIsVirtual()) {
		$orderObj = $convertQuoteObj->addressToOrder($quoteObj->getBillingAddress());
	    } else {
		$orderObj = $convertQuoteObj->addressToOrder($quoteObj->getShippingAddress());
	    }
	    
	    $orderObj->setBillingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getBillingAddress()));
	    $orderObj->setPayment($convertQuoteObj->paymentToOrderPayment($quoteObj->getPayment()));
	    if (!$quoteObj->getIsVirtual()) {
		$orderObj->setShippingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getShippingAddress()));
	    }
	    // child order's comment's html that who has placed order for him
	    $orderBy = "Order has been generated.";

	    $orderObj->setBasePrice(0)
	    ->setOriginalPrice(0)
	    ->setRowTotal(0)
	    ->setTotalQtyOrdered(1)
	    ->setQtyOrdered(1)
	    ->setQtyInvoiced(1)
	    ->setBaseRowTotal(0)
	    ->setParentOrderItemId($parentItem->getId())
	    ->setPremiumAccess(1)
	    ->setFutureEmail($futureEmail)
	    ->setBundleFlag($bundleFlag)
	    ->setParentOrderId($parentOrder->getId());
	    
	    $items=$quoteObj->getAllItems();
	    
	    foreach ($items as $item) {
	    //@var $item Mage_Sales_Model_Quote_Item
		$orderItem = $convertQuoteObj->itemToOrderItem($item);
		if ($item->getParentItem()) {
		    $orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
		}
		$orderObj->addItem($orderItem);
	    }
	    
	    $orderObj->setCanShipPartiallyItem(false);
	    
	    $totalDue = $orderObj->getTotalDue();
	    
	    $transaction->addObject($orderObj);
	    $transaction->addCommitCallback(array($orderObj, 'place'));
	    $transaction->addCommitCallback(array($orderObj, 'save'));
	    
	    try {
		$transaction->save();
	    } catch (Exception $e){
		Mage::log($e->getMessage());
	    }
	    
	    //$orderObj->sendNewOrderEmail();
	    
	    Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order'=>$orderObj, 'quote'=>$quoteObj));
	    
	    $quoteObj->setIsActive(0);
	    $quoteObj->save();
	    try{
		$order = Mage::getModel('sales/order')->load($orderObj->getId());
		$order->setData('state', "complete");
		$order->setStatus("complete");       
		$history = $order->addStatusHistoryComment($orderBy, false);
		$history->setIsCustomerNotified(true);
		$order->save();
		
//		foreach($order->getAllItems() as $item) {
//                    if($item->getProductType() == 'bundle'){
//                    $allbundle[$item->getId()]=$item->getId();
//                    }
//                    if(!in_array($item->getParentItemId(),$allbundle)):
//                      //$product = Mage::getModel('catalog/product')->load($item->getProductId());
//                      //$expiration = date('m/d/y', $this->getExpriationFromDurationValue($product->getAttributeText("subscription_duration")));
//                      $orderItem = Mage::getModel('sales/order_item')->load($item->getId());
//                      $orderItem->setPremiumAccess(1);
//                      //$orderItem->setExpirydate($expiration);
//                      $orderItem->save();
//                    endif;
//		}
		
	     } catch (Exception $e){
		Mage::log($e->getMessage());
	    }
               
	       Mage::helper('icc_premiumaccess')->setReportsLog($order);
	       return;
	    
	    //die("Stopppp");
    
    }

    /**
     * Sets premium_access flag in sales flat order item table when order is placed, if the oder item has premium_access as "Yes"
     * @param type $event
     */
    public function isPremiumAccess($event) {
        $helper=Mage::helper('icc_premiumaccess');
        $premiumOptions = $helper->getPremiumAccessType();
        $order = $event->getOrder();
        $items = $order->getAllItems();
        $allbundle=array();
        foreach ($items as $number => $item) {
            $obj = Mage::getModel('catalog/product');
            $_product = $obj->load($item->getProductId());
            
            $expiration = date('m/d/y', $this->getExpriationFromDurationValue($_product->getAttributeText("subscription_duration")));
            if ((in_array($_product->getData('item_type'),$premiumOptions))) {
               // echo $item->getProductType().'===';
                if($item->getProductType() == 'bundle'){
                    $allbundle[$item->getId()]=$item->getId();
                }
                
                if(!in_array($item->getParentItemId(),$allbundle)):
                    if(!$order->getPremiumAccess()){ 
                      $order->setPremiumAccess(1);
                      $order->setExpirydate($expiration);
                      $order->save();
                      //echo "<pre>"; print_r($order->getData()); exit;
                    }
                    $orderItem = Mage::getModel('sales/order_item')->load($item->getItemId());
                    $orderItem->setPremiumAccess(1);
                    $orderItem->setExpirydate($expiration);
                    $orderItem->save();
                endif;
            }
        } 
    }

      /**
     * Sets customner statuses as "REFUNDED" when a PremiumACCESS customer is refunded.
     * @param event $observer
     */
    public function updateRefundedPremiumAccess($observer) {
        $refundData = Mage::app()->getRequest()->getParam('creditmemo');
        foreach ($refundData['items'] as $k => $v) {
            foreach ($v as $it => $qt) {
                if (is_array($qt)) {
                    $refundData['items'][$k][$it] = array_sum($qt);
                }
            }
        }
      
        $expire_id = Mage::app()->getRequest()->getParam('volume_orders');
        $expire_ids = $this->sanitizeArrray($expire_id);
        //gets refund data 
        foreach ($expire_ids as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            try {
		if($order->getPremiumAccess()) {
                $order->setData('state', "canceled");
                $order->setStatus("canceled");
                $history = $order->addStatusHistoryComment('This premiumACCESS product is refunded from parent order.', false);
                $history->setIsCustomerNotified(false);
                $order->save();
                }
            } catch (Exception $ex) {
                Mage::log($ex->getMessage());
            }
        }
    }
    
    /**
     * This method used to return date Expiration duraton value from given time
     * @param type $v
     * @return type
     */
    protected function getExpriationFromDurationValue($v) {
            return strtotime(date("Y-m-d", time()) . " +" . $v);
    }
     /**
     * Extracts customer ids for those statuses that needs to be set as refunded from the posted item array
     * @param type $expire_customer_ids
     * @return array
     */
    public function sanitizeArrray($expire_customer_ids) {
        foreach ($expire_customer_ids as $id => $flag) {
            foreach ($flag as $k => $v) {
                if ($v) {
                    $refund_array[] = $k;
                }
            }
        }
        return $refund_array;
    }
    public function allowguest($observer) {
        $helper=Mage::helper('icc_premiumaccess');
        $premiumOptions = $helper->getPremiumAccessType();
            $quote = $observer->getEvent()->getQuote();
            $result = $observer->getEvent()->getResult();
            foreach ($quote->getAllItems() as $item){
                if (in_array(Mage::getModel('catalog/product')->load($item->getProductId())->getData('item_type'),$premiumOptions)){
                    $result->setIsAllowed(false); //don't allow checkout
                    break;
                }
            }
    }
    
 }