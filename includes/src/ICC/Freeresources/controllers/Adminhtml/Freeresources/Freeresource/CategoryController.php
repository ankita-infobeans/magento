<?php
/**
 * Free Resource admin controller
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Adminhtml_Freeresources_Freeresource_CategoryController
    extends Mage_Adminhtml_Controller_Action {
    /**
     * init the category
     * @access protected
     * @return ICC_Freeresources_Model_Freeresource_Category
     */
    protected function _initCategory(){
        $categoryId  = (int) $this->getRequest()->getParam('id');
        $category    = Mage::getModel('icc_freeresources/freeresource_category');
        if ($categoryId) {
            $category->load($categoryId);
        }
        Mage::register('current_category', $category);
        return $category;
    }
     /**
     * default action
     * @access public
     * @return void

     */
    public function indexAction() {
        $this->loadLayout();
        $this->_title(Mage::helper('icc_freeresources')->__('Manage Free Resources'))
             ->_title(Mage::helper('icc_freeresources')->__('Free Resources'))
             ->_title(Mage::helper('icc_freeresources')->__('Category'));
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
     * grid action
     * @access public
     * @return void

     */
    public function gridAction() {
        $this->loadLayout()->renderLayout();
    }
    /**
     * edit category - action
     * @access public
     * @return void

     */
    public function editAction() {
        $categoryId    = $this->getRequest()->getParam('id');
        $category      = $this->_initCategory();
//        if (!$category->getId()) {
//            $this->_getSession()->addError(Mage::helper('icc_freeresources')->__('This category no longer exists.'));
//            $this->_redirect('*/*/');
//            return;
//        }
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $category->setData($data);
        }
        Mage::register('category_data', $category);
        $freeresource = Mage::getModel('icc_freeresources/freeresource')->load($category->getFreeresourceId());
        Mage::register('current_freeresource', $freeresource);
        $this->loadLayout();
        $this->_title(Mage::helper('icc_freeresources')->__('Manage Free Resources'))
             ->_title(Mage::helper('icc_freeresources')->__('Free Resources'))
             ->_title(Mage::helper('icc_freeresources')->__('Category'))
             ->_title($category->getTitle());
        $this->renderLayout();
    }
    /**
     * save free resource - action
     * @access public
     * @return void

     */
    public function saveAction() {
        if ($data = $this->getRequest()->getPost('category')) {
            try {
                $category = $this->_initCategory();
                $category->addData($data);
                $category->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('icc_freeresources')->__('Category has been saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $category->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
            catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('There was a problem saving the category.'));
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('Unable to find category to save.'));
        $this->_redirect('*/*/');
    }
    /**
     * delete category - action
     * @access public
     * @return void
     */
    public function deleteAction() {
        if( $this->getRequest()->getParam('id') > 0) {
            try {
                $category = Mage::getModel('icc_freeresources/freeresource_category');
                $category->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('icc_freeresources')->__('Category has been deleted.'));
                $this->_redirect('*/*/');
                return;
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
            catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('There was an error deleting the category.'));
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('Could not find category to delete.'));
        $this->_redirect('*/*/');
    }
    /**
     * mass delete categorys - action
     * @access public
     * @return void

     */
    public function massDeleteAction() {
        $categoryIds = $this->getRequest()->getParam('category');
        if(!is_array($categoryIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('Please select categorys to delete.'));
        }
        else {
            try {
                foreach ($categoryIds as $categoryId) {
                    $category = Mage::getModel('icc_freeresources/freeresource_category');
                    $category->setId($categoryId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('icc_freeresources')->__('Total of %d categorys have been deleted.', count($categoryIds)));
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('There was an error deleting categorys.'));
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
        $categoryIds = $this->getRequest()->getParam('category');
        if(!is_array($categoryIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('Please select categorys.'));
        }
        else {
            try {
                foreach ($categoryIds as $categoryId) {
                    $category = Mage::getSingleton('icc_freeresources/freeresource_category')->load($categoryId)
                             ->setStatus($this->getRequest()->getParam('status'))
                             ->setIsMassupdate(true)
                             ->save();
                }
                $this->_getSession()->addSuccess($this->__('Total of %d categorys have been updated.', count($categoryIds)));
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('icc_freeresources')->__('There was an error updating categorys.'));
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
        $fileName   = 'freeresource_categorys.csv';
        $content    = $this->getLayout()->createBlock('icc_freeresources/adminhtml_freeresource_category_grid')->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    /**
     * export as MsExcel - action
     * @access public
     * @return void

     */
    public function exportExcelAction(){
        $fileName   = 'freeresource_categorys.xls';
        $content    = $this->getLayout()->createBlock('icc_freeresources/adminhtml_freeresource_category_grid')->getExcelFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    /**
     * export as xml - action
     * @access public
     * @return void

     */
    public function exportXmlAction(){
        $fileName   = 'freeresource_categorys.xml';
        $content    = $this->getLayout()->createBlock('icc_freeresources/adminhtml_freeresource_category_grid')->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    /**
     * check access
     * @access protected
     * @return bool

     */
    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('icc_freeresources/freeresource_categorys');
    }
}
