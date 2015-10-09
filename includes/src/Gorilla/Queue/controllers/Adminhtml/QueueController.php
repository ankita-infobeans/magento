<?php

class Gorilla_Queue_Adminhtml_QueueController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        $this->_redirect('*/*/list');
    }
    
    /**
     * Display grid
     */
    public function listAction()
    {
        $this->_getSession()->setFormData(array());
        $this->_title($this->__('Queue List'));
        $this->loadLayout();
        $this->_setActiveMenu('queue');
        
        $this->renderLayout();
    }
    
    /**
     * Check ACL permissions
     * @return
     */
    public function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('gorilla_queue/queue');
    }
    
    /**
     * Grid action for ajax request
     */
    public function gridAction()
    {
        $this->loadLayout()->renderLayout();
    }
    
    public function newAction()
    {
        $this->_forward('edit');
    }
    
    public function editAction()
    { 
        $model = Mage::getModel('gorilla_queue/queue');
        Mage::register('current_queue', $model);
        $id = $this->getRequest()->getParam('id');

        try{
            if($id)
            {
                if(!$model->load($id, 'queue_id')->getQueueId())
                {
                    Mage::throwException($this->__('No record found with id "%s"', $id));
                }
            }
            
            if($model->getId())
            {
                $pageTitle = $this->__('Edit %s (%s)', $model->getName(), $model->getType());
            } else {
                $pageTitle = $this->__('New Queue Item');
            }

            $this->loadLayout();
            $this->renderLayout();
        }catch(Exception $e){
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/*/list');
        }
    }
    
    /**
     * Process a form
     */
    public function saveAction()
    {
        if($data = $this->getRequest()->getPost()) {
            $this->_getSession()->setFormData($data);
            $model = Mage::getModel('gorilla_queue/queue');
            $id = $this->getRequest()->getParam('id');
            
            try{
                if($id)
                {
                    $model->load($id);
                }

                $model->addData($data);
                $model->save();
                
                $this->_getSession()->addSuccess(
                    $this->__('Queue was successfully saved.')
                );
                $this->_getSession()->setFormData(false);
                
                if($this->getRequest()->getParam('back'))
                {
                    $params = array('id' => $model->getQueueId());
                    $this->_redirect( '*/*/edit', $params);
                }else{
                    $this->_redirect('*/*/list');
                }
            }catch(Exception $e){
                $this->_getSession()->addError($e->getMessage());
                if($model && $model->getQueueId())
                {
                    $this->_redirect('*/*/edit', array('id' => $model->getQueueId()));
                }else{
                    $this->_redirect('*/*/new');
                }
            }
            return;
        }
        
        $this->_getSession()->addError($this->__('No data found to save'));
        $this->_redirect('*/*');
    }
    
   
    
    public function processAction()
    {
        $f = new Zend_Filter_Int();
        $id = $f->filter($this->getRequest()->getParam('id'));
        if(!$id)
        {
            $this->getSession()->addError('No id sent to find queue to process.');
            $this->_redirect('*/*/list');
        }
        $queue = Mage::getModel('gorilla_queue/queue');
        $queue->load($id, 'queue_id');
        $queue->process();

        $this->_redirect('*/*/list');
    }
    
    public function resetAction()
    {
        $id =$this->getRequest()->getParam('id');
        if(!$id)
        {
            $this->getSession()->addError('No id sent to find queue to process.');
            $this->_redirect('*/*/list');
        }
        $model = Mage::getModel('gorilla_queue/queue');
        try{
            $model->load($id, 'queue_id');
            $model->setNumberAttempts(0);
            $model->save();
            $this->_getSession()->addSuccess('Successfully reset the queue');
        }catch(Exception $e){
            $this->_getSession()->addError('Could not reset the queue. ' . $e->getMessage());
        }
        $this->_redirect('*/*/list');
    }
    
    public function removeAction()
    {
        $id =$this->getRequest()->getParam('id');
        if(!$id)
        {
            $this->getSession()->addError('No id sent to find queue to process.');
            $this->_redirect('*/*/list');
        }
        $model = Mage::getModel('gorilla_queue/queue');

        try{
            $model->load($id, 'queue_id');
            $model->setIsManuallyRemoved(true);
            $model->save();
            $this->_getSession()->addSuccess('Successfully removed the item from the queue');
        }catch(Exception $e){
            $this->_getSession()->addError('Could not remove the item from the queue. ' . $e->getMessage());
        }

        $this->_redirect('*/*/list');
    }
}