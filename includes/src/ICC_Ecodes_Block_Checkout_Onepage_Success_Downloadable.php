<?php

class Icc_Ecodes_Block_Checkout_Onepage_Success_Downloadable extends Mage_Checkout_Block_Onepage_Success
{
    public function getDownloadablePurchases()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection()->getOrderInfo(array($orderId));
        return $downloadableCollection;
    }

    /**
     * Return url to download link
     *
     * @param \ICC_Ecodes_Model_Downloadable $item
     * @return string
     */
    public function getDownloadUrl(ICC_Ecodes_Model_Downloadable $item)
    {
        return $this->getUrl('downloadable/download/link', array('id' => $item->getLinkHash(), '_secure' => true));
    }

}