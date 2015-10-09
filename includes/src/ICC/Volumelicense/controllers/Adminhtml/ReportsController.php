<?php

class ICC_Volumelicense_Adminhtml_ReportsController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()->_setActiveMenu("volumelicense/reports")->_addBreadcrumb(Mage::helper("adminhtml")->__("Reports  Manager"), Mage::helper("adminhtml")->__("Reports Manager"));
        return $this;
    }

    public function indexAction() {
        $this->_title($this->__("Volumelicense"));
        $this->_title($this->__("Manager Reports"));

        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Export order grid to CSV format
     */
    public function exportCsvAction() {
        $fileName = 'reports.csv';
        $grid = $this->getLayout()->createBlock('volumelicense/adminhtml_reports_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportXmlAction() {
        $fileName = 'reports.xml';
        $grid = $this->getLayout()->createBlock('volumelicense/adminhtml_reports_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

}
