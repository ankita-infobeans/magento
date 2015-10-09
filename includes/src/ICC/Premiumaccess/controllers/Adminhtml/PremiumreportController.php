<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ICC_Premiumaccess_Adminhtml_PremiumreportController extends Mage_Adminhtml_Controller_Action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('report/premiumreport')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('PremiumACCESS Report'), Mage::helper('adminhtml')->__('Item Manager'));
                  
		return $this;
	}   
 
	public function indexAction() {                
		$this->_initAction()
			->renderLayout();
	}
	
     /**
     * Export order grid to CSV format
     */
    public function exportCsvAction() {
        $fileName = 'reports.csv';
        $grid = $this->getLayout()->createBlock('icc_premiumaccess/adminhtml_premiumreport_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportXmlAction() {
        $fileName = 'reports.xml';
        $grid = $this->getLayout()->createBlock('icc_premiumaccess/adminhtml_premiumreport_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
}        