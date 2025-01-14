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
class Mage_Downloadable_Model_Observer
{
    const XML_PATH_DISABLE_GUEST_CHECKOUT   = 'catalog/downloadable/disable_guest_checkout';

    /**
     * Prepare product to save
     *
     * @param   Varien_Object $observer
     * @return  Mage_Downloadable_Model_Observer
     */
    public function prepareProductSave($observer)
    {
        $request = $observer->getEvent()->getRequest();
        $product = $observer->getEvent()->getProduct();

        if ($downloadable = $request->getPost('downloadable')) {
            $product->setDownloadableData($downloadable);
        }

        return $this;
    }

    /**
     * Save data from order to purchased links
     *
     * @param Varien_Object $observer
     * @return Mage_Downloadable_Model_Observer
     */
    public function saveDownloadableOrderItem($observer)
    {
        $orderItem = $observer->getEvent()->getItem();
        if (!$orderItem->getId()) {
            //order not saved in the database
            return $this;
        }
        $product = $orderItem->getProduct();
        if ($product && $product->getTypeId() != Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE) {
            return $this;
        }
        if (Mage::getModel('downloadable/link_purchased')->load($orderItem->getId(), 'order_item_id')->getId()) {
            return $this;
        }
        if (!$product) {
            $product = Mage::getModel('catalog/product')
                ->setStoreId($orderItem->getOrder()->getStoreId())
                ->load($orderItem->getProductId());
        }
        if ($product->getTypeId() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE) {
            $links = $product->getTypeInstance(true)->getLinks($product);
            if ($linkIds = $orderItem->getProductOptionByCode('links')) {
                $linkPurchased = Mage::getModel('downloadable/link_purchased');
                Mage::helper('core')->copyFieldset(
                    'downloadable_sales_copy_order',
                    'to_downloadable',
                    $orderItem->getOrder(),
                    $linkPurchased
                );
                Mage::helper('core')->copyFieldset(
                    'downloadable_sales_copy_order_item',
                    'to_downloadable',
                    $orderItem,
                    $linkPurchased
                );
                $linkSectionTitle = (
                $product->getLinksTitle()?
                    $product->getLinksTitle():Mage::getStoreConfig(Mage_Downloadable_Model_Link::XML_PATH_LINKS_TITLE)
                );
                $linkPurchased->setLinkSectionTitle($linkSectionTitle)
                    ->save();
                foreach ($linkIds as $linkId) {
                    if (isset($links[$linkId])) {
                        $linkPurchasedItem = Mage::getModel('downloadable/link_purchased_item')
                            ->setPurchasedId($linkPurchased->getId())
                            ->setOrderItemId($orderItem->getId());

                        Mage::helper('core')->copyFieldset(
                            'downloadable_sales_copy_link',
                            'to_purchased',
                            $links[$linkId],
                            $linkPurchasedItem
                        );
                        $linkHash = strtr(base64_encode(microtime() . $linkPurchased->getId() . $orderItem->getId()
                            . $product->getId()), '+/=', '-_,');
                        $numberOfDownloads = $links[$linkId]->getNumberOfDownloads()*$orderItem->getQtyOrdered();
                        $linkPurchasedItem->setLinkHash($linkHash)
                            ->setNumberOfDownloadsBought($numberOfDownloads)
                            ->setStatus(Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PENDING)
                            ->setCreatedAt($orderItem->getCreatedAt())
                            ->setUpdatedAt($orderItem->getUpdatedAt())
                            ->save();
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Set checkout session flag if order has downloadable product(s)
     *
     * @param Varien_Object $observer
     * @return Mage_Downloadable_Model_Observer
     */
    public function setHasDownloadableProducts($observer)
    {
        $session = Mage::getSingleton('checkout/session');
        if (!$session->getHasDownloadableProducts()) {
            $order = $observer->getEvent()->getOrder();
            foreach ($order->getAllItems() as $item) {
                /* @var $item Mage_Sales_Model_Order_Item */
                if ($item->getProductType() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE
                    || $item->getRealProductType() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE
                    || $item->getProductOptionByCode('is_downloadable'))
                {
                    $session->setHasDownloadableProducts(true);
                    break;
                }
            }
        }
        return $this;
    }

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

    /**
     * Check is allowed guest checkuot if quote contain downloadable product(s)
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Downloadable_Model_Observer
     */
    public function isAllowedGuestCheckout(Varien_Event_Observer $observer)
    {
        $quote  = $observer->getEvent()->getQuote();
        /* @var $quote Mage_Sales_Model_Quote */
        $store  = $observer->getEvent()->getStore();
        $result = $observer->getEvent()->getResult();

        $isContain = false;

        foreach ($quote->getAllItems() as $item) {
            if (($product = $item->getProduct()) &&
                $product->getTypeId() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE) {
                $isContain = true;
            }
        }

        if ($isContain && Mage::getStoreConfigFlag(self::XML_PATH_DISABLE_GUEST_CHECKOUT, $store)) {
            $result->setIsAllowed(false);
        }

        return $this;
    }
}
