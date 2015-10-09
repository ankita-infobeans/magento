<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */
class Amasty_Xcoupon_Adminhtml_ReportController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction() 
	{
	    $this->loadLayout(); 
        $this->_setActiveMenu('promo/amxcoupon');
        if (version_compare(Mage::getVersion(), '1.4.1.0') >= 0) { 
            $this
                ->_title($this->__('Promotions'))
                ->_title($this->__('Coupons Usage Report')); 
        }         
        $this->_addContent($this->getLayout()->createBlock('amxcoupon/adminhtml_report')); 	    
 	    $this->renderLayout();
	} 

    public function exportCsvAction()
    {
        $block    = $this->getLayout()->createBlock('amxcoupon/adminhtml_report');
        
        $content  = $block->getCsvFile();
        if (!$content){ // earlier versions
            $content = $block->getCsv();
        }
            
        $this->_prepareDownloadResponse('report.csv', $content);  
    }

    public function exportXmlAction()
    {
        $content    = $this->getLayout()->createBlock('amxcoupon/adminhtml_report')
            ->getXmlFile();
        $this->_prepareDownloadResponse('report.xml', $content);  
    }
	
}