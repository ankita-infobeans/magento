<?php
/**
* @author Amasty Team
* @copyright Amasty
* @package Amasty_Acart
*/
class Amasty_Acart_Adminhtml_HistoryController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout(); 

        $this->_setActiveMenu('promo/amacart/history');
            
        $this->_addContent($this->getLayout()->createBlock('amacart/adminhtml_history')); 
            $this->renderLayout();

            }
        
    /**
     * Export order grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'history.csv';
        $grid       = $this->getLayout()->createBlock('amacart/adminhtml_history_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportExcelAction()
    {
        $fileName   = 'history.xml';
        $grid       = $this->getLayout()->createBlock('amacart/adminhtml_history_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
        
    }
?>