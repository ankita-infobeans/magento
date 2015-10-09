<?php

class Gorilla_Paymentech_Model_Observer extends Mage_Core_Model_Observer {

    public function saveOrderAfter($observer) {
        $event = $observer->getEvent();;
        $order = $event->getOrder();
        $quote = $event->getQuote();
       
        $paymentInfo = $quote->getPayment()->getAdditionalInfo('paymentech_card');
        $order->getPayment()->setAdditionalInformation('paymentech_card',$paymentInfo);
        $order->save();
        return $this;
    }
   

     
}