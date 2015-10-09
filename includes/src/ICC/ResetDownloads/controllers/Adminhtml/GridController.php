<?php

class ICC_ResetDownloads_Adminhtml_GridController extends Mage_Adminhtml_Controller_Action 
{
    public function ordersDownloadsAction() {
        $this->_initCustomer();
        $this->getResponse()->setBody($this->getLayout()->createBlock('resetdownloads/adminhtml_customer_edit_tab_resetdownloads')->toHtml());
    }
    public function volumeOrdersDownloadsAction() {
        $this->_initCustomer();
        $this->getResponse()->setBody($this->getLayout()->createBlock('resetdownloads/adminhtml_customer_edit_tab_volumeresetdownloads')->toHtml());
    }

}