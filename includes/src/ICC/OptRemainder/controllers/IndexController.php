<?php
class ICC_OptRemainder_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    { 
        $this->loadLayout();
        $options = $this->getRequest()->getParam('options');   
        $orderDetail = unserialize(urldecode($options));
        $model = Mage::getModel("optremainder/optremainder");
        $value = $model->getCollection()->addFieldToFilter("order_id", array('eq' => $orderDetail['order_id']))
                                        ->addFieldToFilter("customer_email", array('eq' => $orderDetail['customer_email']));
        if (count($value) == 0) {
            Mage::getModel("optremainder/optremainder")
                ->setOrderId($orderDetail['order_id'])
                ->setCustomerEmail($orderDetail['customer_email'])
                ->setUserType($orderDetail['user_type'])
                ->setItemType($orderDetail['item_type'])
                ->setFlag(1)
                ->save();
        }
         $this->renderLayout();
    }
}   