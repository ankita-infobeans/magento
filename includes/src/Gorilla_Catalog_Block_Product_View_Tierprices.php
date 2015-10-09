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
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Gorilla_Catalog_Block_Product_View_Tierprices extends Mage_Catalog_Block_Product_View {

    public function getCustomerGroup()
    {
        if (!$this->hasData('customer_group')) {
            $this->setData('customer_group', Gorilla_Catalog_Helper_Data::ALL_GROUPS);
        }
        return $this->getData('customer_group');
    }
    public function getCustomerGroup2()
    {
        if (!$this->hasData('customer_group2')) {
            $this->setData('customer_group2', null);
        }
        return $this->getData('customer_group2');
    }

    public function getLabel()
    {
        if (!$this->hasData('label')) {
            $this->setData('label', '');
        }
        return $this->getData('label');
    }

    public function getSelected()
    {
        if (!$this->hasData('selected')) {
            $this->setData('selected', false);
        }
        return $this->getData('selected');
    }

    public function getTierPrices($product = null, $customerGroup = null)
    {
        if (is_null($product)) {
            $product = $this->getProduct();
        }

		$customerGroup = $this->getCustomerGroup();

        $prices  = $product->getFormatedTierPrice(null, $customerGroup);

        $res = array();
        if (is_array($prices)) {
            foreach ($prices as $price) {
                $price['price_qty'] = $price['price_qty']*1;
                if ($product->getPrice() != $product->getFinalPrice()) {
                    $_productPrice = $product->getFinalPrice();
                } else {
                    $_productPrice = $product->getPrice();
                }
                //if ($price['price'] < $_productPrice) {
                    if ($_productPrice > 0) {
                        $price['savePercent'] = ceil(100 - ((100 / $_productPrice) * $price['price']));
		    } else {
		        $price['savePercent'] = 0;
		    }

                    $tierPrice = Mage::app()->getStore()->convertPrice(
                        Mage::helper('tax')->getPrice($product, $price['website_price'])
                    );
                    $price['formated_price'] = Mage::app()->getStore()->formatPrice($tierPrice);
                    $price['formated_price_incl_tax'] = Mage::app()->getStore()->formatPrice(
                        Mage::app()->getStore()->convertPrice(
                            Mage::helper('tax')->getPrice($product, $price['website_price'], true)
                        )
                    );

                    if (Mage::helper('catalog')->canApplyMsrp($product)) {
                        $oldPrice = $product->getFinalPrice();
                        $product->setPriceCalculation(false);
                        $product->setPrice($tierPrice);
                        $product->setFinalPrice($tierPrice);

                        $this->getPriceHtml($product);
                        $product->setPriceCalculation(true);

                        $price['real_price_html'] = $product->getRealPriceHtml();
                        $product->setFinalPrice($oldPrice);
                    }

                    $res[] = $price;
                //}
            }
        }

        return $res;
    }

    public function getTierPriceDifference($product = null)
    {
        if (is_null($product)) {
            $product = $this->getProduct();
        }

		$customerGroup = $this->getCustomerGroup();
		$customerGroup2 = $this->getCustomerGroup2();

		if (!$customerGroup2) return null;

        $prices  = $product->getFormatedTierPrice(null, $customerGroup);
        $prices2  = $product->getFormatedTierPrice(null, $customerGroup2);

        $singlePrice = $this->findPriceByQty($prices, 1);
		$singlePrice2 = $this->findPriceByQty($prices2, 1);

		if ($singlePrice && $singlePrice2) 
			return $singlePrice - $singlePrice2;

		return null;
	}

	private function findPriceByQty($priceAry, $qty) {
        if (is_array($priceAry)) {
            foreach ($priceAry as $price) {
                $price['price_qty'] = $price['price_qty']*1;
				if ($price['price_qty'] = $qty) return $price['price'];
			}
		}
		return null;
	}
}