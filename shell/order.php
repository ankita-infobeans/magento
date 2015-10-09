<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

require_once 'abstract.php';

/**
 * Magento Compiler Shell Script
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Shell_Order extends Mage_Shell_Abstract
{

    
    const APPLY_TAX_AFTER_DISCOUNT = 1;
    const APPLY_TAX_BEFORE_DISCOUNT = 0;
const XMLPATH_APPLY_TAX_AFTER = 'tax/calculation/apply_after_discount';
    public function run()
    {
        $order = Mage::getModel('sales/order')->load(182395);
        

        $shipping = $order->getShippingAddress();
        if (empty($shipping)) {
            $shipping = $order->getBillingAddress();
        } 

        foreach ($order->getAllItems() as $item) {
             echo "Item # {$item->getId()}:\n";
             
        // tax calculator
        $request = Mage::getSingleton("tax/calculation");
        $customer = $this->getCustomerByOrder($order);
        $request->setCustomer($customer);
        
        $request = $request->getRateRequest($shipping, $order->getBillingAddress());
//print_r($request->getData());       
 $calc = new Mage_Tax_Model_Resource_Calculation();
//print_r($item->getData());
$product = Mage::getModel('catalog/product')->load($item->getProductId());
//echo $product->getTaxClassId();
$request->setProductClassId($product->getTaxClassId());        
$taxData = $calc->getRateInfo($request);
        $taxData = $taxData['process'][0]['rates'];
        
        $taxes = array();
        foreach ($taxData as $t) {//print_r($t);
            $taxes[] = $this->processTax($t);
        }

// Unique id
/*$oldtaxes = $taxes;
$taxes = array();
$ids = array();
foreach($oldtaxes AS $tx) {
  if(! @$ids[$tx['id']]) {
    $ids[$tx['id']] = TRUE;
    $taxes[] = $tx;
  }
}*/

print_r($taxes);
$taxdata = $taxes;             
$TaxLines = array();
        if (empty($taxdata)) {
            $TaxLines[] = new Gorilla_Greatplains_Model_Source_Data_OrderLineTaxes();
        } else {
            $itemTax = $item->getTaxAmount();
            $baseprice = $item->getRowTotal();
            $taxableprice = $this->getTaxablePrice($baseprice, $item->getDiscountAmount());

            $totalTax = 0;
            foreach ($taxdata as $line) {
                $t = new Gorilla_Greatplains_Model_Source_Data_OrderLineTaxes($line, $taxableprice,$itemTax);
                $TaxLines[] = $t;
                $totalTax += $t->TaxAmount;
            }

            //$itemtax = round($item, 2);

            if ($totalTax < $itemTax) {
                $difference = $itemTax - $totalTax;
                $TaxLines[0]->TaxAmount += $difference;
            }
            if ($totalTax > $itemTax) {
                $difference = $totalTax - $itemTax;
                $TaxLines[0]->TaxAmount -= $difference;
            }

        }

            print_r($TaxLines);

        }
    }


    
    protected function getTaxablePrice($basePrice, $discountAmount)
    {
        $taxableprice = $basePrice - $discountAmount;
        switch (Mage::getStoreConfig(self::XMLPATH_APPLY_TAX_AFTER)) {
            case self::APPLY_TAX_BEFORE_DISCOUNT :
                $taxableprice = $basePrice;
                break;
            case self::APPLY_TAX_AFTER_DISCOUNT :
                $taxableprice = $basePrice - $discountAmount;
                break;
        }
        return $taxableprice;
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

$shell = new Mage_Shell_Order();
$shell->run();
