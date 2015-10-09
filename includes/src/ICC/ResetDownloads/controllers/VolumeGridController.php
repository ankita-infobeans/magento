<?php

class ICC_ResetDownloads_VolumeGridController extends Mage_Adminhtml_Controller_Action 

{
    protected function _initCustomer($idFieldName = 'id')
    {
        $this->_title($this->__('Customers'))->_title($this->__('Manage Customers'));

        $customerId = (int) $this->getRequest()->getParam($idFieldName);
        $customer = Mage::getModel('customer/customer');

        if ($customerId) {
            $customer->load($customerId);
        }

        Mage::register('current_customer', $customer);
        return $this;
    }


    public function indexAction() {
        $this->_initCustomer();
        $this->getResponse()->setBody($this->getLayout()->createBlock('resetdownloads/adminhtml_customer_edit_tab_volumeresetdownloads')->toHtml());
    }


    public function volumeresetdownloadsAction() {
        $this->_initCustomer();
		$this->resetDownloadsAction();
        $this->getResponse()->setBody($this->getLayout()->createBlock('resetdownloads/adminhtml_customer_edit_tab_volumeresetdownloads')->toHtml());
    }

    public function resetDownloadsAction() {
		$itemId = (int) $this->getRequest()->getParam('item_id');
		if(is_int($itemId)) {
			$downloadsModel = Mage::getModel('volumelicense/links') -> load($itemId);	
			try {
				$downloadsModel->setNumberOfDownloads(0)->save();
				echo "Data updated successfully.";
			} catch (Exception $e){
				echo $e->getMessage();
				$this->_getSession()->addError('There was a problem resetting the link.');
			}
		} else {
				$this->_getSession()->addError('There was a problem resetting the link.');
		}
    }
}