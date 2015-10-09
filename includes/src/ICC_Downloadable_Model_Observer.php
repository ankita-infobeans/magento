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
 * @package     Mage_Downloadable
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Downloadable Products Observer
 *
 * @category    Mage
 * @package     Mage_Downloadable
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ICC_Downloadable_Model_Observer extends Mage_Downloadable_Model_Observer
{
    /**
     * Set status of link
     *
     * @param Varien_Object $observer
     * @return Mage_Downloadable_Model_Observer
     */
    public function setLinkStatus($observer)
    {
    	$order = $observer->getEvent()->getOrder();

        if (!$order->getId()) {
            //order not saved in the database
            return $this;
        }

        /* @var $order Mage_Sales_Model_Order */
        $status = '';
        $orderItemsIds = array();
        $orderItemStatusToEnable = Mage::getStoreConfig(
            Mage_Downloadable_Model_Link_Purchased_Item::XML_PATH_ORDER_ITEM_STATUS, $order->getStoreId()
        );

        if ($order->getState() == Mage_Sales_Model_Order::STATE_HOLDED) {
            $status = Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PENDING;
        } elseif ($order->isCanceled() || $order->getState() == Mage_Sales_Model_Order::STATE_CLOSED) {
            $status = Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_EXPIRED;
        } elseif ($order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            $status = Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PENDING_PAYMENT;
        } elseif ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
            $status = Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PAYMENT_REVIEW;
        } else {
            $availableStatuses = array($orderItemStatusToEnable, Mage_Sales_Model_Order_Item::STATUS_INVOICED);
            foreach ($order->getAllItems() as $item) {
                if ($item->getProductType() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE
                    || $item->getRealProductType() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE)
                {
                    if (in_array($item->getStatusId(), $availableStatuses)) {
                        $orderItemsIds[] = $item->getId();
                    }
                }
            }
            if ($orderItemsIds) {
                $status = Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_AVAILABLE;
            }
        }
        if (!$orderItemsIds && $status) {
            foreach ($order->getAllItems() as $item) {
                if ($item->getProductType() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE
                    || $item->getRealProductType() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE)
                {
                    $orderItemsIds[] = $item->getId();
                }
            }
        }

        if ($orderItemsIds) {
            $linkPurchased = Mage::getResourceModel('downloadable/link_purchased_item_collection')
                ->addFieldToFilter('order_item_id', array('in'=>$orderItemsIds));
            foreach ($linkPurchased as $link) {
                if ($link->getStatus() != Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_EXPIRED
                    && $link->getStatus() != ICC_Ecodes_Helper_Downloadable::LINK_STATUS_REFUNDED)
                {
                    $link->setStatus($status)
                        ->save();
                }
            }
        }
        return $this;
    }
}
