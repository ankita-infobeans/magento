<?php

class ICC_Ecodes_Adminhtml_Premium_GridController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Main index action
     *
     */
    public function indexAction()
    {
        //die('in index grid controller');
        $this->loadLayout();
        $this->_setActiveMenu('customer/premium');
        $this->renderLayout();
    }
    
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function massDeleteAction()
    {
        /* $serialIds = $this->getRequest()->getParam('serial');
        if(!is_array($serialIds)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select one or more serial numbers.'));
        } else {
            try {
                $serial = Mage::getModel('ecodes/downloadable');
                foreach ($serialIds as $serialId) {
                    // reset object
                    $serial->setData(array())
                           ->setOrigData();
                    
                    // delete it
                    $serial->load($serialId)
                           ->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were deleted.', count($serialIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        /* */
        $this->_redirect('*/*/index');
    }
    
    public function editAction()
    {
        $model = Mage::getModel('ecodes/download');
        if ($id = $this->getRequest()->getParam('id')) {
            $model->load($id);
        }

        Mage::register('_current_template', $model);
        
        $this->loadLayout();
        $this->_setActiveMenu('catalog/ecodes');
        
//                Mage::log('in edit grid controller edit action', null, 'admin-premium-edit.log');

    }
    
    /**
     * Check ACL permissions
     */
    public function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('ecodes/ecodes');
    }
    
}