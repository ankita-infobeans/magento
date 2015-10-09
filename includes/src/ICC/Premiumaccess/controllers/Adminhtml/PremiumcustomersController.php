<?php

class ICC_Premiumaccess_Adminhtml_PremiumcustomersController extends Mage_Adminhtml_Controller_action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('customer/premiumcustomers')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Customers PremiumACCESS Manager'), Mage::helper('adminhtml')->__('Item Manager'));
                  
		return $this;
	}   
 
        /**
         * This method used to render premium access customer list view
         */
	public function indexAction() {                   
		$this->_initAction()
			->renderLayout();
	}        
        

	/**
         * This method used to mass update for selected subscription and status
         */
        public function massStatusAction()
        {            
            $subscriptionIds = $this->getRequest()->getParam('subscription');
            if(!is_array($subscriptionIds)) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
            } else {
                try {
                    foreach ($subscriptionIds as $subscriptionId) {
                        $subscription = Mage::getSingleton('icc_premiumaccess/premiumaccess')
                            ->load($subscriptionId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                    }
                    $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) were successfully updated', count($subscriptionIds))
                    );
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
            $this->_redirect('*/*/index');
        }

        /**
         * This method used to export csv file of premium access list 
         */
        public function exportCsvAction()
        {
            $fileName   = 'subscription.csv';
            $content    = $this->getLayout()->createBlock('subscription/adminhtml_subscription_grid')
                ->getCsv();

            $this->_sendUploadResponse($fileName, $content);
        }

        /**
         * This method used to export xml file of premium access list
         */
        public function exportXmlAction()
        {
            $fileName   = 'subscription.xml';
            $content    = $this->getLayout()->createBlock('subscription/adminhtml_subscription_grid')
                ->getXml();

            $this->_sendUploadResponse($fileName, $content);
        }

        /**
         * This method used to send report on export file for both xml and csv format 
         * @param type $fileName
         * @param type $content
         * @param type $contentType
         */
        protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
        {
            $response = $this->getResponse();
            $response->setHeader('HTTP/1.1 200 OK','');
            $response->setHeader('Pragma', 'public', true);
            $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
            $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
            $response->setHeader('Last-Modified', date('r'));
            $response->setHeader('Accept-Ranges', 'bytes');
            $response->setHeader('Content-Length', strlen($content));
            $response->setHeader('Content-type', $contentType);
            $response->setBody($content);
            $response->sendResponse();
            die;
        }
}