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

/**
 * Catalog product model
 *
 * @method Mage_Catalog_Model_Resource_Product getResource()
 * @method Mage_Catalog_Model_Resource_Product _getResource()
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Gorilla_Catalog_Model_Product extends OrganicInternet_SimpleConfigurableProducts_Catalog_Model_Product
{
    //overidden to pass in customerGroup
    public function getFormatedTierPrice($qty=null, $customerGroup = null)
    {
        return $this->getPriceModel()->getFormatedTierPrice($qty, $this, $customerGroup);
    }

    //overidden to pass in customerGroup
    public function getTierPrice($qty=null, $customerGroup = null)
    {
        return $this->getPriceModel()->getTierPrice($qty, $this, $customerGroup);
    }

    //overridden to pull member price if set
    public function getMinimalPrice()
    {
	if ($this->getTypeId() == 'grouped' || $this->getTypeId() == 'configurable') {
	
	    $pids = $this->getTypeInstance()->getChildrenIds($this->getId());

	    $price = 0;
	    foreach ($pids as $ids) {
		foreach ($ids as $id) {
		    $prod = Mage::getModel('catalog/product')->load($id);
		    $newPrice = $this->_getMinimalPrice($prod->getPrice(), $prod->getData('tier_price'));
		    if ($price == 0 || $newPrice < $price) $price = $newPrice;
		}
	    }
	    return $price;
	}elseif($this->getTypeId() == 'downloadable'){
			$price = 0;
			$prod = Mage::getModel('catalog/product')->load($this->getId());
			$newPrice = $this->_getMinimalPrice($prod->getPrice(), $prod->getData('tier_price'));
		    if ($price == 0 || $newPrice < $price) $price = $newPrice;	
		return $price;	    
	} else {

		$price = 0;
		if ($this->getTypeId() == 'simple') {
			$prod = Mage::getModel('catalog/product')->load($this->getId());
			$newPrice = $this->_getMinimalPrice($prod->getPrice(), $prod->getData('tier_price'));
		    if ($price == 0 || $newPrice < $price) $price = $newPrice;	 
		}
		$memberPrice = max($this->_getData('member_price'), 0);
		if ($memberPrice == 0) {
			$memberPrice = $price;
		}

	    $regularPrice = max($this->_getData('minimal_price'), 0);
	    if ($memberPrice != 0 && $regularPrice != 0) {
		return min($memberPrice, $regularPrice);
	    } else if ($memberPrice != 0) {
		return $memberPrice;
	    } else {
		return $regularPrice;
	    }
	}
    }
    
    protected function _getMinimalPrice($price, $tierPrices)
    {
	if (sizeof($tierPrices)) {
	    foreach($tierPrices as $tier) {
		if ($tier['price_qty'] == 1 && $tier['cust_group'] == 2) {
		    if ($tier['website_price'] != 0 && $tier['website_price'] < $price) return $tier['website_price'];
		    break;
		}		
	    }	    
	}
	return $price;
    }
    
}
