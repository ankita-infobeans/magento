<?php

class ICC_Premiumaccess_Model_Premiumaccess extends Mage_Core_Model_Abstract
{ 
    /**
     * This method used to send notification email for 2 days and 2 weeks for not completely shared customers.
     */
    public function notificationEmail()
    {      
        $subscription = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection()->getNotifySubscription();
        $helper=Mage::helper('icc_premiumaccess');
        foreach($subscription as $sub):
            if(!$sub->getEmailsSent()):
                $createdDate = $sub->getCreatedAt();
                $createTime = strtotime($createdDate) + 86400;
                $twodaysTime = $createTime + (2 * 24 * 60 * 60);
                if(time() > $twodaysTime): // Check for two days
                   $customer=Mage::getModel('customer/customer')->load($sub->getCustomerId());
                   $helper->premiumAccessNotifyEmail($customer->getEmail(),$customer->getName(),
                   $sub->getProductName(),$sub->getCreatedAt(),$sub->getSeatsTotal(),$sub->getRegisteredCount());
                   $subs= Mage::getModel('icc_premiumaccess/premiumaccess')->load($sub->getId());
                   $subs->setEmailsSent(1);
                   $twoweeksTime = $createTime + (14 * 24 * 60 * 60);
                   $new_date = date('Y-m-d H:i:s', $twoweeksTime);
                   $subs->setWeekMailDate($new_date);
                   $subs->save();
                endif;
            else:
                $weekDate = $sub->getWeekMailDate();
                $weekDatetime = strtotime($weekDate) + 86400;
                if($weekDate):
                if(time() > $weekDatetime): // Check for two days
                   $customer=Mage::getModel('customer/customer')->load($sub->getCustomerId());
                   $helper->premiumAccessNotifyEmail($customer->getEmail(),$customer->getName(),
                   $sub->getProductName(),$sub->getCreatedAt(),$sub->getSeatsTotal(),$sub->getRegisteredCount());
                   $subs= Mage::getModel('icc_premiumaccess/premiumaccess')->load($sub->getId());
                   $subs->setEmailsSent(1);
                   $twoweeksTime = $weekDatetime + (14 * 24 * 60 * 60);
                   $new_date = date('Y-m-d H:i:s', $twoweeksTime);
                   $subs->setWeekMailDate($new_date);
                   $subs->save();
                endif;
                endif;
            endif;         
        endforeach;
    }
    
    /**
     * Change status of the expired shopping cart price rule
     */
    public function checkCouponExpiryDate()
    {
        Mage::log('checkExpiry',null,'checkExpiry.log');
        $todayDate = date("Y-m-d");
        $collection = Mage::getModel('salesrule/rule')->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('to_date', array('lt' => $todayDate))
	    ->load();
        $collection->getSelect();
        foreach($collection as $model) {
	     $model->setIsActive(0);
             $model->save();
             Mage::log("RuleID: ".$model->getRuleId()."'s status has been changed <br />", null, 'checkExpiry.log');
        }	
    }
    
    public function assignPremiumaccess($customer)
     {
        if($customer){
	  $email = $customer->getEmail();
	  $order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('future_email', $email);
	  if (sizeof($order) != 0) {
	      foreach ($order as $od) {
		  $od->setCustomerFirstname($customer->getFirstname());
		  $od->setCustomerLastname($customer->getLastname());
		  $od->setCustomerId($customer->getId());
		  $od->setCustomerEmail($customer->getEmail());
		  $od->setFutureEmail(NULL);
		  try {
		      $od->save();
		  } catch (Exception $e) {
		      Mage::log($e->getMessage());
		  }
	      }
	  }
       }
    }
}
	 