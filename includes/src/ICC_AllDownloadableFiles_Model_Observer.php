<?php

class ICC_AllDownloadableFiles_Model_Observer
{
    public function updatePurchasedOrders(Varien_Event_Observer $observer)
    {
        $product = $observer->getProduct();

        if('downloadable' == $product->getTypeId()) {

            $orderItems = Mage::getModel('sales/order_item')->getCollection()->addFieldToFilter('product_id', $product->getId());

            foreach($_POST['downloadable']['link'] as $link) {

                $linkId = $link['link_id'];
                $file = json_decode($link['file']);
                $fileStatus = $file[0]->status;

                // $link['link_id'] -> means the new file was added
                if('0' == $linkId && $product->getLinksPurchasedSeparately() != 1) {
                    // This is a new file being added.  Insert a record into downloadable_link_purchased_item for each customer that has purchased this item.
                    try {
                        foreach($orderItems as $orderItem) {
                            $order = Mage::getModel('sales/order')->getCollection()
                                ->addFieldToFilter('entity_id', $orderItem->getOrderId())
                                ->getFirstItem();

                            $downloadPurchase = Mage::getModel('downloadable/link_purchased')
                                ->setOrderId($orderItem->getOrderId())
                                ->setOrderIncrementId($order->getIncrementId())
                                ->setOrderItemId($orderItem->getId())
                                ->setCreatedAt(date('Y-m-d H:i:s'))
                                ->setUpdatedAt(date('Y-m-d H:i:s'))
                                ->setCustomerId($order->getCustomerId())
                                ->setProductName($product->getName())
                                ->setProductSku($product->getSku())
                                ->setLinkSectionTitle('Links');

                            $downloadPurchase->save();
                            // linkId was already created and we need to get it to set up in our new order
                            $linkId = 0;
                            $allProductLinks = Mage::getModel('downloadable/product_type')->getLinks($product);
                            foreach ($allProductLinks as $key => $productLink){
                                if ($productLink->getTitle() == $link['title']){
                                    $linkId = $key;
                                    break;
                                }
                            }
                            //line from /app/code/core/Mage/Downloadable/sql/downloadable_setup/mysql4-upgrade-0.1.12-0.1.13.php
                            //strtr(base64_encode(microtime() . $row['purchased_id'] . $row['order_item_id'] . $row['product_id']), '+/=', '-_,')
                            $linkHash = strtr(base64_encode(microtime() . $downloadPurchase->getId() . $orderItem->getId() . $product->getId()), '+/=', '-_,');
                            $downloadItem = Mage::getModel('downloadable/link_purchased_item')
                                ->setProductId($product->getId())
                                ->setNumberOfDownloadsBought($link['number_of_downloads'])
                                ->setNumberOfDownloadsUsed(0)
                                ->setLinkTitle($link['title'])
                                ->setIsShareable($link['is_shareable'])
                                ->setLinkId($linkId)
                                ->setLinkFile($file[0]->file)
                                ->setLinkType('file')
                                ->setStatus('available')
                                ->setCreatedAt(date('Y-m-d H:i:s'))
                                ->setUpdatedAt(date('Y-m-d H:i:s'))
                                ->setLinkHash($linkHash)
                                ->setOrderItemId($orderItem->getId())
                                ->setPurchasedId($downloadPurchase->getId());

                            $downloadItem->save();
                        }
                    } catch (Mage_Core_Exception $e) {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('downloadable')->__('An error occurred in Gorilla_AllDownloadableFiles_Model_Observer.'));
                    }

                }elseif($fileStatus == 'new'){ // the new PDF file was uploaded to existed Link
                    $purchasedItemCollection = Mage::getModel('downloadable/link_purchased_item')->getCollection()
                        ->addFieldToFilter('product_id', $product->getId())
                        ->addFieldToFilter('link_id', $linkId)
                        ->addFieldToFilter('order_item_id', array('notnull'=> true));

                    try {
                        foreach ($purchasedItemCollection as $purchasedItem){
                            if ($purchasedItem->getLinkFile() == $file[0]->file){
                                continue;
                            }

                            $purchasedItem->setLinkFile($file[0]->file);
                            $purchasedItem->save();
                        }
                    } catch (Mage_Core_Exception $e) {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('downloadable')->__('An error occurred in Gorilla_AllDownloadableFiles_Model_Observer.'));
                    }
                }
            }
        }
    }
}