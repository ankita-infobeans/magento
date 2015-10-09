<?php
/**
 * Created by Gorilla Group.
 * Project: icc-dev.com
 * User:    Aleksey Rybitskiy
 * Date:    7/16/13
 * Time:    7:24 PM
 */

class ICC_TaxRates_Model_Downloadable {

    public $showLog = FALSE;

    public function run() {

        /* CREATE NEW CLASS */

Mage::log("Starting load collection");
        $classes = Mage::getModel('tax/class')->getCollection()
            ->addFieldToFilter('class_name', array('eq'=>'Downloadable'))
            ->load();
Mage::log("collection loaded");
        if($classes->count()) {
            $downloadableClass = $classes->getFirstItem();
            if($this->showLog) echo "Found downloadable class\n";
Mage::log("Found downloadable class");
        }
        else {
            $downloadableClass = Mage::getModel('tax/class');
            $downloadableClass->setData(array(
                'class_name'    =>  'Downloadable',
                'class_type'    =>  'PRODUCT',
            ));
            $downloadableClass->save();
            if($this->showLog) echo "Downloadable class created\n";
Mage::log("Downloadable class created");
        }

        /* SET DOWNLOADABLE CLASS FOR ALL PRODUCTS */
        $products = Mage::getModel('catalog/product')->getCollection();
        $products->addAttributeToFilter('type_id', array('eq'=>'downloadable'));
        $products->load();
        foreach($products AS $product) {
            $product->setTaxClassId($downloadableClass->getId());
            $product->save();
            if($this->showLog) echo $product->getName() . " updated\n";
Mage::log($product->getName() . " updated");
        }
        if($this->showLog) echo "Updated ".$products->count()."\n";
Mage::log("Updated ".$products->count());

        /* GET CALIFORNIA RATES */
        $rates = Mage::getModel('tax/calculation_rate')->getCollection()
            ->addFieldToFilter('cast(substring(tax_postcode,1,5) as unsigned)', array(
                array("from" => 90001, "to" => 96162)
            ))
            ->load()
        ;
        $californiaRates = array();
        foreach($rates AS $rate) {
            $californiaRates[] = $rate->getId();
        }
        // COPY RULES
        $ruleCollection = Mage::getModel('tax/calculation_rule')->getCollection();
        foreach($ruleCollection AS $ruleModel) {
            if(! preg_match('#_california$#', $ruleModel->getCode())) {
                $ruleModels = Mage::getModel('tax/calculation_rule')
                    ->getCollection()
                    ->addFieldToFilter('code', array('eq'=>$ruleModel->getCode().'_california'))
                    ->load();
                if($ruleModels->count()) {
                    $newRule = $ruleModels->getFirstItem();
                }
                else {
                    $newRule = Mage::getModel('tax/calculation_rule');
                    $newRule->setData(array(
                        'code'      =>  $ruleModel->getCode() . '_california',
                        'priority'  =>  '0',
                    ));
                    $newRule->setData('tax_customer_class', array());
                    $newRule->setData('tax_product_class', array());
                    $newRule->setData('tax_rate', array());
                    $newRule->save();
                }
                $currentModelCustomerClasses = $ruleModel->getCustomerTaxClasses();
                $currentRequestCustomerClasses = array_values(array_unique($currentModelCustomerClasses));
                $currentRequestProductClasses = array($downloadableClass->getId());
                $currentModelRates = $ruleModel->getRates();
                $currentRequestRates = array_values(array_unique($currentModelRates));

                foreach($currentRequestRates AS $k => $v) {
                    if(in_array($v, $californiaRates)) {
                        unset($currentRequestRates[$k]);
                    }
                }
                $currentRequestRates = array_values($currentRequestRates);

                $newRule->setData('tax_customer_class', $currentRequestCustomerClasses);
                $newRule->setData('tax_product_class', $currentRequestProductClasses);
                $newRule->setData('tax_rate', $currentRequestRates);

                $newRule->save();
                if($this->showLog) echo "Copied ".$currentRequestRates." rules\n";
Mage::log("Copied ".$currentRequestRates." rules");
            }
        }

    }

}
