<?php
class ICC_TableRateMixed_Model_Carrier_Tablerate extends Mage_Shipping_Model_Carrier_Tablerate
{
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // exclude Virtual products price from Package value if pre-configured
        if (!$this->getConfigFlag('include_virtual_price') && $request->getAllItems()) {
            $groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                /*
                 * Changes for the individual product shipping in Fixed Priced bundle
                 */
                $finalPrice = 0;
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($item->getProductType() == 'bundle' && ($item->getProduct()->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED)) {
                            $child->getProduct()->setCustomerGroupId($groupId);
                            $price = $child->getProduct()->getPriceModel()->getFinalPrice($child->getQty(), $child->getProduct()) * $item->getQty();
                            if (!$child->getProduct()->isVirtual()) {
                               $finalPrice = $finalPrice + $price;
                            }
                        } else {
                            if ($child->getProduct()->isVirtual()) {
                                $request->setPackageValue($request->getPackageValue() - $child->getBaseRowTotal());
                            }
                        }
                    }
                    if ($item->getProductType() == 'bundle' && ($item->getProduct()->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED)) {
                        $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
                        $request->setPackagePhysicalValue($request->getPackagePhysicalValue() - $item->getBaseRowTotal());

                        $request->setPackageValue($request->getPackageValue() + $finalPrice);
                        $request->setPackagePhysicalValue($request->getPackagePhysicalValue() + $finalPrice);
                    }
                } elseif ($item->getProduct()->isVirtual()) {
                    $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
                }
            }
        }

        // Free shipping by qty
        $freeQty = 0;
        $freePackageValue = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeQty += $item->getQty() * ($child->getQty() - (is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0));
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeQty += ($item->getQty() - (is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0));
                    $freePackageValue += $item->getRowTotal();
                }
            }
        }
        if ($freePackageValue) {
            $request->setPackageValue($request->getPackageValue() - $freePackageValue);
        }

        if (!$request->getConditionName()) {
            $request->setConditionName($this->getConfigData('condition_name') ? $this->getConfigData('condition_name') : $this->_default_condition_name);
        }

        // Package weight and qty free shipping
        $oldWeight = $request->getPackageWeight();
        $oldQty = $request->getPackageQty();

        $request->setPackageWeight($request->getFreeMethodWeight());
        $request->setPackageQty($oldQty - $freeQty);

        $result = Mage::getModel('shipping/rate_result');
        $rate = $this->getRate($request);

        $request->setPackageWeight($oldWeight);
        $request->setPackageQty($oldQty);

        if (!empty($rate) && $rate['price'] >= 0) {
            $method = Mage::getModel('shipping/rate_result_method');

            $method->setCarrier('tablerate');
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod('bestway');
            $method->setMethodTitle($this->getConfigData('name'));

            if ($request->getFreeShipping() === true || ($request->getPackageQty() == $freeQty)) {
                $shippingPrice = 0;
            } elseif(isset($rate['calculation_type']) && $rate['calculation_type'] == ICC_TableRateMixed_Model_Resource_Carrier_Tablerate::CALCULATION_TYPE_PERCENTAGE ) {
                $shippingPrice = $this->_getPercentPriceOfCartSubtotal($rate['price'], $request);
            }else{
                $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);
            }

            $method->setPrice($shippingPrice);
            $method->setCost($rate['cost']);

            $result->append($method);
        } elseif (empty($rate) && $request->getFreeShipping() === true) {
            /**
             * was applied promotion rule for whole cart
             * other shipping methods could be switched off at all
             * we must show table rate method with 0$ price, if grand_total more, than min table condition_value
             * free setPackageWeight() has already was taken into account
             */
            $request->setPackageValue($freePackageValue);
            $request->setPackageQty($freeQty);
            $rate = $this->getRate($request);
            if (!empty($rate) && $rate['price'] >= 0) {
                $method = Mage::getModel('shipping/rate_result_method');

                $method->setCarrier('tablerate');
                $method->setCarrierTitle($this->getConfigData('title'));

                $method->setMethod('bestway');
                $method->setMethodTitle($this->getConfigData('name'));

                $method->setPrice(0);
                $method->setCost(0);

                $result->append($method);
            }
        }

        return $result;
    }

    protected function _getPercentPriceOfCartSubtotal($cost, $request){
        
        $international_extra_cost = Mage::helper('icc_tableratemixed')->getFixedInternationalShippingAmount();
        $subTotal = ($request->getPackagePhysicalValue()) ? $request->getPackagePhysicalValue() : $request->getPackageValueWithDiscount();
        $return_val = $subTotal * ($cost / 100);

        if($request->getDestCountryId() != 'US' && $request->getDestCountryId() != 'CA' && $request->getDestCountryId() != 'MX')
        {
            $return_val += $international_extra_cost;
        }
        
        return $return_val;
    }
}