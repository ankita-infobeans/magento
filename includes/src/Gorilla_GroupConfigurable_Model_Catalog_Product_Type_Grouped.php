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
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Grouped product type implementation
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Gorilla_GroupConfigurable_Model_Catalog_Product_Type_Grouped extends Mage_Catalog_Model_Product_Type_Grouped 
{
    

    /**
     * Retrieve array of associated products
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getAssociatedProducts($product = null)
    {
        if (!$this->getProduct($product)->hasData($this->_keyAssociatedProducts)) {
            $associatedProducts = array();

            if (!Mage::app()->getStore()->isAdmin()) {
                $this->setSaleableStatus($product);
            }

            $collection = $this->getAssociatedProductCollection($product)
                ->addAttributeToSelect('*')
                ->addAttributeToSelect('image')
                //->addFilterByRequiredOptions()
                ->setPositionOrder()
                ->addStoreFilter($this->getStoreFilter($product))
                ->addAttributeToFilter('status', array('in' => $this->getStatusFilters($product)));

            foreach ($collection as $item) {
                $associatedProducts[] = $item;
            }

            $this->getProduct($product)->setData($this->_keyAssociatedProducts, $associatedProducts);
        }
        return $this->getProduct($product)->getData($this->_keyAssociatedProducts);
    }

    /**
     * Add status filter to collection
     *
     * @param  int $status
     * @param  Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Product_Type_Grouped
     */
    public function addStatusFilter($status, $product = null)
    {
        $statusFilters = $this->getProduct($product)->getData($this->_keyStatusFilters);
        if (!is_array($statusFilters)) {
            $statusFilters = array();
        }

        $statusFilters[] = $status;
        $this->getProduct($product)->setData($this->_keyStatusFilters, $statusFilters);

        return $this;
    }

    /**
     * Set only saleable filter
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Product_Type_Grouped
     */
    public function setSaleableStatus($product = null)
    {
        $this->getProduct($product)->setData($this->_keyStatusFilters,
            Mage::getSingleton('catalog/product_status')->getSaleableStatusIds());
        return $this;
    }

    /**
     * Return all assigned status filters
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getStatusFilters($product = null)
    {
        if (!$this->getProduct($product)->hasData($this->_keyStatusFilters)) {
            return array(
                Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
                Mage_Catalog_Model_Product_Status::STATUS_DISABLED
            );
        }
        return $this->getProduct($product)->getData($this->_keyStatusFilters);
    }

    /**
     * Retrieve related products identifiers
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getAssociatedProductIds($product = null)
    {
        if (!$this->getProduct($product)->hasData($this->_keyAssociatedProductIds)) {
            $associatedProductIds = array();
            foreach ($this->getAssociatedProducts($product) as $item) {
                $associatedProductIds[] = $item->getId();
            }
            $this->getProduct($product)->setData($this->_keyAssociatedProductIds, $associatedProductIds);
        }
        return $this->getProduct($product)->getData($this->_keyAssociatedProductIds);
    }

    /**
     * Retrieve collection of associated products
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection
     */
    public function getAssociatedProductCollection($product = null)
    {
        $collection = $this->getProduct($product)->getLinkInstance()->useGroupedLinks()
            ->getProductCollection()
            ->setFlag('require_stock_items', true)
            ->setFlag('product_children', true)
            ->setIsStrongMode();
        $collection->setProduct($this->getProduct($product));
        return $collection;
    }

    /**
     * Check is product available for sale
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function isSalable($product = null)
    {
        $salable = parent::isSalable($product);
        if (!is_null($salable)) {
            return $salable;
        }

        $salable = false;
        foreach ($this->getAssociatedProducts($product) as $associatedProduct) {
            $salable = $salable || $associatedProduct->isSalable();
        }
        return $salable;
    }

    /**
     * Save type related data
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Product_Type_Grouped
     */
    public function save($product = null)
    {
        parent::save($product);
        $this->getProduct($product)->getLinkInstance()->saveGroupedLinks($this->getProduct($product));
        return $this;
    }

    /**
     * Prepare product and its configuration to be added to some products list.
     * Perform standard preparation process and add logic specific to Grouped product type.
     *
     * @param Varien_Object $buyRequest
     * @param Mage_Catalog_Model_Product $product
     * @param string $processMode
     * @return array|string
     */
    protected function _prepareProduct(Varien_Object $buyRequest, $product, $processMode)
    {
        $product = $this->getProduct($product);
        $productsInfo = $buyRequest->getSuperGroup();
        $isStrictProcessMode = $this->_isStrictProcessMode($processMode);

        if (!$isStrictProcessMode || (!empty($productsInfo) && is_array($productsInfo))) {
            $products = array();
            $associatedProductsInfo = array();
            $associatedProducts = $this->getAssociatedProducts($product);
            if ($associatedProducts || !$isStrictProcessMode) {
                foreach ($associatedProducts as $subProduct) {
                    $subProductId = $subProduct->getId();
                    if(isset($productsInfo[$subProductId])) {
                        $qty = $productsInfo[$subProductId];
                        if (!empty($qty) && is_numeric($qty)) {

                            $_result = $subProduct->getTypeInstance(true)
                                ->_prepareProduct($buyRequest, $subProduct, $processMode);
                            if (is_string($_result) && !is_array($_result)) {
                                return $_result;
                            }

                            if (!isset($_result[0])) {
                                return Mage::helper('checkout')->__('Cannot process the item.');
                            }

                            if ($isStrictProcessMode) {
                                $_result[0]->setCartQty($qty);
                                $_result[0]->addCustomOption('product_type', self::TYPE_CODE, $product);
                                $_result[0]->addCustomOption('info_buyRequest',
                                    serialize(array(
                                        'super_product_config' => array(
                                            'product_type'  => self::TYPE_CODE,
                                            'product_id'    => $product->getId()
                                        )
                                    ))
                                );
                                $products[] = $_result[0];
                            } else {
                                $associatedProductsInfo[] = array($subProductId => $qty);
                                $product->addCustomOption('associated_product_' . $subProductId, $qty);
                            }
                        }
                    }
                }
            }

            if (!$isStrictProcessMode || count($associatedProductsInfo)) {
                $product->addCustomOption('product_type', self::TYPE_CODE, $product);
                $product->addCustomOption('info_buyRequest',serialize($buyRequest));

                $products[] = $product;
            }

            if (count($products)) {
                return $products;
            }
        }

        return Mage::helper('catalog')->__('Please specify the quantity of product(s).');
    }

    /**
     * Retrieve products divided into groups required to purchase
     * At least one product in each group has to be purchased
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getProductsToPurchaseByReqGroups($product = null)
    {
        $product = $this->getProduct($product);
        return array($this->getAssociatedProducts($product));
    }

    /**
     * Prepare selected qty for grouped product's options
     *
     * @param  Mage_Catalog_Model_Product $product
     * @param  Varien_Object $buyRequest
     * @return array
     */
    public function processBuyRequest($product, $buyRequest)
    {
        $superGroup = $buyRequest->getSuperGroup();
        $superGroup = (is_array($superGroup)) ? array_filter($superGroup, 'intval') : array();

        $options = array('super_group' => $superGroup);

        return $options;
    }
}
