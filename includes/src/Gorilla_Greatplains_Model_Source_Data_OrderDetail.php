<?php

class Gorilla_Greatplains_Model_Source_Data_OrderDetail {

    public $_orderdetails;
    public $subtotal;
    public $taxdata;
    public $totalLineDiscounts;

    public function __construct($order) {
        $this->subtotal = 0;
        
        $orderId = (is_object($order)) ? $order->getId() : null;
        if ( (!is_object($order)) || empty($orderId) )  {
            Mage::helper("greatplains")->Log("No order object");
            return $this;
        }

        $items = $order->getAllItems();
        $this->_orderdetails = array();

        $shipping = $order->getShippingAddress();
        if (empty($shipping)) {
            $shipping = $order->getBillingAddress();
        } 
        
        // tax calculator
        $request = Mage::getSingleton("tax/calculation");
        $customer = $this->getCustomerByOrder($order);
        $request->setCustomer($customer);
        $request = $request->getRateRequest($shipping, $order->getBillingAddress());
        $calc = new ICC_TaxRates_Model_Resource_Calculation();
        $taxData = $calc->getRateInfo($request);
        $taxData = $taxData['process'][0]['rates'];
        
        $taxes = array();
        foreach ($taxData as $t) {
            $taxes[] = $this->processTax($t);
        }
        $this->taxdata = $taxes;

        $a = array();
        foreach ($items as $item) {
            //Check if item is a child of a bundled parent, except if parent is membership product.
            if ($parentId = $item->getParentItemId()) {
                if ($parentItem = $order->getItemById($parentId)) {
                    $parentSku = $parentItem->getSku();
                    $isMembership = false;
                    if (substr($parentSku,0,3) == 'MEM') {
                        $isMembership = true;
                    }
                    if ($parentItem->getProductType() == 'bundle' && !$isMembership) {
                        continue;
                    }
                }
            }

            // Fix
            // tax calculator
            $request = Mage::getSingleton("tax/calculation");
            $request->setCustomer($customer);
            $request = $request->getRateRequest($shipping, $order->getBillingAddress());
            $calc = new ICC_TaxRates_Model_Resource_Calculation();

            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $request->setProductClassId($product->getTaxClassId());

            $taxData = $calc->getRateInfo($request);
            $taxData = $taxData['process'][0]['rates'];

            $taxes = array();
            foreach ($taxData as $t) {
                $taxes[] = $this->processTax($t);
            }
            // End fix

            $a = new Gorilla_Greatplains_Model_Source_Data_OrderLine($order, $item, $taxes);
            $this->_orderdetails[] = $a;
            $this->subtotal += $a->ExtendedPrice;
            $this->totalLineDiscounts += ($a->PromotionAmount * $a->Quantity);
        }

        return $this;
    }

    function processTax($obj) {
        if (!isset($obj['code'])) {
            return false;
        }

        $taxarray = explode("_", $obj['code']);
        $taxinfo['detail'] = $taxarray[2];
        $taxinfo['id'] = $taxarray[3];
        $taxinfo['percent'] = $taxarray[4];
        return $taxinfo;
    }

    public function getCustomerByOrder($order)
    {
        $customer = null;
        if(is_object($order)){
            $customer = $order->getCustomer();
            if(!$customer){
                $customer = Mage::getModel("customer/customer")->load($order->getCustomerId()) ;
            }
        }
        if(!$customer){
            $customer = Mage::getModel("customer/customer");
        }
        return $customer;
    }

}

?>