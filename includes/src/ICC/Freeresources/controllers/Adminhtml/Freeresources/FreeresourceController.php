<?php
/**
 * Free Resource admin controller
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Adminhtml_Freeresources_FreeresourceController
    extends ICC_Freeresources_Controller_Adminhtml_Freeresources {
    /**
     * init the freeresource
     * @access protected
     * @return ICC_Freeresources_Model_Freeresource
     */
    protected function _initFreeresource(){
        $freeresourceId  = (int) $this->getRequest()->getParam('id');
        $freeresource    = Mage::getModel('icc_freeresources/freeresource');
        if ($freeresourceId) {
            $freeresource->load($freeresourceId);
        }
        Mage::register('current_freeresource', $freeresource);
        return $freeresource;
    }
     /**
     * default action
     * @access public
     * @return void
     */
    public function indexAction() {
        $this->loadLayout();
        $this->_title(Mage::helper('icc_freeresources')->__('Manage Free Resources'))
             ->_title(Mage::helper('icc_freeresources')->__('Free Resources'));
        $this->renderLayout();
    }
    /**
     * grid action
     * @access public
     * @return void
     */
    public function gridAction() {
        $this->loadLayout()->renderLayout();
    }
    /**
     * edit free resource - action
     * @access public
     * @return void
     */
    public function editAction() {
        $freeresourceId    = $this->getRequest()->getParam('id');
        $freeresource      = $this->_initFreeresource();
        if ($freeresourceId && !$freeresource->getId()) {
            $this->_getSession()->addError(Mage::helper('icc_freeresources')->__('This free resource no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }
        $data = Mage::getSingleton('adminhtml/session')->getFreeresourceData(true);
        if (!empty($data)) {
            $freeresource->setData($data);
        }
        Mage::register('freeresource_data', $freeresource);
        $this->loadLayout();
        $this->_title(Mage::helper('icc_freeresources')->__('Manage Free Resources'))
             ->_title(Mage::helper('icc_freeresources')->__('Free Resources'));
        if ($freeresource->getId()){
            $this->_title($freeresource->getFreeResource());
        }
        else{
            $this->_title(Mage::helper('icc_freeresources')->__('Add Free Resource'));
        }
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        $this->renderLayout();
    }
    /**
     * new free resource action
     * @access public
     * @return void
     */
    public function newAction() {
        $this->_forward('edit');
    }
    /**
     * save free resource - action
     * @access public
     * @return void
     */
    public function saveAction() {
        if ($data = $this->getRequest()->getPost('freeresource')) {
            try {
                $freeresource = $this->_initFreeresource();
                $freeresource->addData($data);
                $freeresource->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('icc_freeresources')->__('Free Resource has been saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $freeresource->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFreeresourceData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
            catch (Exception $e) {
                print_r($e); die;
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('There was a problem saving the free resource.'));
                Mage::getSingleton('adminhtml/session')->setFreeresourceData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('Unable to find free resource to save.'));
        $this->_redirect('*/*/');
    }
    /**
     * delete free resource - action
     * @access public
     * @return void
     */
    public function deleteAction() {
        if( $this->getRequest()->getParam('id') > 0) {
            try {
                $freeresource = Mage::getModel('icc_freeresources/freeresource');
                $freeresource->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('icc_freeresources')->__('Free Resource has been deleted.'));
                $this->_redirect('*/*/');
                return;
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('There was an error deleting free resource.'));
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                Mage::logException($e);
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('Could not find free resource to delete.'));
        $this->_redirect('*/*/');
    }
    /**
     * mass delete free resource - action
     * @access public
     * @return void
     */
    public function massDeleteAction() {
        $freeresourceIds = $this->getRequest()->getParam('freeresource');
        if(!is_array($freeresourceIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('Please select free resources to delete.'));
        }
        else {
            try {
                foreach ($freeresourceIds as $freeresourceId) {
                    $freeresource = Mage::getModel('icc_freeresources/freeresource');
                    $freeresource->setId($freeresourceId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('icc_freeresources')->__('Total of %d free resources have been deleted.', count($freeresourceIds)));
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('There was an error deleting free resources.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }
    /**
     * mass status change - action
     * @access public
     * @return void
     */
    public function massStatusAction(){
        $freeresourceIds = $this->getRequest()->getParam('freeresource');
        if(!is_array($freeresourceIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('Please select free resources.'));
        }
        else {
            try {
                foreach ($freeresourceIds as $freeresourceId) {
                $freeresource = Mage::getSingleton('icc_freeresources/freeresource')->load($freeresourceId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess($this->__('Total of %d free resources have been updated.', count($freeresourceIds)));
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('There was an error updating free resources.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/*/index');
    }
    /**
     * export as csv - action
     * @access public
     * @return void
     */
    public function exportCsvAction(){
        $fileName   = 'freeresource.csv';
        $content    = $this->getLayout()->createBlock('icc_freeresources/adminhtml_freeresource_grid')->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    /**
     * export as MsExcel - action
     * @access public
     * @return void
     */
    public function exportExcelAction(){
        $fileName   = 'freeresource.xls';
        $content    = $this->getLayout()->createBlock('icc_freeresources/adminhtml_freeresource_grid')->getExcelFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    /**
     * export as xml - action
     * @access public
     * @return void
     */
    public function exportXmlAction(){
        $fileName   = 'freeresource.xml';
        $content    = $this->getLayout()->createBlock('icc_freeresources/adminhtml_freeresource_grid')->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    /**
     * Check if admin has permissions to visit related pages
     * @access protected
     * @return boolean
     */
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('icc_freeresources/freeresource');
    }
}