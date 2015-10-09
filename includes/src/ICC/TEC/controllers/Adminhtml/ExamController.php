<?php

class ICC_TEC_Adminhtml_ExamController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction() 
    {
        $this->_redirect('*/*/list');
    }
    
    /**
     * Display grid
     */
    public function listAction ()
    {
        //housekeeping
        $this->_getSession()->setFormData(array());
        
        $this->_title($this->__('Exam Roster List'));
        
        $this->loadLayout();
        
        $this->_setActiveMenu('icc_tec');
        
//        $this->_addBreadcrumb($this->__('Catalog'),$this->__('Catalog'));
//        $this->_addBreadcrumb($this->__('Animal'),$this->__('Animal'));
        $this->renderLayout();
       // $model = Mage::getModel('gorilla_queue/queue');
       // $ar = array( 'action' => 'another test with array');
        //$model->setData($ar);
        //$model->save();
    }
    
    public function rosterAction()
    {
        $this->_getSession()->setFormData(array());
        
        $this->_title($this->__('Exam Product Roster List'));
        $this->loadLayout();
        $this->_setActiveMenu('icc_tec');
        $this->renderLayout();
    }
    
    public function gridAction()
    {
        $this->_redirect('*/*/list');
//        $this->loadLayout()->renderLayout();
    }
    
    /**
     * Check ACL permissions
     */
    public function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('icc_tec/exam');
    }
    
    public function exportSearchCsvAction()
    {    
        $content = $this->getLayout()->createBlock('icc_tec/adminhtml_exam_grid')->getCsvFile();
//        print_r($content); die;
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
            $file_name   = 'exam_roster-' . date('Y-m-d' ) . '.csv';
            
            $this->_prepareDownloadResponse($file_name, $content);
            $conn->commit();
        } 
        catch(Exception $e)
        {
            Mage::log( 'Could not download and uppdate download dates for roster with exception: ' . $e->getMessage(), null, 'export-event-csv-exception.log');
            $conn->rollback();
        }
    }

    public function exportSearchExcelAction()
    {
        $content = $this->getLayout()->createBlock('icc_tec/adminhtml_exam_grid')->getExcelFile();
//        print_r($content); die;
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
            $file_name   = 'exam_roster-' . date('Y-m-d' ) . '.xml';
            
            $this->_prepareDownloadResponse($file_name, $content);
            $conn->commit();
        } 
        catch(Exception $e)
        {
            Mage::log( 'Could not download and uppdate download dates for roster with exception: ' . $e->getMessage(), null, 'export-event-excel-exception.log');
            $conn->rollback();
        }
    }    
}