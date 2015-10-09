<?php
class ICC_ChangeOrderOwner_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_errors     = array();
    protected $_newOwner   = null;
    protected $_movedOrder = null;

    public function validate($data){

        $incrementId = (isset($data['increment_id'])) ? $data['increment_id'] : '';
        $email       = (isset($data['customer_email'])) ? $data['customer_email'] : '';

        if (!Zend_Validate::is($incrementId, 'NotEmpty')) {
            $this->_errors[] = $this->__('Order increment id can\'t be empty');
        }

        if (!Zend_Validate::is($email, 'NotEmpty')) {
            $this->_errors[] = $this->__('Email can\'t be empty');
        }

        if (!Zend_Validate::is($incrementId, 'Digits')) {
            $this->_errors[] = $this->__('Please use numbers only in the "Order #" field. Please avoid spaces or other characters such as dots or commas.');
        }

        if (!Zend_Validate::is($email, 'EmailAddress')) {
            $this->_errors[] = $this->__('Please enter a valid email address in the "Customer email" field. For example johndoe@domain.com.');
        }

        if (empty($this->_errors)){
            $this->_checkOrder($incrementId);
        }

        if (empty($this->_errors)){
            $this->_checkCustomer($email);
        }

        if (empty($this->_errors)){
            return true;
        }

        return $this->_errors;
    }

    protected function _checkCustomer($email){
        $storeId = $this->_movedOrder->getStoreId();
        $customer = Mage::getModel('customer/customer')
            ->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToFilter('email', $email)
            ->addAttributeToFilter('store_id', $storeId)
            ->getFirstItem();

        if (is_null($customer->getId())){
            $this->_errors[] = $this->__('Customer with email: ' .$email. ' was not found in the store #' . $storeId);
            return;
        }
        $this->_newOwner = $customer;
    }

    protected function _checkOrder($incrementId){
        $order = Mage::getModel('sales/order')->load($incrementId, 'increment_id');
        if (is_null($order->getId())){
            $this->_errors[] = $this->__('There is no order with increment ID: ' .$incrementId);
            return;
        }
        $this->_movedOrder = $order;
    }

    public function getMovedOrder(){
        return $this->_movedOrder;
    }

    public function getNewOwner(){
        return $this->_newOwner;
    }
}