<?php

class ICC_Volumelicense_Model_Volumelicense extends Mage_Core_Model_Abstract {

    /**
     * Sends purchaser an email to distribute all the purchased volumelicense product as configured by admin 
     */
    public function notificationEmail()
    {
        $helper = Mage::helper('volumelicense');
        $subscription = Mage::getModel('sales/order')->getCollection()
        ->addFieldToSelect('volume_emails_sent')
        ->addFieldToSelect('created_at')
        ->addFieldToSelect('customer_id')
        ->addFieldToSelect('increment_id')
        ->addFieldToSelect('entity_id')
        ->addFieldToSelect('email_weekly')
        ->addFieldToSelect('customer_email')
        ->addFieldToFilter('volume_license', 1)
        ->addAttributeToFilter('parent_order_id', array('null' => true)) ;
        //foreach($order as $odr){
        //    $childData = $helper->checkEmailStatus($odr, $odr->getCustomerEmail());
       // }
    
       // $subscription = $helper->checkNotifyData();

        $first_notification = (int)Mage::getStoreConfig('email_frequency/notification_period/first_notification_period');
        $second_notification = (int)Mage::getStoreConfig('email_frequency/notification_period/second_notification_period');
        if($first_notification == '' || (!$first_notification)){
            $first_notification = 2 ;
        }
        if($second_notification == '' || (!$second_notification)){
            $second_notification = 14 ;
        }
        foreach($subscription as $sub):
            $childData = $helper->checkEmailStatus($sub, $sub->getCustomerEmail());
            if(!is_array($childData)){
                continue;
            }
            
         
          // echo "<pre>";  print_r($sub->getIncrementId());  echo "<br><br>";
            if(!$sub->getVolumeEmailsSent()):
                $createdDate = $sub->getCreatedAt();
                $createTime = strtotime($createdDate) + 86400;
                $twodaysTime = $createTime +  ($first_notification * 24 * 60 * 60);
                if(time() > $twodaysTime): // Check for two days from first notification config
                   $customer=Mage::getModel('customer/customer')->load($sub->getCustomerId());
                   $helper->volumeLicenseNotifyEmail($customer->getEmail(),$customer->getName(),
                   $childData['name'],$childData['created_date'], $childData['max_register'],$childData['register_count'],$sub->getIncrementId());
                   $subs= Mage::getModel('sales/order')->load($sub->getId());
                   $this->sendEmailsToUnregistered($subs);
                   $subs->setVolumeEmailsSent(1);
                   $twoweeksTime = $createTime + ($second_notification * 24 * 60 * 60);
                   $new_date = date('Y-m-d H:i:s', $twoweeksTime);
                   $subs->setEmailWeekly($new_date);
                   $subs->save();
                endif;
            else:
                // for two week notification 
                $weekDate = $sub->getEmailWeekly();
                //Weekdate will not be empty, here we are taking extra measures :)
                if($weekDate == '0000-00-00 00:00:00'){
                    $weekDate = date('Y-m-d H:i:s');
                }
                $weekDatetime = strtotime($weekDate) + 86400;
                if($weekDate):
                    if(time() > $weekDatetime): // Check for two weeks
                       $customer=Mage::getModel('customer/customer')->load($sub->getCustomerId());
                       $helper->volumeLicenseNotifyEmail($customer->getEmail(),$customer->getName(),
                       $childData['name'],$childData['created_date'], $childData['max_register'],$childData['register_count'],$sub->getIncrementId());
                       $subs= Mage::getModel('sales/order')->load($sub->getId());
                       $this->sendEmailsToUnregistered($subs);
                       $subs->setVolumeEmailsSent(1);
                       $twoweeksTime = $weekDatetime + ($second_notification * 24 * 60 * 60);
                       $new_date = date('Y-m-d H:i:s', $twoweeksTime);
                       $subs->setEmailWeekly($new_date);
                       $subs->save();
                    endif;
                endif;
            endif;         
        endforeach;
    }
    public function sendEmailsToUnregistered($parent_order)
    {
        $helper = Mage::helper('volumelicense');
        $customer=Mage::getModel('customer/customer')->load($parent_order->getCustomerId());
        $parent_name = $parent_order->getCustomerFirstname()." ".$parent_order->getCustomerLastname(); 
        $locale = Mage::app()->getLocale();
        $date = $locale->date( $parent_order->getCreatedAt(), $locale->getDateFormat(), $locale->getLocaleCode(), false )->toString($locale->getDateTimeFormat()) ;
        $items = $parent_order->getAllVisibleItems();
                    foreach($items as $i):
                      $name[] = $i->getName();
                    endforeach;
        $product_name = implode(' ,',$name);
        $childs = Mage::getModel('sales/order')->getCollection()
                    ->addFieldToFilter('volume_license', 1)->addFieldToFilter('parent_order_id', $parent_order->getId())
                    ->addFieldToFilter('status', array('neq' => 'canceled'));
        foreach($childs as $child){
            if($child->getFutureEmail() != NULL){
                $helper->volumeLicenseNotifyUnregistered($child->getFutureEmail(), $parent_name, $product_name, $date, $child->getIncrementId());
            }
           
        }
       
    }
    
     public function assignVolumelicense($customer)
     {
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
    
}
