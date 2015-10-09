<?php
require_once Mage::getModuleDir('controllers','Mage_Downloadable') . DS . 'DownloadController.php';
class ICC_Watermark_DownloadController extends Mage_Downloadable_DownloadController
{ 
   
    /**
     * Download link action
     */
    public function linkAction()
    {
        $id = $this->getRequest()->getParam('id', 0);
        $linkPurchasedItem = Mage::getModel('downloadable/link_purchased_item')->load($id, 'link_hash');
        
        $order_item_id = $linkPurchasedItem->getOrderItemId(); 
        $checkGift = $this->checkGiftLink($order_item_id);
        
        if (! $linkPurchasedItem->getId() ) {
            $this->_getCustomerSession()->addNotice(Mage::helper('downloadable')->__("Requested link does not exist."));
            return $this->_redirect('*/customer/products');
        }
        $product = Mage::getModel('catalog/product')->load($linkPurchasedItem->getProductId());

        //Modification. Put already loaded product into registry so it does not need to be loaded again
        //When checking for a copyright value.
        Mage::register('downloadable_product',$product);
        Mage::register('link_purchased_item',$linkPurchasedItem);

        if (!Mage::helper('downloadable')->getIsShareable($linkPurchasedItem)) {
            $customerId = $this->_getCustomerSession()->getCustomerId();
            if (!$customerId) {
                $product = Mage::getModel('catalog/product')->load($linkPurchasedItem->getProductId());
                if ($product->getId()) {
                    $notice = Mage::helper('downloadable')->__(
                        'Please log in to download your product or purchase <a href="%s">%s</a>.',
                        $product->getProductUrl(), $product->getName()
                    );
                } else {
                    $notice = Mage::helper('downloadable')->__('Please log in to download your product.');
                }
                $this->_getCustomerSession()->addNotice($notice);
                $this->_getCustomerSession()->authenticate($this);
                $this->_getCustomerSession()->setBeforeAuthUrl(Mage::getUrl('downloadable/customer/products/'),
                    array('_secure' => true)
                );
                return ;
            }
            $linkPurchased = Mage::getModel('downloadable/link_purchased')->load($linkPurchasedItem->getPurchasedId());
            if ($linkPurchased->getCustomerId() != $customerId) {
                $this->_getCustomerSession()->addNotice(Mage::helper('downloadable')->__("Requested link does not exist."));
                //return $this->_redirect('*/customer/products');
                return $this->_redirect('ecodes/account/products/');
            }
        }
        $downloadsLeft = $linkPurchasedItem->getNumberOfDownloadsBought()
            - $linkPurchasedItem->getNumberOfDownloadsUsed();

        $status = $linkPurchasedItem->getStatus();
        if ($status == Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_AVAILABLE
            && ($downloadsLeft || $linkPurchasedItem->getNumberOfDownloadsBought() == 0)
        ) {
            $resource = '';
            $resourceType = '';
            if ($linkPurchasedItem->getLinkType() == Mage_Downloadable_Helper_Download::LINK_TYPE_URL) {
                $resource = $linkPurchasedItem->getLinkUrl();
                $resourceType = Mage_Downloadable_Helper_Download::LINK_TYPE_URL;
            } elseif ($linkPurchasedItem->getLinkType() == Mage_Downloadable_Helper_Download::LINK_TYPE_FILE) {
                $resource = Mage::helper('downloadable/file')->getFilePath(
                    Mage_Downloadable_Model_Link::getBasePath(), $linkPurchasedItem->getLinkFile()
                );
                $resourceType = Mage_Downloadable_Helper_Download::LINK_TYPE_FILE;
            }
            try {
                $linkPurchasedItem->setNumberOfDownloadsUsed($linkPurchasedItem->getNumberOfDownloadsUsed() + 1);

                if ($linkPurchasedItem->getNumberOfDownloadsBought() != 0 && !($downloadsLeft - 1)) {
                    $linkPurchasedItem->setStatus(Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_EXPIRED);
                }
                $linkPurchasedItem->save();
                
                /* Update in the volumelicense reports*/
                if(Mage::helper('core')->isModuleEnabled('ICC_Volumelicense')){
                    $order_id = Mage::getResourceModel('sales/order_item_collection')->addAttributeToSelect('order_id')
                            ->addAttributeToFilter('item_id', $linkPurchasedItem->getOrderItemId())->getFirstItem()->getData();
                    if($order_id){
                        $order = Mage::getModel("sales/order")->load($order_id);
                        
                        if($order->getData('volume_license')) {
                                Mage::helper('volumelicense')->setReportsLog ($order, FALSE , TRUE);
                                unset($order);
                        }
                       
                    }
                }
                /* Update in the volumelicense reports code end*/
                // Ticket#2013121810000161, in case when PDF file has a huge size, we have to increase the value of NumberOfDownloadsUsed before downloading, to avoid killing the PHP script by web server by timeout
                $this->_processDownload($resource, $resourceType,$checkGift);

                exit(0);
            }
            catch (Exception $e) {
                $this->_getCustomerSession()->addError(
                    Mage::helper('downloadable')->__('An error occurred while getting the requested content. Please contact the store owner.')
                );
            }
        } elseif ($status == Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_EXPIRED) {
            $this->_getCustomerSession()->addNotice(Mage::helper('downloadable')->__('The link has expired.'));
        } elseif ($status == Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PENDING
            || $status == Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PAYMENT_REVIEW
        ) {
            $this->_getCustomerSession()->addNotice(Mage::helper('downloadable')->__('The link is not available.'));
        } else {
            $this->_getCustomerSession()->addError(
                Mage::helper('downloadable')->__('An error occurred while getting the requested content. Please contact the store owner.')
            );
        }
        //return $this->_redirect('*/customer/products');
        return $this->_redirect('ecodes/account/products/');
    }

    protected function _processDownload($resource, $resourceType, $checkGift = Null)
    {
        $helper = Mage::helper('downloadable/download');
        /* @var $helper Mage_Downloadable_Helper_Download */

        $helper->setResource($resource, $resourceType,$checkGift);

        $fileName       = $helper->getFilename();
        $contentType    = $helper->getContentType();

        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true);

        if ($fileSize = $helper->getFilesize()) {
            $this->getResponse()
                ->setHeader('Content-Length', $fileSize);
        }

//        if($contentType != 'application/pdf'){
            if ($contentDisposition = $helper->getContentDisposition()) {
                $this->getResponse()
                    ->setHeader('Content-Disposition', $contentDisposition . '; filename='.$fileName);
            }
//        }
        $this->getResponse()
            ->clearBody();
        $this->getResponse()
            ->sendHeaders();

        $helper->output();
    }
    
      /**
      * Volume Licence Water Mark show for gift user
      */
    public function checkGiftLink($itemOrderId) {
	
	$order_item_details = Mage::getModel("sales/order_item")->load($itemOrderId); 
	
	$order_id = $order_item_details->getOrderId(); 
	$order_details = Mage::getModel("sales/order")->load($order_id); 
	
	$parent_order_id = $order_details->getParentOrderId();
	$volume_licence = $order_details->getVolumeLicense();
	
	if((empty($parent_order_id) || $parent_order_id == NULL || $parent_order_id == Null || $parent_order_id == null) && $volume_licence == 1){
	    $checkGift = 1;
	}else if ((!empty($parent_order_id) || $parent_order_id != NULL || $parent_order_id != Null || $parent_order_id != null) && $volume_licence == 1){
	    $parent_order_details = Mage::getModel("sales/order")->load($parent_order_id); 
	    $customer_id = $parent_order_details->getCustomerId();
	    
	    $customerData = Mage::getSingleton('customer/session')->getCustomer();
	    $loginCustomerId = $customerData->getId();
	    
	    if($customer_id == $loginCustomerId){
		$checkGift = 1;
	    }else{
		$checkGift = 0;
	    }
	}else{
	    $checkGift = '';
	}    
	//echo $parent_order_id.'***********'.$volume_licence;
	return $checkGift;
    }
   }
