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
 * @package     Mage_GoogleAnalytics
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * GoogleAnalitics Page Block
 *
 * @category   Mage
 * @package    Mage_GoogleAnalytics
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class ICC_GoogleAnalytics_Block_Ga extends Mage_GoogleAnalytics_Block_Ga
{
    /**
     * Render information about specified orders and their items
     *
     * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApiEcommerce.html#_gat.GA_Tracker_._addTrans
     * @return string
     */
    protected function _getOrdersTrackingCode()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', array('in' => $orderIds))
        ;
        $result = array();
        foreach ($collection as $order) {
            if ($order->getIsVirtual()) {
                $address = $order->getBillingAddress();
            } else {
                $address = $order->getShippingAddress();
            }
            
            $store = Mage::app()->getStore()->getFrontendName();
            $role  = Mage::getSingleton('customer/group')->load( Mage::getSingleton('customer/session')->getCustomerGroupId() )->getData('customer_group_code');
            $store = $role;
            
            $result[] = sprintf("_gaq.push(['_addTrans', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);",
                $order->getIncrementId(), $store, $order->getBaseGrandTotal(),
                $order->getBaseTaxAmount(), $order->getBaseShippingAmount(),
                $this->jsQuoteEscape($address->getCity()),
                $this->jsQuoteEscape($address->getRegion()),
                $this->jsQuoteEscape($address->getCountry())
            );
            foreach ($order->getAllVisibleItems() as $item) {/* @var $item Mage_Sales_Model_Order_Item */
                $result[] = sprintf("_gaq.push(['_addItem', '%s', '%s', '%s', '%s', '%s', '%s']);",
                    $order->getIncrementId(),
                    $this->jsQuoteEscape($item->getSku()), $this->jsQuoteEscape($item->getName()),
                    $this->jsQuoteEscape( $this->getProductTypeByOrderItem($item) ), // added as part of OTRS Ticket#2012100510000298 — Google Analytics 
                    $item->getBasePrice(), $item->getQtyOrdered()
                );
            }
            $result[] = "_gaq.push(['_trackTrans']);";
        }
        return implode("\n", $result);
    }

    /**
     * Get names of second level categories for ordered item
     *
     * @item Mage_Sales_Model_Order_Item
     * @return string
     */
    public function getProductTypeByOrderItem( Mage_Sales_Model_Order_Item $item ) 
    {
        $ret = null;
        if($item->getProductId() && $_product = Mage::getModel('catalog/product')->load($item->getProductId())) {/* @var $_product Gorilla_Catalog_Model_Product */
            if ($typeId = $_product->getItemType()) {
                $ret = $_product->getResource()->getAttribute("item_type")->getSource()->getOptionText( $typeId );
            }
        }
        return $ret;
    }
}
