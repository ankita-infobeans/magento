<?php
class ICC_NewBundleProduct_Block_Catalog_Product_View_Type_Bundle extends Mage_Bundle_Block_Catalog_Product_View_Type_Bundle {
    public function getProduct()
    {
            if (Mage::registry('grouped-product-child-product')){
                    return Mage::registry('grouped-product-child-product');
            }
            return parent::getProduct();
    }

    public function getOptions()
    {
            if (!$this->_options || $this->getSkipOptionsCache()) {
                    $product = $this->getProduct();
                    $typeInstance = $product->getTypeInstance(true);
                    $typeInstance->setStoreFilter($product->getStoreId(), $product);

                    $optionCollection = $typeInstance->getOptionsCollection($product);

                    $selectionCollection = $typeInstance->getSelectionsCollection(
                                    $typeInstance->getOptionsIds($product),
                                    $product
                    );

                    $this->_options = $optionCollection->appendSelections($selectionCollection, false, false);
            }

            return $this->_options;
    }
    /*
    * old code
    public function getJsonConfig() {
            Mage::app ()->getLocale ()->getJsPriceFormat ();
            $optionsArray = $this->getOptions ();
            $options = array ();
            $selected = array ();
            $currentProduct = $this->getProduct ();
            $coreHelper = Mage::helper ( 'core' );

            if ($preconfiguredFlag = $currentProduct->hasPreconfiguredValues ()) {
                    $preconfiguredValues = $currentProduct->getPreconfiguredValues ();
                    $defaultValues = array ();
            }

            foreach ( $optionsArray as $_option ) {
                    if (! $_option->getSelections ()) {
                            continue;
                    }

                    $optionId = $_option->getId ();
                    $option = array (
                                    'selections' => array (),
                                    'title' => $_option->getTitle (),
                                    'isMulti' => in_array ( $_option->getType (), array (
                                                    'multi',
                                                    'checkbox' 
                                    ) ) 
                    );

                    $selectionCount = count ( $_option->getSelections () );

                    $bundleTierPrices = $currentProduct->getTierPrice ();
                    $tierPercent = 0;
                    foreach ( $bundleTierPrices as $tier ) {
                            if ($tier ['cust_group'] == Mage::getSingleton ( 'customer/session' )->getCustomerGroupId () && $tier ['price_qty'] == 1) {
                                    $tierPercent = $tier ['price'];
                            }
                    }

                    foreach ( $_option->getSelections () as $_selection ) {
                            $selectionId = $_selection->getSelectionId ();
                            $_qty = ! ($_selection->getSelectionQty () * 1) ? '1' : $_selection->getSelectionQty () * 1;
                            // recalculate currency
                            $tierPrices = $_selection->getTierPrice ();
                            foreach ( $tierPrices as &$tierPriceInfo ) {
                                    $tierPriceInfo ['price'] = $coreHelper->currency ( $tierPriceInfo ['price'], false, false );
                            }
                            unset ( $tierPriceInfo ); // break the reference with the last element

                            $taxPercent = 0;
                            $taxClassId = $_selection->getTaxClassId ();
                            if ($taxClassId) {
                                    $request = Mage::getSingleton ( 'tax/calculation' )->getRateRequest ();
                                    $taxPercent = Mage::getSingleton ( 'tax/calculation' )->getRate ( $request->setProductClassId ( $taxClassId ) );
                            }

                            $itemPrice = $_selection->getFinalPrice ();

                            // if ($_selection->getSelectionPriceValue() != 0) {
                            if ($_selection->getSelectionPriceType ()) { // percent
                                    $itemPrice = $currentProduct->getFinalPrice () * $_selection->getSelectionPriceValue () / 100;
                            } else { // fixed
                                    $itemPrice = $_selection->getSelectionPriceValue ();
                            }
                            // }

                            $canApplyMAP = false;

                            // @var $taxHelper Mage_Tax_Helper_Data 
                            $taxHelper = Mage::helper ( 'tax' );

                            $_priceInclTax = $taxHelper->getPrice ( $_selection, $itemPrice, true );
                            $_priceExclTax = $taxHelper->getPrice ( $_selection, $itemPrice );
                            if ($currentProduct->getPriceType () == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                                    $_priceInclTax = $taxHelper->getPrice ( $currentProduct, $itemPrice, true );
                                    $_priceExclTax = $taxHelper->getPrice ( $currentProduct, $itemPrice );
                            }

                            $selection = array (
                                            'qty' => $_qty,
                                            'customQty' => $_selection->getSelectionCanChangeQty (),
                                            'price' => $coreHelper->currency ( $_selection->getFinalPrice (), false, false ),
                                            'priceInclTax' => $coreHelper->currency ( ($_priceInclTax - ($_priceInclTax * ($tierPercent / 100))), false, false ),
                                            'priceExclTax' => $coreHelper->currency ( ($_priceExclTax - ($_priceExclTax * ($tierPercent / 100))), false, false ),
                                            'priceValue' => $coreHelper->currency ( $_selection->getSelectionPriceValue (), false, false ),
                                            'priceType' => $_selection->getSelectionPriceType (),
                                            'tierPrice' => $tierPrices,
                                            'name' => $_selection->getName (),
                                            'plusDisposition' => 0,
                                            'minusDisposition' => 0,
                                            'canApplyMAP' => $canApplyMAP 
                            );

                            $responseObject = new Varien_Object ();
                            $args = array (
                                            'response_object' => $responseObject,
                                            'selection' => $_selection 
                            );
                            Mage::dispatchEvent ( 'bundle_product_view_config', $args );
                            if (is_array ( $responseObject->getAdditionalOptions () )) {
                                    foreach ( $responseObject->getAdditionalOptions () as $o => $v ) {
                                            $selection [$o] = $v;
                                    }
                            }
                            $option ['selections'] [$selectionId] = $selection;

                            if (($_selection->getIsDefault () || ($selectionCount == 1 && $_option->getRequired ())) && $_selection->isSalable ()) {
                                    $selected [$optionId] [] = $selectionId;
                            }
                    }
                    $options [$optionId] = $option;

                    // Add attribute default value (if set)
                    if ($preconfiguredFlag) {
                            $configValue = $preconfiguredValues->getData ( 'bundle_option/' . $optionId );
                            if ($configValue) {
                                    $defaultValues [$optionId] = $configValue;
                            }
                    }
            }

            $config = array (
                            'options' => $options,
                            'selected' => $selected,
                            'bundleId' => $currentProduct->getId (),
                            'priceFormat' => Mage::app ()->getLocale ()->getJsPriceFormat (),
                            'basePrice' => $coreHelper->currency ( $currentProduct->getPrice (), false, false ),
                            'priceType' => $currentProduct->getPriceType (),
                            'specialPrice' => $currentProduct->getSpecialPrice (),
                            'includeTax' => Mage::helper ( 'tax' )->priceIncludesTax () ? 'true' : 'false',
                            'isFixedPrice' => $this->getProduct ()->getPriceType () == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED,
                            'isMAPAppliedDirectly' => Mage::helper ( 'catalog' )->canApplyMsrp ( $this->getProduct (), null, false ) 
            );

            if ($preconfiguredFlag && ! empty ( $defaultValues )) {
                    $config ['defaultValues'] = $defaultValues;
            }

            return $coreHelper->jsonEncode ( $config );
    }
    */
    
    public function getJsonConfig()
    {
        Mage::app()->getLocale()->getJsPriceFormat();
        $optionsArray = $this->getOptions();
        $options      = array();
        $selected     = array();
        $currentProduct = $this->getProduct();
        $coreHelper   = Mage::helper('core');

        if ($preconfiguredFlag = $currentProduct->hasPreconfiguredValues()) {
            $preconfiguredValues = $currentProduct->getPreconfiguredValues();
            $defaultValues       = array();
        }

        foreach ($optionsArray as $_option) {
            if (!$_option->getSelections()) {
                continue;
            }

            $optionId = $_option->getId();
            $option = array (
                'selections' => array(),
                'title'      => $_option->getTitle(),
                'isMulti'    => in_array($_option->getType(), array('multi', 'checkbox'))
            );

            $selectionCount = count($_option->getSelections());

            foreach ($_option->getSelections() as $_selection) {
                $selectionId = $_selection->getSelectionId();
                $_qty = !($_selection->getSelectionQty()*1) ? '1' : $_selection->getSelectionQty()*1;
                // recalculate currency
                $tierPrices = $_selection->getTierPrice();
                foreach ($tierPrices as &$tierPriceInfo) {
                    $tierPriceInfo['price'] = $coreHelper->currency($tierPriceInfo['price'], false, false);
                }
                unset($tierPriceInfo); // break the reference with the last element

                $taxPercent = 0;
                $taxClassId = $_selection->getTaxClassId();
                if ($taxClassId) {
                    $request = Mage::getSingleton('tax/calculation')->getRateRequest();
                    $taxPercent = Mage::getSingleton('tax/calculation')->getRate(
                        $request->setProductClassId($taxClassId)
                    );
                }

                $itemPrice = $_selection->getFinalPrice();
                if ($currentProduct->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                    //if ($_selection->getSelectionPriceValue() != 0) {
                        if ($_selection->getSelectionPriceType()) { // percent
                            $itemPrice = $currentProduct->getFinalPrice() * $_selection->getSelectionPriceValue() / 100;
                        } else { // fixed
                            $itemPrice = $_selection->getSelectionPriceValue();
                        }
                    //}
                }
                else if($currentProduct->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC)
                {
                    if ($_selection->getSelectionPriceValue() != 0) {
                        if ($_selection->getSelectionPriceType()) { // percent
                            $itemPrice = $currentProduct->getFinalPrice() * $_selection->getSelectionPriceValue() / 100;
                        } else { // fixed
                            $itemPrice = $_selection->getSelectionPriceValue();
                        }
                    }
                }
                    

                $canApplyMAP = false;

                /* @var $taxHelper Mage_Tax_Helper_Data */
                $taxHelper = Mage::helper('tax');

                $_priceInclTax = $taxHelper->getPrice($_selection, $itemPrice, true);
                $_priceExclTax = $taxHelper->getPrice($_selection, $itemPrice);

                if ($currentProduct->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                    $_priceInclTax = $taxHelper->getPrice($currentProduct, $itemPrice, true);
                    $_priceExclTax = $taxHelper->getPrice($currentProduct, $itemPrice);
                }

                $selection = array (
                    'qty'       => $_qty,
                    'customQty' => $_selection->getSelectionCanChangeQty(),
                    'price'     => $coreHelper->currency($_selection->getFinalPrice(), false, false),
                    'priceInclTax'  => $coreHelper->currency($_priceInclTax, false, false),
                    'priceExclTax'  => $coreHelper->currency($_priceExclTax, false, false),
                    'priceValue' => $coreHelper->currency($_selection->getSelectionPriceValue(), false, false),
                    'priceType' => $_selection->getSelectionPriceType(),
                    'tierPrice' => $tierPrices,
                    'name'      => $_selection->getName(),
                    'plusDisposition' => 0,
                    'minusDisposition' => 0,
                    'canApplyMAP'      => $canApplyMAP
                );

                $responseObject = new Varien_Object();
                $args = array('response_object' => $responseObject, 'selection' => $_selection);
                Mage::dispatchEvent('bundle_product_view_config', $args);
                if (is_array($responseObject->getAdditionalOptions())) {
                    foreach ($responseObject->getAdditionalOptions() as $o=>$v) {
                        $selection[$o] = $v;
                    }
                }
                $option['selections'][$selectionId] = $selection;

                if (($_selection->getIsDefault() || ($selectionCount == 1 && $_option->getRequired()))
                    && $_selection->isSalable()
                ) {
                    $selected[$optionId][] = $selectionId;
                }
            }
            $options[$optionId] = $option;

            // Add attribute default value (if set)
            if ($preconfiguredFlag) {
                $configValue = $preconfiguredValues->getData('bundle_option/' . $optionId);
                if ($configValue) {
                    $defaultValues[$optionId] = $configValue;
                }
            }
        }

        $config = array(
            'options'       => $options,
            'selected'      => $selected,
            'bundleId'      => $currentProduct->getId(),
            'priceFormat'   => Mage::app()->getLocale()->getJsPriceFormat(),
            'basePrice'     => $coreHelper->currency($currentProduct->getPrice(), false, false),
            'priceType'     => $currentProduct->getPriceType(),
            'specialPrice'  => $currentProduct->getSpecialPrice(),
            'includeTax'    => Mage::helper('tax')->priceIncludesTax() ? 'true' : 'false',
            'isFixedPrice'  => $this->getProduct()->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED,
            'isMAPAppliedDirectly' => Mage::helper('catalog')->canApplyMsrp($this->getProduct(), null, false)
        );

        if ($preconfiguredFlag && !empty($defaultValues)) {
            $config['defaultValues'] = $defaultValues;
        }

        return $coreHelper->jsonEncode($config);
    }
}
?>
