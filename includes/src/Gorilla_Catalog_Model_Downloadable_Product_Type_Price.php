<?php
/**
* Overridden to show the member price and savings in downloadable products detail page
* 
*/

class Gorilla_Catalog_Model_Downloadable_Product_Type_Price extends Mage_Downloadable_Model_Product_Price {

	//overiddent to pass in customerGroup
    public function getFormatedTierPrice($qty=null, $product, $customerGroup = null)
    {
        $price = $product->getTierPrice($qty, $customerGroup);
        if (is_array($price)) {
            foreach ($price as $index => $value) {
                $price[$index]['formated_price'] = Mage::app()->getStore()->convertPrice($price[$index]['website_price'], true);
            }
        }
        else {
            $price = Mage::app()->getStore()->formatPrice($price);
        }

        return $price;
    }

	//overiddent to pass in and process customerGroup
	public function getTierPrice($qty = null, $product, $customerGroup = null) 
	{
        $allGroups = Mage_Customer_Model_Group::CUST_GROUP_ALL;
        $prices = $product->getData('tier_price');

        if (is_null($prices)) {
            $attribute = $product->getResource()->getAttribute('tier_price');
            if ($attribute) {
                $attribute->getBackend()->afterLoad($product);
                $prices = $product->getData('tier_price');
            }
        }

        if (is_null($prices) || !is_array($prices) || sizeof($prices) == 0) {
            if (!is_null($qty)) {
                return $product->getPrice();
            }
            return array(array(
                'price'         => $product->getPrice(),
                'website_price' => $product->getPrice(),
                'price_qty'     => 1,
                'cust_group'    => $allGroups,
            ));
        }
		
		//CUSTOM - override customer group if value is passed in
		if ($customerGroup != null) {
			if ($customerGroup == Gorilla_Catalog_Helper_Data::MEMBER_GROUP) {
				$custGroup = Gorilla_Catalog_Helper_Data::MEMBER_GROUP;
			} else {
				$custGroup = Gorilla_Catalog_Helper_Data::ALL_GROUPS;
			}
		} else {
			$custGroup = $this->_getCustomerGroupId($product);
		}
		//END

        if ($qty) {
            $prevQty = 1;
            $prevPrice = $product->getPrice();
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
                if ($price['website_price'] < $prevPrice) {
                    $prevPrice  = $price['website_price'];
                    $prevQty    = $price['price_qty'];
                    $prevGroup  = $price['cust_group'];
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
                    if ($prices[$j]['website_price'] > $price['website_price']) {
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
}
