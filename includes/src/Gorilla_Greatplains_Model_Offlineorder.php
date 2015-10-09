<?php

class Gorilla_Greatplains_Model_Offlineorder extends Mage_Catalog_Model_Abstract {

    private $gp;

    public function getOfflineOrder() {
        $customer = $this->getCustomer();
        $this->gp = new Gorilla_Greatplains_Model_Soap ();
        $data = $this->gp->getOfflineOrderSummary(
                $customer->getCustomerNo(), $customer->getOrgCustomerNo(), $customer->getLastname());

        $offlineorders = $data->_return;

        return $offlineorders;
    }

    public function getId() {
        
    }

    public function getCustomer() {

        $session = Mage::getSingleton('customer/session');

        $cid = $session->getId();
        $customer = Mage::getModel('customer/customer')->load($cid);

        return $customer;
    }

    public function getCustomerId() {

        $session = Mage::getSingleton('customer/session');

        $cid = $session->getId();
        $customer = Mage::getModel('customer/customer')->load($cid);

        return $customer->getCustomerNo();

        //return '0111693';
        echo "<pre>";
        print_r(Mage::helper('customer')->getCustomer()->getData());
        echo "</pre>";
        // echo Mage::helper('customer')->getCustomer()->getCustomerNo();
        exit;
        return Mage::helper('customer')->getCustomer()->getCustomerNo();
    }

}