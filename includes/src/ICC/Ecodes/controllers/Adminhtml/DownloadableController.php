<?php

class ICC_Ecodes_Adminhtml_DownloadableController extends Mage_Adminhtml_Controller_Action
{
//    private $_productId;
    private $_gpSku;
    
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('catalog/ecodes');
        $this->renderLayout();
    }

    public function resetLinkAction()
    {
        $ecodeDownloadableId = (int) $this->getRequest()->getParam('id');
        if(is_int($ecodeDownloadableId))
        {
            $ecodeDownloadable = Mage::getModel('ecodes/downloadable')->load($ecodeDownloadableId);
            $downloableLinkPurchasedItem = Mage::getModel('downloadable/link_purchased_item')
                ->load($ecodeDownloadable->getOrderItemId(), 'order_item_id');
            $downloableLinkPurchasedItem->setNumberOfDownloadsUsed(0);
			$downloableLinkPurchasedItem->setStatus(Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_AVAILABLE);
            try{
                $downloableLinkPurchasedItem->save();
                $this->_getSession()->addSuccess('Links successfully reset.');
            }catch(Exception $e){
                Mage::logException($e);
            }
        }else{
            $this->_getSession()->addError('There was a problem resetting the link.');
        }

        $this->_redirect('*/*');
    }

    public function massDisableAction()
    {
        $serialIds = $this->getRequest()->getParam('serial');
        if(!is_array($serialIds)) {
             Mage::getSingleton('adminhtml/session')
                 ->addError(Mage::helper('adminhtml')->__('Please select one or more serial numbers.'));
        } else {
            try {
                $serial = Mage::getModel('ecodes/downloadable');
                foreach ($serialIds as $serialId) {
                    // reset object
                    $serial->setData(array())
                           ->setOrigData();
                    
                    // disable it
                    $serial->load($serialId)
                           ->setEnabled(false);
                    $serial->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) have been disabled.', count($serialIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        
        $this->_redirect('*/*/index');
    }

    /**
     * Generate Downloadable eCode grid for ajax request from customer page
     */
    public function assignedCustomerAction()
    {
        $customerId = intval($this->getRequest()->getParam('id'));
        if ($customerId) {
            $this->getResponse()->setBody(
                $this
                    ->getLayout()
                    ->createBlock('ecodes/adminhtml_customer_edit_tab_downloadable_assigned')
                    ->setCustomerId($customerId)
                    ->toHtml()
            );
        }
    }

    /**
     * Generate Downloadable eCode grid for ajax request from customer page
     */
    public function availableCustomerAction()
    {
        $customerId = intval($this->getRequest()->getParam('id'));
        if ($customerId) {
            $this->getResponse()->setBody(
                $this
                    ->getLayout()
                    ->createBlock('ecodes/adminhtml_customer_edit_tab_downloadable_available')
                    ->setCustomerId($customerId)
                    ->toHtml()
            );
        }
    }

    public function massAssignAction()
    {
        $serialIds = $this->getRequest()->getParam('serial');

        if(!is_array($serialIds))
        {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')
                ->__('Please select one or more serial numbers.'));
            $this->_redirect('*/*/index');
            return $this;
        }else{
            $this->_redirect('*/*/assign', array('_query' => array('serial_ids' => $serialIds)));
            return $this;
        }
    }

    public function assignAction()
    {
        $serialIds = $this->getRequest()->getParam('serial_ids');

        if(!is_array($serialIds))
        {
            $serialIds = array($serialIds);
        }

        if(!$this->_checkSerials($serialIds))
        {
            $this->_redirect('*/*/index');
            return $this;
        }

        Mage::register('ecode_assignment_ids', $serialIds);
        $this->loadLayout();
        $this->renderLayout();
    }

    public function assignPostAction()
    {
        $serialIds = $this->getRequest()->getParam('serial_ids');
        $orderIncrementId = $this->getRequest()->getParam('order_increment_id');

        if(!$this->_checkSerials($serialIds))
        {
            $this->_redirect('*/*/index');
            return $this;
        }

        $orderItem = $this->_getOrderItem($this->_getOrderFromIncrementId($orderIncrementId), $this->_gpSku );
        /* @var Mage_Sales_Model_Order_Item $orderItem */

        if(is_array($serialIds) && is_object($orderItem) && $orderItem->getId())
        {
            $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection();
            /* @var ICC_Ecodes_Model_Mysql4_Downloadable_Collection $downloadableCollection */
            
            try{
                $downloadableCollection->assignSpecificSerials($orderItem, $serialIds);
                $errors = $downloadableCollection->getInfo('errors');
                if(count($errors)) {
                    $message = '';
                    foreach($errors as $e) {
                        $message .= $e;
                    }
                    $this->_getSession()->addError($message);
                }
                if( ! count( $this->_getSession()->getMessages()->getErrors() )) {
                    $this->_getSession()->addSuccess('Successfully added these serials to the order.');
                }
            }catch(ICC_Ecodes_Exception $e){
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
            }
        } else {
            $this->_getSession()->addError('The GP Sku for the serials you selected do not match any of the items in the order.');
        }
        $this->_redirect('*/*/index');
    }

    private function _getOrderFromIncrementId($incrementId)
    {
        if(is_integer((int) $incrementId))
        {
            try{
                $order = Mage::getModel('sales/order');
                /* @var Mage_Sales_Model_Order $order */
                $order->loadByIncrementId($incrementId);
                if($order->getId())
                {
                    return $order;
                }
            }catch(Exception $e){
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
                return false;
            }
        }
        return false;
    }

    private function _getOrderItem(Mage_Sales_Model_Order $order, $gpSku)
    {
        foreach($order->getAllItems() as $orderItem)
        {
            $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
            if($product->getGpSku() == $gpSku)
            {
                return $orderItem;
            }
        }
        return false;
    }

    private function _checkSerials($serialIds)
    {
        $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection();
        /* @var ICC_Ecodes_Model_Mysql4_Downloadable_Collection $downloadableCollection */
        $downloadableCollection
//            ->addFieldToFilter('order_item_id', array('eq'=>''))
            ->addFieldToFilter('id', array('in' => $serialIds))
            ->addFieldToFilter('enabled', array('eq' => true))
        ;
        $downloadableCollection->getSelect()->where( 'order_item_id = "" OR order_item_id IS NULL' );

        if($downloadableCollection->count('serial') != count($serialIds))
        {
            $this->_getSession()->addError('Some selected eCode Serials are either disabled or already assigned.');
            return false;
        }

        $downloadableCollection->getSelect()->group('gp_sku');
        if($downloadableCollection->clear()->count() != 1)
        {
            $this->_getSession()->addError('The selected eCode Serials are not all assigned to the same GP SKU.');
            return false;
        }
//        $this->_productId = $downloadableCollection->load()->getFirstItem()->getProductId();
        $this->_gpSku = $downloadableCollection->load()->getFirstItem()->getGpSku();
        
        return true;
    }

    public function editAction()
    {
        $downloadable = Mage::getModel('ecodes/downloadable');
        if ($id = $this->getRequest()->getParam('id'))
        {

            $downloadable->load($id);
            Mage::register('current_downloadable', $downloadable);

            $this->loadLayout();
            $this->_setActiveMenu('catalog/ecodes');
            $this->renderLayout();
            return $this;
        }
        $this->_redirect('*/*/grid');
    }

    public function saveAction()
    {
        $downloadableId = $this->getRequest()->getParam('id');
        $isEnabled = (bool) $this->getRequest()->getParam('enabled');
        $documentId = $this->getRequest()->getParam('document_id');
        $prodTitle = $this->getRequest()->getParam('product_title');
        try{
            $downloadable = Mage::getModel('ecodes/downloadable')->load($downloadableId);
            if($downloadable->getId() && is_bool($isEnabled))
            {
                $downloadable->setEnabled($isEnabled);
                $downloadable->setDocumentId($documentId);
                $downloadable->setProductTitle($prodTitle);
                $downloadable->save();
                $this->_getSession()->addSuccess('eCode Serial successfully updated.');
            }

            $this->_redirect('*/*/edit', array('id' => $downloadableId));
            return $this;
        }catch(Exception $e){
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }

        $this->_redirect('*/*/index');
    }
    
    /**
     * Check ACL permissions
     * @return bool
     */
    public function _isAllowed()
    {
        $isAllowed = (boolean) Mage::getSingleton('admin/session')->isAllowed('ecodes/ecodes');
        return $isAllowed;
    }

    /**
     * Render new eCode import Form.
     */
    public function newAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('catalog/ecodes');
        $this->renderLayout();
    }

    public function importAction()
    {
        $params = $this->getRequest()->getParams();

        $ecodes = $this->_initEcodes($params['ecodes']);
        $sku = $params['sku'];

        $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection();
        if(!$downloadableCollection->addEcodesBySku($sku, $ecodes))
        {

            foreach($downloadableCollection->getInfo('errors') as $error)
            {
                $this->_getSession()->addError($error);
            }
        }else{
            $this->_getSession()->addSuccess('eCodes imported successfuly.');
            $this->_processDownloadsWaitingForSerial($sku);
        }

        $this->_redirectReferer();
    }

    /**
     * CSV upload section
     */
    public function uploadcsvAction()
    {
        // show updload form
        $this->loadLayout();
        $this->_setActiveMenu('catalog/ecodes');
        $this->renderLayout();
    }
    
    public function savecsvAction ()
    {
        $file_name = $_FILES['csv_file']['name'];
        $file_path = $_FILES['csv_file']['tmp_name'];
        $session = $this->_getSession();
        
        $d = Mage::getModel('ecodes/downloadable');
        $d->validateSerialCsvFile($file_path, $file_name);

        if($d->hasPassedRows()) {
            $d->processPassedRows();
            if($d->processedSuccess()) {
                $session->addSuccess( sprintf('Successfully saved %d rows out of %d total rows.', $d->getNumberPassedRows(), $d->getTotalNumberRows() ));
            } else {
                $session->addError('No rows were added because there was an error saving the validated rows. Please check below for possible issues.');
            }
        }
        
        if($d->hasErrors()) {
            foreach($d->getErrorMessages() as $message) {
                $session->addError($message);
            }
        }
        
        $this->_redirect('*/*/uploadcsv');
    }
    
    
    /**
     * Process the list of queued tasks for products that were purchased but 
     * are awaiting serial numbers to be added to the serial pool. 
     * 
     * Checks each task for it's sku before processing to avoid unecessary 
     * processing of tasks, since we don't have reason to believe any other skus 
     * might now have a serial.
     * 
     * @param string $sku - the sku of the serials we just added. 
     *                      (Can change this to gp_sku later, just make sure to 
     *                      adjust it everywhere within the function.)
     * @author Ariel Allon
     */
    private function _processDownloadsWaitingForSerial($sku) 
    {
        $queuedTasks = Mage::getModel('gorilla_queue/queue')
                                        ->getCollection()
                                        ->addFieldToFilter('code', 'ecodes-assign-serials');
        foreach ($queuedTasks as $task) {
            $taskData = unserialize($task->getQueueItemData());
            if ($taskData['sku'] == $sku) {
                $task->process();
            }
        }
    }

    private function _initEcodes($ecodes)
    {
        // Explode on any system's newline character.
        $ecodes = explode('<br />', nl2br($ecodes));
        $sanitized = array();
        foreach($ecodes as $ecode)
        {
            $sanitized[] = trim($ecode);
        }

        return $sanitized;
    }
}
