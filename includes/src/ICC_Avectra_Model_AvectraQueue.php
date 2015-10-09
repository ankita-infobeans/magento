<?php

class ICC_Avectra_Model_AvectraQueue extends Mage_Core_Model_Abstract
{

    private $__max_number_attempts = null;
    private $__batch_size = 100;
    private $__email_parts = null;
    private $__aborted_queue_items = null;

    public function processBatch()
    {
        $qc = Mage::getModel('gorilla_queue/queue')
                ->getCollection()
                ->addFieldToFilter('status', Gorilla_Queue_Model_Queue::STATUS_OPEN)
                ->addFieldToFilter('model_class', 'icc_avectra/avectraQueue')
                ->addFieldToFilter('number_attempts', array(array('null' => NULL), array('lt' => $this->getMaxNumberAttempts())));

        $qc->getSelect()->limit($this->getBatchSize());

        foreach ($qc as $q) {
            $method = $q->getMethod();
            try {
                $this->$method(array('queue_item' => $q));
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    public function addUpdateUser($avectra_key)
    {
        $account = $this->getAccountInstance();
        $mage_customer = $account->getUserByAvectraKey($avectra_key);
        if ($mage_customer === false) {
            return false; // we want to know if this magento customer exists with this avectra key
        }
        $q = $this->getQueueInstance();
        $this->_logSoapToQueue($q, $account);
        $update_queue = $q->addToQueue($this->getMageModelClass(), 'updateUser', array('avectra_key' => $avectra_key), $code = 'customer-update');
        $update_queue->setShortDescription('Queued syncing Magento up to Avectra. Magento id: ' . $mage_customer->getId() . ' Name: ' . $mage_customer->getName())
                ->save();
        return $update_queue;
    }

    public function updateUser($queue)
    {
        
        $q = $queue['queue_item'];
        if (!$q->testAndSetLock()) {
            return false; // already processing
        }
        $queue_data = unserialize($q->getQueueItemData());
        $avectra_key = $queue_data['avectra_key'];
        $account = $this->getAccountInstance();
        $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
        if (!$account->hasAvectraConnection()) {
            $q->updateFailedAttempt();
            if ($q->getNumberAttempts() >= $this->getMaxNumberAttempts()) {
                $status = Gorilla_Queue_Model_Queue::STATUS_ABORTED;
            }
            $q->releaseLock($status);
            return;
        }
        try {
            $update_result = $account->updateUser($avectra_key);
            $this->_logSoapToQueue($q, $account);
            if ($update_result) {
                $q->updateSuccessfulAttempt();
                $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
            }
            $q->releaseLock($status);
        } catch (Exception $e) {
            $this->_logSoapToQueue($q, $account);
            $q->releaseLock($status);
            Mage::logException($e);
        }
        return $q;
    }

//    private function isSendEmail()
//    {
//        return (bool)($this->__q->getNumberAttempts() >= $this->__max_number_attempts);
//    }

    public function addUpdateAvectra($avectra_key, $error_message = null, $isRec = false)
    {
        if(!Mage::app()->getRequest()->getParam('ind_token')){
        $account = $this->getAccountInstance();
        
        $mage_customer = false;

        if ($isRec && $avectra_key) { // Assume avectra customer record number
            $user = $account->getUserByRecNo($avectra_key);
            if (is_object($user) && isset($user->WEBWebUserGetByRecno_CustomResult) && isset($user->WEBWebUserGetByRecno_CustomResult->Individual->ind_cst_key)) {
                $avectra_key = $user->WEBWebUserGetByRecno_CustomResult->Individual->ind_cst_key;
            }            
        }
        if ($avectra_key) {
            $mage_customer = $account->getUserByAvectraKey($avectra_key);
        }
        if ($mage_customer === false) {
            Mage::log('could not find a customer by avectra key: ' . $avectra_key, null, 'avectra-communication.log', true);
            return false;
        }

        if (!is_null($error_message)) {
            $error_message = ' Error message: ' . $error_message;
        }
        $q = $this->getQueueInstance();
        $this->_logSoapToQueue($q, $account);
        
            //Restricted Queue Entires While Add to Cart
            if (Mage::app()->getRequest()->getControllerName() != 'add' )
            {
                    $q->addToQueue($this->getMageModelClass(), 'updateAvectra', array('avectra_key' => $avectra_key), $code = 'update-avectra')
                    ->setShortDescription('Update Avectra with Magento changes: ' . $mage_customer->getFirstname() . ' ' . $mage_customer->getLastname() . '.' . $error_message)->save();
            }
        
        return $q;        
        }
    }    
      
    public function updateAvectra($queue)
    {
        $q = $queue['queue_item'];
        if (!$q->testAndSetLock()) {
            return false; // already processing
        }
        $q_data = unserialize($q->getQueueItemData());
        $avectra_key = $q_data['avectra_key'];
        $account = $this->getAccountInstance();
        $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
        if (!$account->hasAvectraConnection()) {
            $q->updateFailedAttempt();
            if ($q->getNumberAttempts() >= $this->getMaxNumberAttempts()) {
                $status = Gorilla_Queue_Model_Queue::STATUS_ABORTED;
            }
        }
        try {
            $update_result = $account->updateAvectra($avectra_key);
        } catch (Exception $e) {
            $update_result = false;
            $this->_logSoapToQueue($q, $account);
            $q->updateFailedAttempt(); // leave status open
            $q->releaseLock(Gorilla_Queue_Model_Queue::STATUS_OPEN);
            Mage::logException($e);
        }
        $this->_logSoapToQueue($q, $account);
        if ($update_result) {
            $q->updateSuccessfulAttempt();
            $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
        } else {
            $q->updateFailedAttempt(); // leave status open
            $q->releaseLock(Gorilla_Queue_Model_Queue::STATUS_OPEN);
        }
        $q->setStatus($status);
        $q->save();
        return $q;
    }

    public function addCreateUser($avectra_key, $error_message = 'None set')
    {
        // make sure the user does not exist. many queue items may be added for a single customer
        if (empty($avectra_key)) {
            return false;
        }
        $account = $this->getAccountInstance();
        $mage_customer = $account->getUserByAvectraKey($avectra_key);
        if ($mage_customer === false) {
            return false;
        }
        $this->_logSoapToQueue($q, $account);
        $q = $this->getQueueInstance();
        $q->addToQueue($this->getMageModelClass(), 'createUser', array('avectra_key' => $avectra_key), $code = 'create-user')
                ->setShortDescription('Trying to create the user with avectra key: ' . $avectra_key . ' Error message: ' . $error_message)->save();
        return $q;
    }

    public function createUser($queue)
    {

        $q = $queue['queue_item'];
        if (!$q->testAndSetLock()) {
            return false; // already processing
        }
        $q_data = unserialize($q->getQueueItemData());
        $avectra_key = $q_data['avectra_key'];
        $account = $this->getAccountInstance();
        $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
        if (!$account->hasAvectraConnection()) {
            $q->updateFailedAttempt();
            if ($q->getNumberAttempts() >= $this->getMaxNumberAttempts()) {
                $status = Gorilla_Queue_Model_Queue::STATUS_ABORTED;
            }
        }
        try {
            $create_result = $account->createNewUser($avectra_key);
        } catch (Exception $e) {
            $this->_logSoapToQueue($q, $account);
            $q->updateFailedAttempt();
            $q->releaseLock($status);
            Mage::logException($e);
        }
        if ($create_result) {
            $this->_logSoapToQueue($q, $account);
            $q->updateSuccessfulAttempt();
            $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
        }
        $q->releaseLock($status);
        return $q;
    }

    /**
     *
     * @return ICC_Avectra_Model_Account
     */
    private function getAccountInstance()
    {
        return Mage::getModel('icc_avectra/account');
    }

    private function getQueueInstance()
    {
        return Mage::getModel('gorilla_queue/queue');
    }

    private function getMageModelClass()
    {
        return 'icc_avectra/avectraQueue';
    }

    public function getMaxNumberAttempts()
    {
        if (is_null($this->__max_number_attempts)) {
            $this->__max_number_attempts = Mage::getStoreConfig('gorilla_queue/avectra/maxattempts');
        }
        return $this->__max_number_attempts;
    }

    public function getBatchSize()
    {
        return $this->__batch_size;
    }

    protected function _getEmailParts()
    {
        if (is_null($this->__email_parts)) {
            $this->__email_parts = array(
                'address' => Mage::getStoreConfig('gorilla_queue/avectra/abortedemail'),
                'name'    => 'ICC Admin',
            );
        }
        return $this->__email_parts;
    }

    protected function _getAbortedQueueItems()
    {
        if (is_null($this->__aborted_queue_items)) {
            $q_collection = Mage::getModel('gorilla_queue/queue')->getCollection();
            $q_collection->addFieldToFilter('number_attempts', array('gteq' => $this->_getMaxNumberAttempts()))
                    ->addFieldToFilter('status', Gorilla_Queue_Model_Queue::STATUS_ABORTED)
                    ->addFieldToFilter('model_class', 'icc_avectra/avectraQueue');
            $this->__aborted_queue_items = $q_collection;
        }
        return $this->__aborted_queue_items;
    }

    protected function sendEmail() // $body, $subj, $to, $from=null, $format=null
    {
        $email = Mage::getModel('core/email');
        $email->setBody($this->_getEmailBody());
        $email->setSubject($this->_getEmailSubject());
        $email->setToEmail($this->_getEmailTo('address'));
        $email->setToName($this->_getEmailTo('name'));
        $email->setFromEmail('no-reply@iccsafe.org');
        $email->setFromName('No Reply');

        return $email->send();
    }

    protected function _getMaxNumberAttempts()
    {
        if (is_null($this->__max_number_attempts)) {
            $this->__max_number_attempts = Mage::getStoreConfig('gorilla_queue/citation/maxattempts');
        }
        return $this->__max_number_attempts;
    }

    /**
     * @return string
     */
    protected function _getEmailBody()
    {
        $message_body = 'The following Queue Items have reached the maximum number of tries. They will no longer be processed automatically. Please log in to the administrations section of the Web site to address the issues.' . "\n\n";
        $q_collection = $this->_getAbortedQueueItems();
        foreach ($q_collection as $q) {
            $message_body .= "Queue Id: {$q->getId()} Description: {$q->getShortDescription()}\n";
        }
        return $message_body;
    }

    protected function _getEmailSubject()
    {
        // might have to select from the db?
        return 'ICC Automated Message';
    }

    /**
     * @param string $part
     * @return  array
     */
    protected function _getEmailTo($part = 'address')
    {
        $email_parts = $this->_getEmailParts();
        return $email_parts[$part];
    }

    public function notifyIccAbortedQueueItems()
    {
        if (!$this->_getAbortedQueueItems()->count()) {
            return; // no aborted queue items
        }
        if ($this->sendEmail()) {
            try {
                $items = $this->_getAbortedQueueItems();
                $items->getResource()->beginTransaction();
                $notified_status = Gorilla_Queue_Model_Queue::STATUS_ABORTED_NOTIFIED;
                foreach ($items as $q_item) {
                    $q_item->setStatus($notified_status);
                    $q_item->save();
                }
                $items->getResource()->commit();
            } catch (Exception $e) {
                $items->getResource()->rollBack();
            }
        }
    }

    public function addDeleteAvectraAddress($avectra_key, $customer_av_key, $e_message = '')
    {
        if (empty($avectra_key) || empty($customer_av_key)) {
            return false;
        }
        //current items for delete-avectra-address in the queue for this user
        $currentQueueItems = Mage::getModel('gorilla_queue/queue')
            ->getCollection()
            ->getSelect()
            ->where("code='delete-avectra-address'")
            ->where("queue_item_data like '%$avectra_key%'")
            ->query()
            ->fetchAll();

        //only add to queue if the item does not already exist
        if(empty($currentQueueItems)) {
            $account = $this->getAccountInstance();
            $q = $this->getQueueInstance();

            $this->_logSoapToQueue($q, $account);
            $q->addToQueue($this->getMageModelClass(), 'deleteAvectraAddress', array('avectra_key' => $avectra_key, 'customer_av_key' => $customer_av_key), $code = 'delete-avectra-address')
                ->setShortDescription('Trying to delete an address wich cxa avectra key: ' . $avectra_key . ' Error message: ' . $e_message)->save();
        }
    }

    public function deleteAvectraAddress($queue)
    {
        $q = $queue['queue_item'];
        if (!$q->testAndSetLock()) {
            return false; // already processing
        }
        $q_data = unserialize($q->getQueueItemData());
        $avectra_key = $q_data['avectra_key'];
        $customer_av_key = $q_data['customer_av_key'];
        $account = $this->getAccountInstance();
        $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
        if (!$account->hasAvectraConnection()) {
            $q->updateFailedAttempt();
        } else {
            try {
                $delete_result = $account->deleteAvectraAddress($avectra_key, $customer_av_key, false);
            } catch (Exception $e) {
                $this->_logSoapToQueue($q, $account);
                $q->updateFailedAttempt();
                $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
                $q->releaseLock($status);
                Mage::logException($e);
            }
            $this->_logSoapToQueue($q, $account);
            if ($delete_result) {
                $q->updateSuccessfulAttempt();
                $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
            } else {
                $q->updateFailedAttempt();
                $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
            }
        }
        if ($q->getNumberAttempts() >= $this->getMaxNumberAttempts()) {
            $status = Gorilla_Queue_Model_Queue::STATUS_ABORTED;
        }
        $q->releaseLock($status);
        return $q;
    }

    private function _logSoapToQueue($q, $account)
    {
        $client = $account->getAvComm()->getClient();
        if ($client instanceof SoapClient) {
            $q->setSoapRequest($account->getAvComm()->getClient()->__getLastRequest());
            $q->setSoapResponse($account->getAvComm()->getClient()->__getLastResponse());
        }
    }

}
