<?php

class ICC_NewBundleProduct_Model_Catalog_Product_Price extends Mage_Bundle_Model_Product_Price
{
    
    const PRICE_TYPE_FIXED      = 1;
    const PRICE_TYPE_DYNAMIC    = 0;

    /**
     * Flag wich indicates - is min/max prices have been calculated by index
     *
     * @var bool
     */
    protected $_isPricesCalculatedByIndex;

    /**
     * Is min/max prices have been calculated by index
     *
     * @return bool
     */
    public function getIsPricesCalculatedByIndex()
    {
        return $this->_isPricesCalculatedByIndex;
    }

    /**
     * Return product base price
     *
     * @return string
     */
    public function getPrice($product)
    {
        if ($product->getPriceType() == self::PRICE_TYPE_FIXED) {
            return $product->getData('price');
        } else {
            return 0;
        }
    }

    /**
     * Get product final price
     *
     * @param   double $qty
     * @param   Mage_Catalog_Model_Product $product
     * @return  double
     */
    public function getFinalPrice($qty=null, $product)
    {
        if (is_null($qty) && !is_null($product->getCalculatedFinalPrice())) {
            return $product->getCalculatedFinalPrice();
        }

        $finalPrice = $product->getPrice();

        /**
         * Just product with fixed price calculation has price
         */
        if ($finalPrice) {
            $tierPrice      = $this->_applyTierPrice($product, $qty, $finalPrice);
            $specialPrice   = $this->_applySpecialPrice($product, $finalPrice);
            //echo $finalPrice.' --- '.$tierPrice.' --- '.$specialPrice; exit;
            $finalPrice     = min(array($tierPrice, $specialPrice));

            $product->setFinalPrice($finalPrice);
            Mage::dispatchEvent('catalog_product_get_final_price', array('product'=>$product));
            $finalPrice = $product->getData('final_price');
        }
        $basePrice = $finalPrice;

        if ($product->hasCustomOptions()) {
            $customOption = $product->getCustomOption('bundle_selection_ids');
            $selectionIds = unserialize($customOption->getValue());
            $selections = $product->getTypeInstance(true)->getSelectionsByIds($selectionIds, $product);
            $selections->addTierPriceData();
            Mage::dispatchEvent('prepare_catalog_product_collection_prices', array(
                'collection'    => $selections,
                'store_id'      => $product->getStoreId(),
            ));
            foreach ($selections->getItems() as $selection) {
                if ($selection->isSalable()) {
                    $selectionQty = $product->getCustomOption('selection_qty_' . $selection->getSelectionId());
                    if ($selectionQty) {
                        $finalPrice += $this->getSelectionFinalPrice($product, $selection, $qty,
                            $selectionQty->getValue());
                    }
                }
            }
        }

        $finalPrice = $finalPrice + $this->_applyOptionsPrice($product, $qty, $basePrice) - $basePrice;
        $product->setFinalPrice($finalPrice);

        return max(0, $product->getData('final_price'));
    }

    public function getChildFinalPrice($product, $productQty, $childProduct, $childProductQty)
    {
        return $this->getSelectionFinalPrice($product, $childProduct, $productQty, $childProductQty, false);
    }

    /**
     * Retrieve Price
     *
     * @deprecated after 1.10.1.1
     * @see Mage_Bundle_Model_Product_Price::getTotalPrices()
     *
     * @param  Mage_Catalog_Model_Product $product
     * @param  string $which
     * @return decimal|array
     */
    public function getPrices($product, $which = null)
    {
        return $this->getTotalPrices($product, $which);
    }

    /**
     * Retrieve Prices depending on tax
     *
     * @deprecated after 1.10.1.1
     * @see Mage_Bundle_Model_Product_Price::getTotalPrices()
     *
     * @param  Mage_Catalog_Model_Product $product
     * @param  string $which
     * @param  bool|null $includeTax
     * @return decimal|array
     */
    public function getPricesDependingOnTax($product, $which = null, $includeTax = null)
    {
        return $this->getTotalPrices($product, $which, $includeTax);
    }
        
    //infobeans modified for fixed/ percent price  16 feb 2015
    /**
     * Retrieve Price with take into account tier price
     *
     * @param  Mage_Catalog_Model_Product $product
     * @param  string|null $which
     * @param  bool|null $includeTax
     * @param  bool $takeTierPrice
     * @return decimal|array
     */
	/*************************
	GORILLA 
	- modified to add the ability to pass in customerGroup
	**************************/
    public function getTotalPrices($product, $which = null, $includeTax = null, $takeTierPrice = true, $customerGroup = null)
    {
        //echo $product->getData('min_price').' ---- '.$product->getData('max_price'); exit;
        // check calculated price index
        if ($product->getData('min_price') && $product->getData('max_price')) {
            //echo 'ee<br>';
                $minimalPrice = Mage::helper('tax')->getPrice($product, $product->getData('min_price'), $includeTax);
                $maximalPrice = Mage::helper('tax')->getPrice($product, $product->getData('max_price'), $includeTax);
                $this->_isPricesCalculatedByIndex = true;
        } else {
            //echo 'ss<br>';
            /**
             * Check if product price is fixed
             */
            $finalPrice = $product->getFinalPrice();
            //echo $finalPrice.'<br>'; 
            if ($product->getPriceType() == self::PRICE_TYPE_FIXED) {
                $minimalPrice = $maximalPrice = Mage::helper('tax')->getPrice($product, $finalPrice, $includeTax);
            } else { // PRICE_TYPE_DYNAMIC
                $minimalPrice = $maximalPrice = 0;
            }

            //echo ' --- '.$minimalPrice.' ---- '.$maximalPrice.'<br>'; 
            
            $options = $this->getOptions($product);
            $minPriceFounded = false;

            if ($options) {
                foreach ($options as $option) {
                    /* @var $option Mage_Bundle_Model_Option */
                    $selections = $option->getSelections();
                    if ($selections) {
                        $selectionMinimalPrices = array();
                        $selectionMaximalPrices = array();

                        foreach ($option->getSelections() as $selection) {
                            /* @var $selection Mage_Bundle_Model_Selection */
                            if (!$selection->isSalable()) {
                                /**
                                 * @todo CatalogInventory Show out of stock Products
                                 */
                                continue;
                            }

                            $qty = $selection->getSelectionQty();
                            if ($selection->getSelectionCanChangeQty() && !$option->isMultiSelection()) {
                                $qty = min(1, $qty);
                            }

                            $item = $product->getPriceType() == self::PRICE_TYPE_FIXED ? $product : $selection;

                            $selectionMinimalPrices[] = Mage::helper('tax')->getPrice(
                                $item,
                                $this->getSelectionFinalTotalPrice($product, $selection, 1, $qty, true, $takeTierPrice, $customerGroup),
                                $includeTax,
                                $takeTierPrice
                            );
                            $selectionMaximalPrices[] = Mage::helper('tax')->getPrice(
                                $item,
                                $this->getSelectionFinalTotalPrice($product, $selection, 1, null, true, $takeTierPrice, $customerGroup),
                                $includeTax,
                                $takeTierPrice
                            );
                        }

                        if (count($selectionMinimalPrices)) {
                            $selMinPrice = min($selectionMinimalPrices);
                            if ($option->getRequired()) {
                                $minimalPrice += $selMinPrice;
                                $minPriceFounded = true;
                            } elseif (true !== $minPriceFounded) {
                                $selMinPrice += $minimalPrice;
                                $minPriceFounded = (false === $minPriceFounded)
                                    ? $selMinPrice
                                    : min($minPriceFounded, $selMinPrice);
                            }

                            if ($option->isMultiSelection()) {
                                $maximalPrice += array_sum($selectionMaximalPrices);
                            } else {
                                $maximalPrice += max($selectionMaximalPrices);
                            }
                        }
                    }
                }
            }
            // condition is TRUE when all product options are NOT required
            if (!is_bool($minPriceFounded)) {
                $minimalPrice = $minPriceFounded;
            }

            $customOptions = $product->getOptions();
            if ($product->getPriceType() == self::PRICE_TYPE_FIXED && $customOptions) {
                foreach ($customOptions as $customOption) {
                    /* @var $customOption Mage_Catalog_Model_Product_Option */
                    $values = $customOption->getValues();
                    if ($values) {
                        $prices = array();
                        foreach ($values as $value) {
                            /* @var $value Mage_Catalog_Model_Product_Option_Value */
                            $valuePrice = $value->getPrice(true);

                            $prices[] = $valuePrice;
                        }
                        if (count($prices)) {
                            if ($customOption->getIsRequire()) {
                                $minimalPrice += Mage::helper('tax')->getPrice($product, min($prices), $includeTax);
                            }

                            $multiTypes = array(
                                //Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN,
                                Mage_Catalog_Model_Product_Option::OPTION_TYPE_CHECKBOX,
                                Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE
                            );

                            if (in_array($customOption->getType(), $multiTypes)) {
                                $maximalValue = array_sum($prices);
                            } else {
                                $maximalValue = max($prices);
                            }
                            $maximalPrice += Mage::helper('tax')->getPrice($product, $maximalValue, $includeTax);
                        }
                    } else {
                        $valuePrice = $customOption->getPrice(true);

                        if ($customOption->getIsRequire()) {
                            $minimalPrice += Mage::helper('tax')->getPrice($product, $valuePrice, $includeTax);
                        }
                        $maximalPrice += Mage::helper('tax')->getPrice($product, $valuePrice, $includeTax);
                    }
                }
            }
            $this->_isPricesCalculatedByIndex = false;
        }

        if ($which == 'max') {
            return $maximalPrice;
        } else if ($which == 'min') {
            return $minimalPrice;
        }

        return array($minimalPrice, $maximalPrice);
    }
    
    //infobeans modified for fixed/ percent price  16 feb 2015
    public function getTotalPricesCustom($product, $which = null, $includeTax = null, $takeTierPrice = true, $customerGroup = null)
    {
        //echo $product->getData('min_price').' ---- '.$product->getData('max_price'); exit;
        // check calculated price index
        
            //echo 'ss<br>';
            /**
             * Check if product price is fixed
             */
            $finalPrice = $this->getFinalPrice(null, $product);
            //echo $finalPrice.'<br>'; 
            if ($product->getPriceType() == self::PRICE_TYPE_FIXED) {
                $minimalPrice = $maximalPrice = Mage::helper('tax')->getPrice($product, $finalPrice, $includeTax);
            } else { // PRICE_TYPE_DYNAMIC
                $minimalPrice = $maximalPrice = 0;
            }

            //echo ' --- '.$minimalPrice.' ---- '.$maximalPrice.'<br>'; 
            
            $options = $this->getOptions($product);
            $minPriceFounded = false;

            if ($options) {
                foreach ($options as $option) {
                    /* @var $option Mage_Bundle_Model_Option */
                    $selections = $option->getSelections();
                    if ($selections) {
                        $selectionMinimalPrices = array();
                        $selectionMaximalPrices = array();

                        foreach ($option->getSelections() as $selection) {
                            /* @var $selection Mage_Bundle_Model_Selection */
                            if (!$selection->isSalable()) {
                                /**
                                 * @todo CatalogInventory Show out of stock Products
                                 */
                                continue;
                            }

                            $qty = $selection->getSelectionQty();
                            if ($selection->getSelectionCanChangeQty() && !$option->isMultiSelection()) {
                                $qty = min(1, $qty);
                            }

                            $item = $product->getPriceType() == self::PRICE_TYPE_FIXED ? $product : $selection;

                            $selectionMinimalPrices[] = Mage::helper('tax')->getPrice(
                                $item,
                                $this->getSelectionFinalTotalPrice($product, $selection, 1, $qty, true, $takeTierPrice, $customerGroup),
                                $includeTax,
                                $takeTierPrice
                            );
                            $selectionMaximalPrices[] = Mage::helper('tax')->getPrice(
                                $item,
                                $this->getSelectionFinalTotalPrice($product, $selection, 1, null, true, $takeTierPrice, $customerGroup),
                                $includeTax,
                                $takeTierPrice
                            );
                        }

                        if (count($selectionMinimalPrices)) {
                            $selMinPrice = min($selectionMinimalPrices);
                            if ($option->getRequired()) {
                                $minimalPrice += $selMinPrice;
                                $minPriceFounded = true;
                            } elseif (true !== $minPriceFounded) {
                                $selMinPrice += $minimalPrice;
                                $minPriceFounded = (false === $minPriceFounded)
                                    ? $selMinPrice
                                    : min($minPriceFounded, $selMinPrice);
                            }

                            if ($option->isMultiSelection()) {
                                $maximalPrice += array_sum($selectionMaximalPrices);
                            } else {
                                $maximalPrice += max($selectionMaximalPrices);
                            }
                        }
                    }
                }
            }
            // condition is TRUE when all product options are NOT required
            if (!is_bool($minPriceFounded)) {
                $minimalPrice = $minPriceFounded;
            }

            $customOptions = $product->getOptions();
            if ($product->getPriceType() == self::PRICE_TYPE_FIXED && $customOptions) {
                foreach ($customOptions as $customOption) {
                    /* @var $customOption Mage_Catalog_Model_Product_Option */
                    $values = $customOption->getValues();
                    if ($values) {
                        $prices = array();
                        foreach ($values as $value) {
                            /* @var $value Mage_Catalog_Model_Product_Option_Value */
                            $valuePrice = $value->getPrice(true);

                            $prices[] = $valuePrice;
                        }
                        if (count($prices)) {
                            if ($customOption->getIsRequire()) {
                                $minimalPrice += Mage::helper('tax')->getPrice($product, min($prices), $includeTax);
                            }

                            $multiTypes = array(
                                //Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN,
                                Mage_Catalog_Model_Product_Option::OPTION_TYPE_CHECKBOX,
                                Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE
                            );

                            if (in_array($customOption->getType(), $multiTypes)) {
                                $maximalValue = array_sum($prices);
                            } else {
                                $maximalValue = max($prices);
                            }
                            $maximalPrice += Mage::helper('tax')->getPrice($product, $maximalValue, $includeTax);
                        }
                    } else {
                        $valuePrice = $customOption->getPrice(true);

                        if ($customOption->getIsRequire()) {
                            $minimalPrice += Mage::helper('tax')->getPrice($product, $valuePrice, $includeTax);
                        }
                        $maximalPrice += Mage::helper('tax')->getPrice($product, $valuePrice, $includeTax);
                    }
                }
            }
            $this->_isPricesCalculatedByIndex = false;

        if ($which == 'max') {
            return $maximalPrice;
        } else if ($which == 'min') {
            return $minimalPrice;
        }

        return array($minimalPrice, $maximalPrice);
    }

    /**
     * Calculate Minimal price of bundle (counting all required options)
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return decimal
     */
    public function getMinimalPrice($product)
    {
        return $this->getPricesTierPrice($product, 'min');
    }

    /**
     * Calculate maximal price of bundle
     *
     * @param Mage_Catalog_Model_Product $product
     * @return decimal
     */
    public function getMaximalPrice($product)
    {
        return $this->getPricesTierPrice($product, 'max');
    }

    /**
     * Get Options with attached Selections collection
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Bundle_Model_Mysql4_Option_Collection
     */
    public function getOptions($product)
    {
        $product->getTypeInstance(true)
            ->setStoreFilter($product->getStoreId(), $product);

        $optionCollection = $product->getTypeInstance(true)
            ->getOptionsCollection($product);

        $selectionCollection = $product->getTypeInstance(true)
            ->getSelectionsCollection(
                $product->getTypeInstance(true)->getOptionsIds($product),
                $product
            );

        return $optionCollection->appendSelections($selectionCollection, false, false);
    }

    /**
     * Calculate price of selection
     *
     * @param Mage_Catalog_Model_Product $bundleProduct
     * @param Mage_Catalog_Model_Product $selectionProduct
     * @param decimal $selectionQty
     * @return decimal
     */
    public function getSelectionPrice($bundleProduct, $selectionProduct, $selectionQty = null, $multiplyQty = true)
    {
        if (is_null($selectionQty)) {
            $selectionQty = $selectionProduct->getSelectionQty();
        }

        if ($bundleProduct->getPriceType() == self::PRICE_TYPE_DYNAMIC) {
            if ($multiplyQty) {
                return $selectionProduct->getFinalPrice($selectionQty) * $selectionQty;
            } else {
                return $selectionProduct->getFinalPrice($selectionQty);
            }
        } else {
            if ($selectionProduct->getSelectionPriceType()) { // percent
                return $bundleProduct->getPrice() * ($selectionProduct->getSelectionPriceValue() / 100) * $selectionQty;
            } else {
                return $selectionProduct->getSelectionPriceValue() * $selectionQty;
            }
        }
    }

    /**
     * Calculate selection price for front view (with applied special of bundle)
     *
     * @param Mage_Catalog_Model_Product $bundleProduct
     * @param Mage_Catalog_Model_Product $selectionProduct
     * @param decimal
     * @return decimal
     */
    public function getSelectionPreFinalPrice($bundleProduct, $selectionProduct, $qty = null, $customerGroup = null)
    {
		$tierPrice = $this->_applyTierPrice($bundleProduct, $qty, $this->getSelectionPrice($bundleProduct, $selectionProduct, $qty), $customerGroup);

        return $this->_applySpecialPrice($bundleProduct, $tierPrice);
    }

    /**
     * Calculate final price of selection
     *
     * @deprecated after 1.10.1.1
     * @see Mage_Bundle_Model_Product_Price::getSelectionFinalTotalPrice()
     *
     * @param  Mage_Catalog_Model_Product $bundleProduct
     * @param  Mage_Catalog_Model_Product $selectionProduct
     * @param  decimal $bundleQty
     * @param  decimal $selectionQty
     * @param  bool $multiplyQty
     * @return decimal
     */	
    public function getSelectionFinalPrice($bundleProduct, $selectionProduct, $bundleQty, $selectionQty = null,
       $multiplyQty = true)
    {
        return $this->getSelectionFinalTotalPrice($bundleProduct, $selectionProduct, $bundleQty, $selectionQty,
            $multiplyQty);
    }

    /**
     * Calculate final price of selection
     * with take into account tier price
     *
     * @param  Mage_Catalog_Model_Product $bundleProduct
     * @param  Mage_Catalog_Model_Product $selectionProduct
     * @param  decimal $bundleQty
     * @param  decimal $selectionQty
     * @param  bool $multiplyQty
     * @param  bool $takeTierPrice
     * @return decimal
     */
	/*************************
	GORILLA 
	- modified to add the ability to pass in customerGroup
	**************************/
    public function getSelectionFinalTotalPrice($bundleProduct, $selectionProduct, $bundleQty, $selectionQty,
        $multiplyQty = true, $takeTierPrice = true, $customerGroup = null)
    {
        $selectionPrice = $this->getSelectionPrice($bundleProduct, $selectionProduct, $selectionQty, $multiplyQty);

        // apply bundle special price
        $specialPrice = $this->_applySpecialPrice($bundleProduct, $selectionPrice);

        if ($takeTierPrice) {
            // apply bundle tier price
            $tierPrice = $this->_applyTierPrice($bundleProduct, $bundleQty, $selectionPrice, $customerGroup);
            return min(array($tierPrice, $specialPrice));
        } else {
            return $specialPrice;
        }
    }
    
    
    //infobeans added function to check if the tiers are fixed or percent 16 feb 2015
    public function checkDiscountTypeInformation($_product)
    {

        $attribute = $_product->getResource()->getAttribute('discount_type');
        if($attribute)
        {
            $discount_type = $attribute->getFrontend()->getValue($_product);
        }
        else
        {
            $discount_type = 'Percent';
        }
        
        if($discount_type == 'Fixed')
        {       
            return true;
        }
        else
        {
            return false;
        }
    }

    //infobeans modified for fixed/ percent price  16 feb 2015
    protected function _applySpecialPrice($product, $finalPrice)
    {
        $checkDiscountTypeInformation = $this->checkDiscountTypeInformation($product);
        
        return $this->calculateSpecialPrice($finalPrice, $product->getSpecialPrice(), $product->getSpecialFromDate(),
                        $product->getSpecialToDate(), $product->getStore(), $checkDiscountTypeInformation
        );
    }
    
    //infobeans modified for fixed/ percent price  16 feb 2015
    /**
     * Apply tier price for bundle
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   decimal $qty
     * @param   decimal $finalPrice
     * @return  decimal
     */
	/*************************
	GORILLA 
	- modified to add the ability to pass in customerGroup
	**************************/
    protected function _applyTierPrice($product, $qty, $finalPrice, $customerGroup = null)
    {
        if (is_null($qty)) {
            return $finalPrice;
        }

        $tierPrice  = $product->getTierPrice($qty, $customerGroup);
        //echo $tierPrice; exit;
        
        $checkDiscountTypeInformation = $this->checkDiscountTypeInformation($product);
        
        if (is_numeric($tierPrice) && $tierPrice != false) {
            if($checkDiscountTypeInformation)
            {
                $finalPrice = min($finalPrice, $tierPrice);
            }
            else
            {
                $tierPrice = $finalPrice - ($finalPrice * ($tierPrice / 100));
                $finalPrice = min($finalPrice, $tierPrice);
            }
        }

        //bandaid fix for Ticket#2014102310000152. Round off 1 cent rounding errors
        $diff = $finalPrice - floor($finalPrice);
        if($diff > 0 && $diff < 0.02) {
            $finalPrice = floor($finalPrice);
        }

        return $finalPrice;
    }

    //infobeans modified for fixed/ percent price  16 feb 2015
    /**
     * Get product tier price by qty
     *
     * @param   decimal $qty
     * @param   Mage_Catalog_Model_Product $product
     * @return  decimal
     */
	/*************************
	GORILLA 
	- modified to add the ability to pass in customerGroup
	**************************/
    public function getTierPrice($qty=null, $product, $customerGroup = null)
    {
        $allGroups = Mage_Customer_Model_Group::CUST_GROUP_ALL;
        $prices = $product->getData('tier_price');

        if (is_null($prices)) {
            if ($attribute = $product->getResource()->getAttribute('tier_price')) {
                $attribute->getBackend()->afterLoad($product);
                $prices = $product->getData('tier_price');
            }
        }

        if (is_null($prices) || !is_array($prices)) {
            if (!is_null($qty)) {
                return $product->getPrice();
            }
            return array(array(
                'price'         => $product->getPrice(),
                'website_price' => $product->getPrice(),
                'price_qty'     => 1,
                'cust_group'    => $allGroups
            ));
        }
		
		if ($customerGroup == null) $custGroup = $this->_getCustomerGroupId($product);
		else $custGroup = $customerGroup;
                $checkDiscountTypeInformation = $this->checkDiscountTypeInformation($product);
        if ($qty) {
            $prevQty = 1;
            $prevPrice = $checkDiscountTypeInformation ? $product->getPrice() : 0;
            $prevGroup = $allGroups;

            foreach ($prices as $price) {
                if ($price['cust_group']!=$custGroup && $price['cust_group']!=$allGroups) {
                    // tier not for current customer group nor is for all groups
                    continue;
                }
                if ($qty < $price['price_qty']) {
                    // tier is higher than product qty
                    continue;
                }
                if ($price['price_qty'] < $prevQty) {
                    // higher tier qty already found
                    continue;
                }
                if ($price['price_qty'] == $prevQty && $prevGroup != $allGroups && $price['cust_group'] == $allGroups) {
                    // found tier qty is same as current tier qty but current tier group is ALL_GROUPS
                    continue;
                }
                if($checkDiscountTypeInformation)
                {
                    if ($price['website_price'] < $prevPrice) {
                        $prevPrice  = $price['website_price'];
                        $prevQty    = $price['price_qty'];
                        $prevGroup  = $price['cust_group'];
                    }
                }
                else
                {
                    if ($price['website_price'] > $prevPrice) {
                        $prevPrice  = $price['website_price'];
                        $prevQty    = $price['price_qty'];
                        $prevGroup  = $price['cust_group'];
                    }
                }
            }

            return $prevPrice;
        } else {
            $qtyCache = array();
            foreach ($prices as $i => $price) {
                if ($price['cust_group'] != $custGroup && $price['cust_group'] != $allGroups) {
                    unset($prices[$i]);
                } else if (isset($qtyCache[$price['price_qty']])) {
                    $j = $qtyCache[$price['price_qty']];
                    if ($prices[$j]['website_price'] < $price['website_price']) {
                        unset($prices[$j]);
                        $qtyCache[$price['price_qty']] = $i;
                    } else {
                        unset($prices[$i]);
                    }
                } else {
                    $qtyCache[$price['price_qty']] = $i;
                }
            }
        }

        return ($prices) ? $prices : array();
    }	

	/*************************
	GORILLA 	
	**************************/
    public function getTierPrices($product, $customerGroup = null)
    {
        $prices = $product->getData('tier_price');

        if (is_null($prices)) {
            if ($attribute = $product->getResource()->getAttribute('tier_price')) {
                $attribute->getBackend()->afterLoad($product);
                $prices = $product->getData('tier_price');
            }
        }

		if ($customerGroup != null) {
			$filteredPrices = array();
			foreach($prices as $price) {
				if ($price['cust_group'] == $customerGroup) {
					$filteredPrices[] = $price;
				}
			}
		} else {
			$filteredPrices = $prices;
		}
		
		return $filteredPrices;
	}

    //infobeans modified for fixed/ percent price  16 feb 2015    
    /**
     * Calculate product price based on special price data and price rules
     *
     * @param   float $basePrice
     * @param   float $specialPrice
     * @param   string $specialPriceFrom
     * @param   string $specialPriceTo
     * @param   float|null|false $rulePrice
     * @param   mixed $wId
     * @param   mixed $gId
     * @param   null|int $productId
     * @return  float
     */
    public static function calculatePrice($basePrice, $specialPrice, $specialPriceFrom, $specialPriceTo,
        $rulePrice = false, $wId = null, $gId = null, $productId = null)
    {
        $product =  Mage::getModel('catalog/product')->load($productId);                 
        $checkDiscountTypeInformation = $this->checkDiscountTypeInformation($product);
        
        $resource = Mage::getResourceSingleton('bundle/bundle');
        $selectionResource = Mage::getResourceSingleton('bundle/selection');
        $productPriceTypeId = Mage::getSingleton('eav/entity_attribute')->getIdByCode(
            Mage_Catalog_Model_Product::ENTITY,
            'price_type'
        );

        if ($wId instanceof Mage_Core_Model_Store) {
            $store = $wId->getId();
            $wId = $wId->getWebsiteId();
        } else {
            $store = Mage::app()->getStore($wId)->getId();
            $wId = Mage::app()->getStore($wId)->getWebsiteId();
        }

        if (!$gId) {
            $gId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        } else if ($gId instanceof Mage_Customer_Model_Group) {
            $gId = $gId->getId();
        }

        if (!isset(self::$attributeCache[$productId]['price_type'])) {
            $attributes = $resource->getAttributeData($productId, $productPriceTypeId, $store);
            self::$attributeCache[$productId]['price_type'] = $attributes;
        } else {
            $attributes = self::$attributeCache[$productId]['price_type'];
        }

        $options = array(0);
        $results = $resource->getSelectionsData($productId);

        if (!$attributes || !$attributes[0]['value']) { //dynamic
            foreach ($results as $result) {
                if (!$result['product_id']) {
                    continue;
                }

                if ($result['selection_can_change_qty'] && $result['type'] != 'multi'
                    && $result['type'] != 'checkbox'
                ) {
                    $qty = 1;
                } else {
                    $qty = $result['selection_qty'];
                }

                $result['final_price'] = $selectionResource->getPriceFromIndex($result['product_id'], $qty, $store,
                    $gId);

                $selectionPrice = $result['final_price']*$qty;

                if (isset($options[$result['option_id']])) {
                    $options[$result['option_id']] = min($options[$result['option_id']], $selectionPrice);
                } else {
                    $options[$result['option_id']] = $selectionPrice;
                }
            }
            $basePrice = array_sum($options);
        } else {
            foreach ($results as $result) {
                if (!$result['product_id']) {
                    continue;
                }
                if ($result['selection_price_type']) {
                    $selectionPrice = $basePrice*$result['selection_price_value']/100;
                } else {
                    $selectionPrice = $result['selection_price_value'];
                }

                if ($result['selection_can_change_qty'] && $result['type'] != 'multi'
                    && $result['type'] != 'checkbox'
                ) {
                    $qty = 1;
                } else {
                    $qty = $result['selection_qty'];
                }

                $selectionPrice = $selectionPrice*$qty;

                if (isset($options[$result['option_id']])) {
                    $options[$result['option_id']] = min($options[$result['option_id']], $selectionPrice);
                } else {
                    $options[$result['option_id']] = $selectionPrice;
                }
            }

            $basePrice = $basePrice + array_sum($options);
        }

        $finalPrice = self::calculateSpecialPrice($basePrice, $specialPrice, $specialPriceFrom, $specialPriceTo,
            $store, $checkDiscountTypeInformation);

        /**
         * adding customer defined options price
         */
        $customOptions = Mage::getResourceSingleton('catalog/product_option_collection')->reset();
        $customOptions->addFieldToFilter('is_require', '1')
            ->addProductToFilter($productId)
            ->addPriceToResult($store, 'price')
            ->addValuesToResult();

        foreach ($customOptions as $customOption) {
            if ($values = $customOption->getValues()) {
                $prices = array();
                foreach ($values as $value) {
                    $prices[] = $value->getPrice();
                }
                if (count($prices)) {
                    $finalPrice += min($prices);
                }
            } else {
                $finalPrice += $customOption->getPrice();
            }
        }

        if ($rulePrice === false) {
            $rulePrice = Mage::getResourceModel('catalogrule/rule')
                ->getRulePrice(Mage::app()->getLocale()->storeTimeStamp($store), $wId, $gId, $productId);
        }

        if ($rulePrice !== null && $rulePrice !== false) {
            $finalPrice = min($finalPrice, $rulePrice);
        }

        $finalPrice = max($finalPrice, 0);

        return $finalPrice;
    }

    //infobeans modified for fixed/ percent price  16 feb 2015
    /**
     * Calculate and apply special price
     *
     * @param float $finalPrice
     * @param float $specialPrice
     * @param string $specialPriceFrom
     * @param string $specialPriceTo
     * @param mixed $store
     * @return float
     */
    public static function calculateSpecialPrice($finalPrice, $specialPrice, $specialPriceFrom, $specialPriceTo,
         $store = null, $checkDiscountTypeInformation = false)
    {
        if (!is_null($specialPrice) && $specialPrice != false) {
            if (Mage::app()->getLocale()->isStoreDateInInterval($store, $specialPriceFrom, $specialPriceTo)) {
                
                if($checkDiscountTypeInformation)
                {
                    $finalPrice     = min($finalPrice, $specialPrice);
                }
                else
                {
                    $specialPrice   = Mage::app()->getStore()->roundPrice($finalPrice * $specialPrice / 100);
                    $finalPrice     = min($finalPrice, $specialPrice);
                }
            }
        }

        return $finalPrice;
    }

    /**
     * Check is tier price value fixed or percent of original price
     *
     * @return bool
     */
    public function isTierPriceFixed()
    {
        return false;
    }
    
    
}
