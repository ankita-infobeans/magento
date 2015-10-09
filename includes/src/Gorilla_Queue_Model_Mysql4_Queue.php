<?php

class Gorilla_Queue_Model_Mysql4_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        // this is the entity table - in the config xml the table is defined there
        $this->_init( 'gorilla_queue/queue', 'queue_id');
    }

    public function testAndSetLock(Gorilla_Queue_Model_Queue $queue)
    {
//        Mage::log(var_export($queue,true),null,"queue/queue.log");
        $writeAdapater = $this->_getWriteAdapter();
        $writeAdapater->beginTransaction();
        $select = $writeAdapater->select();
        $select->from($this->getMainTable(), 'status')
            ->where('queue_id = ?', $queue->getId())
            ->forUpdate();
        $all_results = $writeAdapater->fetchAll($select);
        $results = array_shift($all_results);
//        Mage::log(var_export($results,true),null,"queue/results.log");
        if(isset($results['status']) && $results['status'] !== Gorilla_Queue_Model_Queue::STATUS_PROCESSING)
        {   
            try{
                $writeAdapater->update(
                    $this->getMainTable(),
                    array('status' => Gorilla_Queue_Model_Queue::STATUS_PROCESSING),
                    array('queue_id = ?' => $queue->getId())
                );
                $writeAdapater->commit();
            }catch(Exception $e){
                Mage::logException($e);
                $writeAdapater->rollBack();
                return false;
            }
            return true;
        }else{
            $writeAdapater->rollBack();
            return false;
        }
    }

    public function releaseLock(Gorilla_Queue_Model_Queue $queue, $status)
    {   
        $writeAdapater = $this->_getWriteAdapter();
        $writeAdapater->beginTransaction();
        $select = $writeAdapater->select();
        $select->from($this->getMainTable(), 'status')
            ->where('queue_id = ?', $queue->getId())
            ->forUpdate();
        $results = $writeAdapater->fetchAll($select);
        if(isset($results['status']) && $results['status'] === Gorilla_Queue_Model_Queue::STATUS_PROCESSING)
        {
            try{
                $writeAdapater->update(
                    $this->getMainTable(),
                    array('status' => $status),
                    array('queue_id = ?' => $queue->getId())
                );
                $writeAdapater->commit();
                return true;
            }catch(Exception $e){
                Mage::logException($e);
                $writeAdapater->rollBack();
                return false;
            }
        }else{
            $writeAdapater->rollBack();
            return false;
        }
    }
}
