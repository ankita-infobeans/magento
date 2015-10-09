<?php

class Icc_Ecodes_Block_Checkout_Multishipping_Success_Downloadable extends Mage_Checkout_Block_Multishipping_Success
{
    public function getDownloadablePurchases()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection()->getOrderInfo(array($orderId));
        return $downloadableCollection;
    }
}