<?php
class ICC_Logging_Model_Downloadable_Observer extends Mage_Downloadable_Model_Observer
{
    public function saveDownloadableOrderItem($observer)
    {
        $orderItem = $observer->getEvent()->getItem();
        if (!$orderItem->getId()) {
            //order not saved in the database
            Mage::log(
                'order not saved in the database! orderId= ' . $orderItem->getOrderId() .
                ' productId: ' . $orderItem->getProductId(),
                null, 'downloadable_link.log'
            );
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
                Mage::log(
                    'Link Purchased has been successfully saved! orderId= ' . $linkPurchased->getOrderId() .
                    ' purchasedId: ' . $linkPurchased->getPurchasedId() .
                    ' productSKU: '  . $linkPurchased->getProductSku() .
                    ' customerId: '  . $linkPurchased->getCustomerId(),
                    null, 'downloadable_link.log'
                );
                /* Added By Infobeans For Bill Member Payment To Change The Link Status Available Start */
                $mailOrder = Mage::getModel('sales/order')->load($linkPurchased->getOrderId());
		$payment_method = $mailOrder->getPayment()->getMethodInstance()->getTitle();
                $billMemberStatus = '';
                if($payment_method == "Bill Member Account" || $payment_method == "No Payment Information Required") {
		  $billMemberStatus = Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_AVAILABLE ;
                }
                else {
		    $billMemberStatus = Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PENDING ;
                }
                /* Added By Infobeans For Bill Member Payment To Change The Link Status Available End */
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
                            //->setStatus(Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PENDING)
                            ->setStatus($billMemberStatus)
                            ->setCreatedAt($orderItem->getCreatedAt())
                            ->setUpdatedAt($orderItem->getUpdatedAt())
                            ->save();
                    }
                }
            }
        }

        return $this;
    }
}