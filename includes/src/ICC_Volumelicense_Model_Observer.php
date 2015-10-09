<?php
class ICC_Volumelicense_Model_Observer extends Mage_Core_Model_Abstract {

    /**
     * When a pending customer registers we are assigning him the shared volume license. 
     * @param type $observer
     */
    public function assignVolumelicense($observer) {
        $event = $observer->getEvent();
        $customer = $event->getCustomer();
        if($customer){
	  $email = $customer->getEmail();
	  $order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('future_email', $email);
	  if (sizeof($order) != 0) {
	      foreach ($order as $od) {
		  $od->setCustomerFirstname($customer->getFirstname());
		  $od->setCustomerLastname($customer->getLastname());

		  $newCustomerEmail = $customer->getEmail();
		  $od->setCustomerId($customer->getId());
		  $od->setCustomerEmail($customer->getEmail());
		  $od->setFutureEmail(NULL);
		  //  $od->addStatusHistoryComment('The owner has been changed from customer: ' . $oldCustomerEmail . ' to customer: ' . $customer->getEmail() . ' by user ' . Mage::getSingleton('admin/session')->getUser()->getUsername());

		  $items = $od->getAllItems();
		  try {
		      $od->save();
		      foreach ($items as $item) {
			  if ($item->getProductType() == 'downloadable') {
			      $downloadableLinks = Mage::getModel('downloadable/link_purchased')
				      ->getCollection()
				      ->addFieldToFilter('order_item_id', $item->getItemId());

			      foreach ($downloadableLinks->getItems() as $link) {
				  $link->setCustomerId($customer->getId());
				  $link->save();
			      }
			  }
		      }
		  /*  $parent_order_id = $od->getParentOrderId();
		      $parentOrderData = Mage::getModel('sales/order')->load($parent_order_id);
		      $parentEmail = $parentOrderData->getCustomerEmail();
		  */
		  // $helper->volumeLicenseShareEmail($toEmail,$purchasingAgentMailData,$parentEmail);
		  } catch (Exception $e) {
		      Mage::log($e->getMessage());
		  }
	      }
	  }
       }
    }

    public function createChildOrder($observer) {
        $helper = Mage::helper('volumelicense');
        $order = Mage::getModel('sales/order')->load($observer->getInvoice()->getOrderId());
        /* $data_array = array('order_number' => $order->getIncrementId(), 
          'parent_order_num' => 0, 'email' => $order->getCustomerEmail(),'customer_id' => $order->getCustomerId()); */
        //$helper->setReportsLog($order);
        if($order->getData('volume_license')) {
	  $helper->setReportsLog($order);
	}
	
        $storeId = Mage::app()->getStore()->getStoreId();
        $uniqueEmailArray = array();
        $sendPAEmail = false;
        foreach ($order->getAllItems() as $item) {
            // echo "<pre>";print_r($item->getData());die;
            $obj = Mage::getModel('catalog/product');
            $_product = $obj->load($item->getProductId());
            //Mage::log($_product->getData(),null,'testae.log');
            //Mage::log($_product->getData('volume_license'),null,'testae.log');
            //if ($_product->getData('volume_license')) { // && $item->getData('has_children')) {
            $volume_license = $_product->getResource()->getAttribute('volume_license')->getFrontend()->getValue($_product);
            if (($item->getProductType() == 'downloadable') && ($item->getQtyOrdered() > 1 ) && ($volume_license == 'Yes')) {
                $purchasingAgentEmail = $order->getData('customer_email');
                $purchasingAgentName = $order->getData('customer_firstname');
                $purchasingAgentDetail = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $purchasingAgentEmail)->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
                $emails = unserialize($order->getVolumeUsers());
                //Mage::log($order->getVolumeUsers(),null,'testae.log');
                //Mage::log($emails,null,'testae.log');
                //echo "<pre>=====";print_r($emails); die("Stopppp");
                //echo "<pre>=====";
                //echo $item->getId();die;
                $email_array = $emails[$item->getQuoteItemId()];
                $customer = Mage::getModel("customer/customer");
                $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                $userCount = count($email_array);
                $item_name = $item->getName();
                $purchasingAgentMailData = '';
                $giftUserMailData = '';
                //echo "<pre>";print_r($email_array);die("vllllllllll");
                //$purchasingAgentMailData .= "You have purchase $item_name volume license for $userCount users.<br />";
                $purchasingAgentMailData = $order->getIncrementId();
                $myEcodeUrl = Mage::getUrl('ecodes/account/products');
                $customerRegisterUrl = Mage::getUrl('ecodes/account/products');
		$purchasingAgentCheck = 0;
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
                   // foreach ($multipleEmail as $key => $value) {

                        if ($multipleEmail['email'] == '') {
                            //echo $item->getQuoteItemId()."=====";
                            $value = $purchasingAgentEmail;
                            $purchasingAgentCheck = $purchasingAgentCheck + 1;
                        }
                        else {
			    $value = $multipleEmail['email'];
                        }
                        $customer = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $value)->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
                        if ($customer->getData('entity_id')) {
                            //$uniqueEmailArray[$value][] = 'exist';
                            //echo "<pre>";print_r($item);die("==============___________");
                            //$this->generateOrder($customer, $item,null,$order);
                            if ($customer->getData('email') != $purchasingAgentEmail) {
                                if (!array_key_exists($value, $uniqueEmailArray)) {
                                    $uniqueEmailArray[$value] = '';
                                }
                                //$uniqueEmailArray[$value]['yes'][] = $item->getId();
                                $gufirstName = $customer->getData('firstname');
                                $uniqueEmailArray[$value] .= $item_name . " and ";
                                /* $uniqueEmailArray[$value] .= " <strong>Dear $gufirstName </strong>,<br/>
                                  <p><b> $purchasingAgentName </b> have gifted you Volume license product $item_name.</p> <p> To View the $item_name <a href='".$myEcodeUrl."'>click here </a></p>"; */
                            }

                            $this->generateOrder($customer, $item, null, $order,$bundleFlag);
                        } else {
                            $customer = $purchasingAgentDetail;

                            if (!array_key_exists($value, $uniqueEmailArray)) {
                                $uniqueEmailArray[$value] = '';
                            }
                            $uniqueEmailArray[$value] .= $item_name . " and ";
                            //$uniqueEmailArray[$value]['no'][] = $item->getId();
                            /* $uniqueEmailArray[$value] .= " <strong>Dear $value </strong>,<br/>
                              <p> <b> $purchasingAgentName </b> have gifted you Volume license product $item_name.</p> <p>You are not registered with ICC StoreFront.Please click here for registeration <a href='".$customerRegisterUrl."'>click here </a>.</p>"; */
                            $this->generateOrder($customer, $item, $value, $order,$bundleFlag);
                        } //die("second");
                  //  }
                    
		      if($purchasingAgentCheck > 1) {
			$sendPAEmail = true;
		      }
                    
                }
            }
        }
        //echo "<pre>";print_r($uniqueEmailArray);die("==============___________");
        /* Send Email To Purchasing Agent Start */

	if($sendPAEmail) {
        $helper->volumeLicensePurchaseEmail($purchasingAgentEmail, $purchasingAgentName, $purchasingAgentMailData);
        }

        /* Send Email To Purchasing Agent End */

        /* Send Email To Gifted User Start */

        foreach ($uniqueEmailArray as $key => $value) {

            $customer = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $key)->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
            if ($customer->getData('entity_id')) {
                $priductData = rtrim($value, ' and ');
                $gufirstName = $customer->getData('firstname');
                $purchasingAgentMailData = "<strong>Dear $gufirstName </strong>,<br/><p><b> $purchasingAgentName </b> have assigned you Volume license product $priductData.</p> <p> To View the $item_name <a href='" . $myEcodeUrl . "'>click here </a></p>";
            } else {
                $priductData = rtrim($value, ' and ');
                $purchasingAgentMailData = "<strong>Dear $key </strong>,<br/><p> <b> $purchasingAgentName </b> have assigned you Volume license product $priductData.</p> <p>You are not registered with ICC StoreFront.Please click here for registeration <a href='" . $customerRegisterUrl . "'>click here </a>.</p>";
            }
            //echo "<pre>".$purchasingAgentMailData;die;

            $helper->volumeLicenseShareEmail($key, $purchasingAgentMailData,$order->getCustomerEmail());
        }



        /* Send Email To Gifted User End */

        /* echo $purchasingAgentMailData."<br />".$giftUserMailData;
          echo "<pre>";print_r($uniqueEmailArray);die("==============___________");
          die("============"); */
    }

    
    
    
    
     public function generateOrder($customer, $parentItem, $futureEmail,$parentOrder,$bundleFlag) {
    
	    // get logged in cusomer id
	    
	    $parentCustomerDetail = $purchasingAgentDetail = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $parentOrder->getCustomerEmail())->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
	    $customerAccountNo = Mage::getModel('customer/session')->getCustomer()->getId();
	    // load customer object
	    
	    /*if($futureEmail == null) {
	    $customerObj = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $parentOrder->getCustomerEmail())->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
	    }
	    else {
	    $customerObj = Mage::getResourceModel('customer/customer_collection')->addFieldToFilter('email', $futureEmail)->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->getFirstItem();
	    }*/
	    $customerObj = $customer;
	    //echo "<pre>";print_r($customer->getData());die("================");
	    //$customerObj = Mage::getModel('customer/customer')->load($customerAccountNo);
	    // assign this customer to quote object, before any type of magento order, first create quote.
	    $quoteObj = Mage::getModel('sales/quote')->assignCustomer($customerObj);
	    $quoteObj = $quoteObj->setStoreId($customer->getStoreId());
	    
	    // product id
	    $productId = $parentItem->getProductId();
	    $productModel = Mage::getModel('catalog/product');
	    $productObj = $productModel->load($productId);
	    $productObj->setPrice(0);
	    
	    //echo "<pre>";print_r($productObj->getData());die("-========");
	    
	    
	    // for simple product
	    if ($productObj->getTypeId() == 'simple') {
		$quoteObj->addProduct($productObj , 1);
		// for downloadable product
	    } else if ($productObj->getTypeId() == 'downloadable') {
		$params = array();
		$links = Mage::getModel('downloadable/product_type')->getLinks( $productObj );
		$linkId = 0;
		foreach ($links as $link) {
		    $linkId = $link->getId();
		}
		$params['product'] = $productId;
		$params['qty'] = 1;
		$params['links'] = array($linkId);
		$request = new Varien_Object();
		$request->setData($params);
		$quoteObj->addProduct($productObj , $request);
	    }
	    //echo "<pre>";print_r($linkId);die("Stoppp");
	    // sample billing address
	    
	    
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
	    
	    $orderPaymentObj = $convertQuoteObj->paymentToOrderPayment($quotePaymentObj);
	    
	    $orderObj->setBillingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getBillingAddress()));
	    $orderObj->setPayment($convertQuoteObj->paymentToOrderPayment($quoteObj->getPayment()));
	    if (!$quoteObj->getIsVirtual()) {
		$orderObj->setShippingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getShippingAddress()));
	    }
	    // child order's comment's html that who has placed order for him
	  //$orderBy = "<strong>Order By</strong><br />";
	  //$orderBy .= 'Name : '.$parentCustomerDetail->getName().'<br />';
	  //$orderBy .= 'Order# : '.$orderObj->getIncrementId().'<br />';
	  //$orderBy .= 'Email : '.$parentCustomerDetail->getEmail().'<br />';
	  //$orderBy .= 'Captured Amount of $'.round($grandTotal, 2);
	  $orderBy = "Order has been generated.";

	    $orderObj->setBasePrice(0)
	    ->setOriginalPrice(0)
	    ->setRowTotal(0)
	    ->setTotalQtyOrdered(1)
	    ->setQtyOrdered(1)
	    ->setQtyInvoiced(1)
	    ->setBaseRowTotal(0)
	    ->setParentOrderItemId($parentItem->getId())
	    ->setVolumeLicense(true)
	    ->setFutureEmail($futureEmail)
	    ->setBundleFlag($bundleFlag)
	    ->setParentOrderId($parentOrder->getId());
	    //->addStatusToHistory('complete', $orderBy, false)
	   // ->setData('state', "complete")
	   // ->setStatus("complete");
	    
	    
	    // set payment options
	    //$orderObj->setPayment($convertQuoteObj->paymentToOrderPayment($quoteObj->getPayment()));
	    
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
		
		foreach($order->getAllItems() as $item) {
		   //$links= Mage::getModel('downloadable/link_purchased')->load(334465,'order_item_id');
		    $links = Mage::getModel('downloadable/link_purchased_item')->getCollection()->addFieldToFilter('order_item_id', $item->getItemId());
		   //echo $item->getId();
		 //echo "<pre>";print_r($links->getData());die;
		     foreach($links as $link) {
		     // echo "<pre>";print_r($link);die;
			   $link_status =  Mage::getModel('downloadable/link_purchased_item')->load($link->getId());
			   $link_status->setStatus('available');
			   $link_status->save();
			   //echo "<pre>";print_r($link_status->getData());die;
		      }
                      $orderItem = Mage::getModel('sales/order_item')->load($item->getId());
                      $orderItem->setQtyInvoiced($item->getQtyOrdered());
                      $orderItem->save();
                      $this->assingSerialcode($orderItem,$order->getIncrementId());
		}
		
				foreach($order->getAllItems() as $item) {
				    $orderItem = Mage::getModel('sales/order_item')->load($item->getId());
				    $orderItem->setVolumeLicense(1);
				    $orderItem->save();
		}
		
	     } catch (Exception $e){
		Mage::log($e->getMessage());
	    }
               
	       Mage::helper('volumelicense')->setReportsLog($order);
	       return;
	    
	    //die("Stopppp");
    
    }
    public function getSession()
    {
        if(!$this->getData('_session'))
        {
            $this->setData('_session', Mage::getSingleton('core/session'));
        }
        return $this->getData('_session');
    }
    public function assingSerialcode($orderItem,$orderIncrementId){
        //echo Mage::getModel('catalog/product')->load($orderItem->getProductId())->getSerialRequired();
        //die;
        $downloadableHelper = Mage::helper('ecodes/downloadable');
        if(Mage::getModel('catalog/product')->load($orderItem->getProductId())->getSerialRequired())
                    {
                        //$orderItem = Mage::getModel('sales/order_item')->load($item);
                        $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection();
                        $downloadableCollection->assignSerials($orderItem);
                        $serialsAdded = $downloadableCollection->getInfo('serials_added');
                        $numSerialsNotAssigned = $downloadableCollection->getInfo('num_serials_not_assigned');
                        Mage::log($serialsAdded,null,'serialcode.log');
                        Mage::log(" serial not assigned",null,'serialcode.log');
                        Mage::log($numSerialsNotAssigned,null,'serialcode.log');
                        $message = $downloadableHelper->generateSerialsAssignedHistoryComment(
                            $serialsAdded,
                            $numSerialsNotAssigned,
                            true
                        );
                        $message=$message." # ".$orderIncrementId;
                        $message = str_replace('.', '',$message);
                        
                        if($message)
                        {
                            $this->getSession()->addSuccess($message);
                        }
                    }
    }
    
   

    /**
     * Sets volume_license flag in sales flat order item table when order is placed, if the oder item has volume_license as "Yes"
     * @param type $event
     */
    public function isVolumeLicense($event) {
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
    public function updateRefundedVolumeLicense($observer) {
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

           /* foreach ($order->getAllItems() as $itemId => $orderItem) {
                $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
                if ($product->getTypeId() == 'downloadable') {
                    $product_links = Mage::getModel('downloadable/product_type')->getLinks($product);
                    foreach ($product_links as $link) {
                        try {
                            $link->setStatus(Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_EXPIRED);
                            $link->save();
                        } catch (Exception $ex) {
                            Mage::log($ex->getMessage());
                        }
                    }
                }
            }*/
            try {
		if($order->getVolumeLicense()) {
                $order->setData('state', "canceled");
                $order->setStatus("canceled");
                $history = $order->addStatusHistoryComment('This volume license product is refunded from parent order.', false);
                $history->setIsCustomerNotified(false);
                $order->save();
                }
            } catch (Exception $ex) {
                Mage::log($ex->getMessage());
            }
        }
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

    public function afterCartSave($event) {
        //Standard on the Design and Construction of Log Structures: ICC 400 - 2007

        $types = array(); //types of product in the cart
        $requiedVolumeStep = false;
        $items = $event->getCart()->getQuote()->getAllItems();   //get all items of quote

        foreach ($items as $item) {
            if ($item->getProduct()->getData('volume_license') && $item->getProduct()->getData('volume_license') == 1 && $item->getQty() > 1) {
                $requiedVolumeStep = true; /* Chenge it to True */
                break;
            }
        }         
        if (!$requiedVolumeStep) {
            $quote = Mage::getModel('sales/quote')->load($event->getCart()->getQuote()->getId());
            $quote->setVolumeLicense(false);
            $quote->setVolumeUsers(null);
            $quote->save();
            return;
        }
    }
    
}
