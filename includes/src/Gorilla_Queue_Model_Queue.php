<?php

class Gorilla_Queue_Model_Queue extends Mage_Core_Model_Abstract
{
    const STATUS_OPEN = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_SUCCESS = 3;
    const STATUS_ABORTED = 4;
    const STATUS_ABORTED_NOTIFIED = 5;
    const NO_OF_ATTEMPTS = 20;

    protected function _construct()
    {
        $this->setIdFieldName('queue_id');
        $this->_init('gorilla_queue/queue', 'queue_id');
    }
    
    /**
     * Get list of process status options
     *
     * @return array
     */
    public function getStatusesOptions()
    {
        return array(
            self::STATUS_SUCCESS            => Mage::helper('gorilla_queue')->__('Successful'),
            self::STATUS_PROCESSING         => Mage::helper('gorilla_queue')->__('Processing'),
            self::STATUS_OPEN               => Mage::helper('gorilla_queue')->__('Open'),
            self::STATUS_ABORTED            => Mage::helper('gorilla_queue')->__('Aborted'),
            self::STATUS_ABORTED_NOTIFIED   => Mage::helper('gorilla_queue')->__('Aborted And ICC Notified'),
        );
    }
    
    public function addToQueue($modelClass, $action, $queueItemData = array(), $code = '')
    {
        if(is_null($modelClass) || is_null($action))
        {
            throw new Exception(
                'Please supply both a Magento model URI and the action to process in this queue item.', E_ERROR
            );
            return false;
        }
        $this->setStatus(self::STATUS_OPEN);
        $this->setCode($code);
        $this->setQueueItemData(serialize($queueItemData));
        $this->setModelClass($modelClass);
        $this->setMethod($action);
        $this->setCreatedAt($this->getNow());
        $this->setLastAttempt(null);
        $this->setNumberAttempts(0);
        $this->save();
        
        return $this;
    }

    public function testAndSetLock()
    {
        return $this->getResource()->testAndSetLock($this);
    }

    public function releaseLock($status)
    {   
        return $this->getResource()->releaseLock($this, $status);
    }

    /**
     * @param array $data
     */
    public function update(array $data = array())
    {
        if(!empty($data))
        {
            $this->addData($data);
        }
        $this->setNumberAttempts(($this->getNumberAttempts() + 1));
        $this->setLastAttempt($this->getNow());
        $this->incrementAttempts();
        $this->save();
    }
    
    public function changeStatus($status)
    {
        $this->setStatus($status);
        $this->save();
        return $this;
    }

    private function getNow()
    {
        $now = new DateTime('now');
        return $now->format('Y-m-d H:i:s');
    }
    
    public function incrementAttempts()
    {
        $this->setNumberAttempts(($this->getNumberAttempts() + 1));
        $this->save();
        
        $attempts = Mage::getStoreConfig('greatplains/new_orders/max_send_attempts');
        
        if($this->getNumberAttempts() >= $attempts)
        {
            $this->changeStatus(self::STATUS_ABORTED);
        }
        
        return $this;
    }
    
    public function updateFailedAttempt()
    {
        // could be in a transactional
        $this->incrementAttempts();
        $this->updateLastAttempt();
        return $this;
    }
    
    public function updateLastAttempt()
    {
        $this->setLastAttempt($this->getNow());
        $this->save();
        return $this;
    }
    
    public function updateSuccessfulAttempt()
    {
        // could be in a transactional
        $this->incrementAttempts();
        $this->updateLastAttempt();
        $this->changeStatus(self::STATUS_SUCCESS);
        return $this;
    }
    
    /**
     * Process a Queue Task. Uses the callback model and method found in the task record.
     */
    public function process()
    {
        try {
            $modelClass = (string) $this->getModelClass();
            $method = (string) $this->getMethod();
            $queueItemData = unserialize($this->getQueueItemData());
            $model = Mage::getModel($modelClass);
            $queueEvent = new Varien_Object($queueItemData);
            $queueEvent->setData('queue_item', $this);

            if(method_exists($model, $method))
            {
                call_user_func_array(array($model, $method), array($queueEvent));
            }else{
                $exception = new Gorilla_Queue_Exception(sprintf("%s is not a valid method.", $method));
                Mage::logException($exception);
            }
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }
}


