<?php

class ICC_TEC_Adminhtml_EventController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction() 
    {
        $this->_redirect('*/*/list');
    }
    
    public function listAction ()
    {
        //housekeeping
        $this->_getSession()->setFormData(array());
        $this->_title($this->__('Event Roster'));
        $this->loadLayout();
        $this->_setActiveMenu('icc_tec');
        $this->renderLayout();
    }
    
    public function rosterAction()
    {
        $this->_title($this->__('Event Roster'));
        $this->loadLayout();
        $this->_setActiveMenu('icc_tec');
        $this->renderLayout();
    }
    
    public function gridAction()
    {
        $this->loadLayout()->renderLayout();
    }
    
    /**
     * Check ACL permissions
     */
    public function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('icc_tec/event');
    }
    
    public function exportSearchCsvAction()
    {    
        $content = $this->getLayout()->createBlock('icc_tec/adminhtml_roster_grid')->getCsvFile();
        $roster_rows = file($content['value']); // content is just an array of where the file (et al.) has been put
        $headings_row = array_shift($roster_rows);
        try 
        {
            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');
            $conn->beginTransaction();
            foreach($roster_rows as $row)
            {
                $id = array_shift(explode(',', $row)); // first element is the id of the roster row
                $roster = Mage::getModel('icc_tec/roster')->load($id);
                if( is_null($roster->getInitialExportDate()) )
                {
                    $roster->setInitialExportDate(time());
                    $roster->save();
                }
            }
            $file_name   = 'events.csv';
            $zf = new Zend_Filter_Int();
            $prod_id = $zf->filter( $this->getRequest()->getParam('id') );
            $product = Mage::getModel('catalog/product')->load($prod_id);
            $zf = new Zend_Filter_Alnum();
            $file_name = ($zf->filter('RosterFor' . $product->getName()) . '.csv');
            $this->_prepareDownloadResponse($file_name, $content);
            $conn->commit();
        } 
        catch(Exception $e)
        {
            Mage::log( 'Could not download and uppdate download dates for roster with. Last roster id: ' . $id . ' with exception: ' . $e->getMessage(), null, 'export-event-csv-exception.log');
            $conn->rollback();
        }
    }
    
    public function exportEventProductCsvAction()
    {
        $content = $this->getLayout()->createBlock('icc_tec/adminhtml_event_grid')->getCsvFile();
//        print_r($content);
//        die('is the content');
        $roster_rows = file($content['value']); // content is just an array of where the file (et al.) has been put
        $headings_row = array_shift($roster_rows);
        try 
        {
            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');
            $conn->beginTransaction();
            foreach($roster_rows as $row)
            {
                $rowPieces = explode(',', $row);
                $id = array_shift($rowPieces); // first element is the id of the roster row
                $roster = Mage::getModel('icc_tec/roster')->load($id);
                if( is_null($roster->getInitialExportDate()) )
                {
                    $roster->setInitialExportDate(time());
                    $roster->save();
                }
            }
            $file_name   = 'events-'.date('Y-m-d').'.csv';
            
            $this->_prepareDownloadResponse($file_name, $content);
            $conn->commit();
        } 
        catch(Exception $e)
        {
            Mage::log( 'Could not download and uppdate download dates for roster with. Exception: ' . $e->getMessage(), null, 'export-event-csv-exception.log');
            $conn->rollback();
        }
    }


     public function exportSearchExcelAction()
    {    
        $content = $this->getLayout()->createBlock('icc_tec/adminhtml_roster_grid')->getExcelFile();
        $roster_rows = file($content['value']); // content is just an array of where the file (et al.) has been put
        $headings_row = array_shift($roster_rows);
        try 
        {
            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');
            $conn->beginTransaction();
            foreach($roster_rows as $row)
            {
                $id = array_shift(explode(',', $row)); // first element is the id of the roster row
                $roster = Mage::getModel('icc_tec/roster')->load($id);
                if( is_null($roster->getInitialExportDate()) )
                {
                    $roster->setInitialExportDate(time());
                    $roster->save();
                }
            }
            $file_name   = 'events.xml';
            $zf = new Zend_Filter_Int();
            $prod_id = $zf->filter( $this->getRequest()->getParam('id') );
            $product = Mage::getModel('catalog/product')->load($prod_id);
            $zf = new Zend_Filter_Alnum();
            $file_name = ($zf->filter('RosterFor' . $product->getName()) . '.xml');
            $this->_prepareDownloadResponse($file_name, $content);
            $conn->commit();
        } 
        catch(Exception $e)
        {
            Mage::log( 'Could not download and uppdate download dates for roster with. Last roster id: ' . $id . ' with exception: ' . $e->getMessage(), null, 'export-event-excel-exception.log');
            $conn->rollback();
        }
    }   

    public function exportEventProductExcelAction()
    {
        $content = $this->getLayout()->createBlock('icc_tec/adminhtml_event_grid')->getExcelFile();
//        print_r($content);
//        die('is the content');
        $roster_rows = file($content['value']); // content is just an array of where the file (et al.) has been put
        $headings_row = array_shift($roster_rows);
        try 
        {
            $conn = Mage::getSingleton('core/resource')->getConnection('core_write');
            $conn->beginTransaction();
            foreach($roster_rows as $row)
            {
                $rowPieces = explode(',', $row);
                $id = array_shift($rowPieces); // first element is the id of the roster row
                $roster = Mage::getModel('icc_tec/roster')->load($id);
                if( is_null($roster->getInitialExportDate()) )
                {
                    $roster->setInitialExportDate(time());
                    $roster->save();
                }
            }
            $file_name   = 'events-'.date('Y-m-d').'.xml';
            
            $this->_prepareDownloadResponse($file_name, $content);
            $conn->commit();
        } 
        catch(Exception $e)
        {
            Mage::log( 'Could not download and uppdate download dates for roster with. Exception: ' . $e->getMessage(), null, 'export-event-excel-exception.log');
            $conn->rollback();
        }
    }    
}