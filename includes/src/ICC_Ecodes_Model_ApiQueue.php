<?php
// ICC_Ecodes_Model_EcodesQueue


// crons : processAppendProduct
// 


class ICC_Ecodes_Model_ApiQueue extends Mage_Core_Model_Abstract
{
    private $__max_number_attempts = null;
    private $__aborted_queue_items = null;
    private $__email_parts = null;
    private $__email_type = 'icc_queue_notification';
    private $__customer = null;
    
    protected function _getEmailParts()
    {
        if(is_null($this->__email_parts))
        {
            $this->__email_parts = array(
                'address' => Mage::getStoreConfig('gorilla_queue/citation/abortedemail'),
                'name'    => 'ICC Admin',
            );
        }
        return $this->__email_parts;
    }

    protected function _getMaxNumberAttempts()
    {
        if(is_null($this->__max_number_attempts))
        {
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
        foreach($q_collection as $q)
        {
            $message_body .= "Queue Id: {$q->getId()} Description: {$q->getShortDescription()}\n";
        }
        return $message_body;
    }
    
    protected function _getCreateMasterUserEmailBody()
    {
        $c = $this->__customer;
        $message_body = "Dear {$c->getFirstname()},\n\nWe are contacting you with this email to let you know that our service has been restored. Please login to your ICC account to update your PremiumACCESS Subscriptions. \nPlease visit http://iccsafe.org/account/login\n\nThank you,\nICC Support";   
        
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
    
    /** 
     * @return  Gorilla_Queue_Model_Mysql4_Queue_Collection 
     */
    protected function _getAbortedQueueItems()
    {
        if(is_null($this->__aborted_queue_items))
        {
            $q_collection = Mage::getModel('gorilla_queue/queue')->getCollection();
            $q_collection->addFieldToFilter('number_attempts', array('gteq' => $this->_getMaxNumberAttempts() ))
                    ->addFieldToFilter('status', Gorilla_Queue_Model_Queue::STATUS_ABORTED)
                    ->addFieldToFilter('model_class', 'ecodes/apiQueue');
            $this->__aborted_queue_items = $q_collection;
        }
        return $this->__aborted_queue_items;
    }
    
    
    public function processAppendProduct ()
    {
        $q_collection = Mage::getModel('gorilla_queue/queue')->getCollection();
        $q_collection->addFieldToFilter('status', array( 'in' => array(
                                                            Gorilla_Queue_Model_Queue::STATUS_OPEN,
                                                            Gorilla_Queue_Model_Queue::STATUS_PROCESSING
                                        )))
                ->addFieldToFilter('number_attempts', array( 'lt' => $this->_getMaxNumberAttempts() ))
                ->addFieldToFilter('code', 'AppendProduct');

        foreach($q_collection as $q )
        {
            $queue = array( 'queue_item' => $q );
       
            $q = $queue['queue_item'];
            $api_test = Mage::getModel('ecodes/api')->hasConnection();

            if( ! $api_test)
            {
                $q->updateFailedAttempt();
                if($q->getNumberAttempts() >= $this->_getMaxNumberAttempts())
                {
                    $q->setStatus(Gorilla_Queue_Model_Queue::STATUS_ABORTED)->save();
                }
                return;
            }

            // get data for task
            $q_data =  unserialize( $q->getQueueItemData() );
            $function_args = new SimpleXMLElement($q_data['xml']);

            if( ! $q->testAndSetLock())
            {
                return; // already processing this q item
            }
            $api = Mage::getModel('ecodes/api');
            $api->setAddToQueue(false); // when processing a queue item we don't want the functionality of adding failed attempts to the queue

            // use System > Configuration values in case the reason it failed was bad values the first time
            $xml_guid     = $api->createGuid();
            $xml_muser    = $api->getLogin();
            $xml_mpass    = $api->getPassword();
            $xml_portalid = $api->getPortalId();

            $xml = '<Params>';
//            $xml .= '<Guid>'     . (empty($xml_guid)     ? $function_args->Guid     : $xml_guid)     . '</Guid>';
//            $xml .= '<MUser>'    . (empty($xml_muser)    ? $function_args->MUser    : $xml_muser)    . '</MUser>';
//            $xml .= '<MPass>'    . (empty($xml_mpass)    ? $function_args->MPass    : $xml_mpass)    . '</MPass>';
//            $xml .= '<PortalId>' . (empty($xml_portalid) ? $function_args->PortalId : $xml_portalid) . '</PortalId>';
            $xml .= '<Guid>'     . (empty($function_args->Guid)     ? $xml_guid     : $function_args->Guid)     . '</Guid>';
            $xml .= '<User>'     . (empty($function_args->MUser)    ? $xml_muser    : $function_args->MUser) . '</User>';
            $xml .= '<PortalId>' . (empty($function_args->PortalId) ? $xml_portalid : $function_args->PortalId) . '</PortalId>';
            $xml .= '<Products>';
            foreach ($function_args->Products->Product as $product)
            {
                $xml .= '  <Product>';
                $xml .= '    <Code>' . $product->Code . '</Code>';
                $xml .= '    <ExpireDate>' . $product->ExpireDate . '</ExpireDate>';
                $xml .= '  </Product>';
            }
            $xml .= '</Products>';
            $xml .= '</Params>';

            $call_results = $api->makeApiCall('AppendProduct', $xml);
            if($call_results['success'])
            {
                $q->updateSuccessfulAttempt();
                $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
            }
            else
            {
                $q->updateFailedAttempt();
                $q->setShortDescription($call_results['message']);
                $q->save();
                $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
                if($q->getNumberAttempts() >= $this->_getMaxNumberAttempts())
                {
                    $status = Gorilla_Queue_Model_Queue::STATUS_ABORTED;
                }
            }
            $q->changeStatus( $status );
            $q->releaseLock( $status );
        }

    }

    public function processApiQueue() {
        $q_collection = Mage::getModel('gorilla_queue/queue')->getCollection();
        $q_collection->addFieldToFilter('number_attempts', array( 'lt' => $this->_getMaxNumberAttempts() ))
                ->addFieldToFilter('status', Gorilla_Queue_Model_Queue::STATUS_OPEN )
                ->addFieldToFilter('code', 'AppendProduct');
        foreach($q_collection as $q )
        {
            $this->processApi($q);
        }
    }

    public function processApi( $queue ) // primarily used for apend product
    {
        
        $q = $queue['queue_item'];
        $api_test = Mage::getModel('ecodes/api')->hasConnection();

        
        if( ! $api_test)
        {
            $q->updateFailedAttempt();
            if($q->getNumberAttempts() >= $this->_getMaxNumberAttempts())
            {
                $q->setStatus(Gorilla_Queue_Model_Queue::STATUS_ABORTED)->save();
            }
            return;
        }
        
        $function_args =  unserialize( $q->getQueueItemData() );
        //die($function_args);
        if( ! $q->testAndSetLock())
        {
            return; // already processing this q item
        }
        $api = Mage::getModel('ecodes/api');
        $api->setAddToQueue(false); // when processing a queue item we don't want the functionality of adding failed attempts to the queue
        
        
        $xml_obj = simplexml_load_string($function_args['xml']);
        $xml_obj->Guid = $api->createGuid(); // get a new guid
        $xml_parts = explode( "\n", $xml_obj->asXML() ); // trying to get rid of the <?xml> line - for some reason (string) $xml_obj->Params wasn't working
        $call_results = $api->makeApiCall($function_args['function'], $xml_parts[1]);
        if($call_results['success'])
        {
            $q->updateSuccessfulAttempt();
            $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
        }
        else
        {
            $q->updateFailedAttempt();
            $q->setShortDescription($call_results['message']);
            $q->save();
            $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
            if($q->getNumberAttempts() >= $this->_getMaxNumberAttempts())
            {
                $status = Gorilla_Queue_Model_Queue::STATUS_ABORTED;
            }
        }
        $q->releaseLock( $status );
    }
        
    public function processNotifiyUserCreateAccountQueueItem($queue) // is icc connect back up? tell user
    {
        $api_test = Mage::getModel('ecodes/api')->hasConnection();
        $q = $queue['queue_item'];
        if( ! $api_test)
        {
            $q->updateFailedAttempt();
            if($q->getNumberAttempts() >= $this->_getMaxNumberAttempts())
            {
                $q->setStatus(Gorilla_Queue_Model_Queue::STATUS_ABORTED)->save();
            }
            return;
        }
        if( ! $q->testAndSetLock())
        {
            return; // already processing
        }
        $q_data = unserialize($q->getQueueItemData());
        $customer = Mage::getModel('customer/customer')->load($q_data['customer_id']);
        if( ! $customer->getId() )
        {
            $q->updateFailedAttempt();
            $q->releaseLock(Gorilla_Queue_Model_Queue::STATUS_ABORTED); // if we can't find the customer something went unrecoverably wrong anyway
            return;
        }

        $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
        if( $this->sendTransactionalEmail($customer) )
        {   
            $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
        }
        $q->incrementAttempts();
        $q->releaseLock($status);
        // it wasn't setting status for some reason
        $q->setStatus($status);
        $q->save();
    }
    
    /** @param integer $customer_id */
    public function processNewMasterUser($customer_id)
    {
        // get all of the items that need to be associated
        $q_collection = Mage::getModel('gorilla_queue/queue')->getCollection();
        $q_collection->addFieldToFilter('code', 'customer_id_' . $customer_id)
                ->addFieldToFilter('status', Gorilla_Queue_Model_Queue::STATUS_OPEN )
                ->addFieldToFilter('number_attempts', array('lt' => $this->_getMaxNumberAttempts()) )
                ->addFieldToFilter('method', 'processCreatedNewMasterUserQueueItem'); 

        foreach($q_collection as $q )
        {
            $queue = array(
                'queue_item' => $q // model after the admin queue type
            );
            $this->processCreatedNewMasterUserQueueItem($queue);
        }
    }
    
    // observer
    public function processCreatedNewMasterUserQueueItem($queue)
    {
        $api = Mage::getModel('ecodes/api');
        $api_test = $api->hasConnection();
        $q = $queue['queue_item'];
        
        if( ! $api_test)
        {
            $q->updateFailedAttempt();
            if($q->getNumberAttempts() >= $this->_getMaxNumberAttempts())
            {
                $q->setStatus(Gorilla_Queue_Model_Queue::STATUS_ABORTED)->save();
            }
            return;
        }
        
        if( ! $q->testAndSetLock() )
        {
            return; // already being processed
        }
        
        $q_info = unserialize($q->getQueueItemData());
        $customer = Mage::getModel('customer/customer')->load($q_info['customer_id']);
        $master_username = $customer->getEcodesMasterUser();
        $master_password = $customer->getEcodesMasterPass();
        if( empty( $master_username ) || empty( $master_password ))
        {
            $q->updateFailedAttempt();
            $q->releaseLock(Gorilla_Queue_Model_Queue::STATUS_OPEN);
            return; // should not proceed
        }
        
        // each item is added as a separate queue item
        // if the following fails it will simply add a different Queue item to process on it's own
        $result = $api->appendProduct( $master_username, $master_password, $q_info['subscription_sku'], $q_info['subscription_expiration']);
        if($result['success'])
        {
            $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
            $q->incrementAttempts();
        } else {
            $q->updateFailedAttempt();
            $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
        }        
        $q->releaseLock($status);
    }
    
    // cron
    public function processMayHaveMasterUserQueueItem($queue)
    {
        // do not lock the q item. Allow one of the two called functions to handle that
        $api = Mage::getModel('ecodes/api');
        $api_test = $api->hasConnection();
        $q = $queue['queue_item'];
        if( ! $api_test )
        {
            $q->updateFailedAttempt();
            if($q->getNumberAttempts() >= $this->_getMaxNumberAttempts())
            {
                $q->setStatus(Gorilla_Queue_Model_Queue::STATUS_ABORTED)->save();
            }
            return;
        }
        $info = unserialize($q->getQueueItemData());
        $customer = Mage::getModel('customer/customer')->load($info['customer_id']);
        $customer_id = $customer->getId();
        if( ! $customer_id )
        {   
            $q->updateFailedAttempt();
            $q->setStatus( Gorilla_Queue_Model_Queue::STATUS_ABORTED );
            $q->setShortDescription('Magento customer id does not exist')->save();
            return;
        }
        $master_username = $customer->getEcodesMasterUser();
        $master_password = $customer->getEcodesMasterPass();
        $api = Mage::getModel('ecodes/api');
        $result = $api->checkMasterCredentials($master_username, $master_password);
        if($result['success'])
        {
            $subscriptions = Mage::getModel('ecodes/premiumsubs')->getCollection()
                    ->addFieldToFilter('customer_id', $info['customer_id'])
                    ->addFieldToFilter('expiration', array('gt' => date('Y-m-d') ));
            $api->setAddToQueueFlag(false);
            $num_errors = 0;
            foreach($subscriptions as $s)
            {
                $apend_result = $api->appendProduct( $master_username, $master_password, $s->getSku(), $s->getExpiration() );
                if( ! $apend_result['success'] ) $num_errors += 1; 
            }
            if($num_errors > 0)
            {
                $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;
            } else {
                $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
            }
            $q->incrementAttempts();
            $q->releaseLock($status);
        }
        else
        {
            /*** start. artem. gorilla. ticket 2013012410000204 ***/
            //$api->createCompany($info['login'],$info['password'],$info['first'],$info['last'],$info['email']);
            /*** end. artem. gorilla. ticket 2013012410000204 ***/
            $customer->setEcodesMasterUser($info['login']);
            $customer->setEcodesMasterPass(Mage::helper('ecodes')->encryptPassword($info['password']));
            $customer->save();
            $this->processNotifiyUserCreateAccountQueueItem($queue);
        }
    }
    
    // cron
    public function processMayHaveMasterUser()
    {
        // get all of the items that need to be associated
        $q_collection = Mage::getModel('gorilla_queue/queue')->getCollection();
        $q_collection->addFieldToFilter('status', Gorilla_Queue_Model_Queue::STATUS_OPEN )
                ->addFieldToFilter('number_attempts', array( 'lt' => $this->_getMaxNumberAttempts() ))
                ->addFieldToFilter('method', 'processMayHaveMasterUserQueueItem');
        foreach($q_collection as $q )
        {
            $queue = array(
                'queue_item' => $q // model after the admin queue type
            );
            $this->processMayHaveMasterUserQueueItem($queue);
        }
    }
    
    public function notifyIccAbortedQueueItems()
    {
        if(!$this->_getAbortedQueueItems()->count())
        {
            return; // no aborted queue items
        }
        if($this->sendEmail())
        {
            try{
                $items = $this->_getAbortedQueueItems();
                $items->getResource()->beginTransaction();
                $notified_status = Gorilla_Queue_Model_Queue::STATUS_ABORTED_NOTIFIED;
                foreach($items as $q_item)
                {
                    $q_item->setStatus($notified_status);
                    $q_item->save();
                }
                $items->getResource()->commit();
            }
            catch( Exception $e )
            {
                $items->getResource()->rollBack();
            }
        }
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
    
    public function sendTransactionalEmail($customer)
    {
        try{
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            $mail_template = Mage::getModel('core/email_template');
            $template_config_path = 'iccconnect_options/configfields/citation_running_template';
            $template = Mage::getStoreConfig($template_config_path, Mage::app()->getStore()->getId());

            $mail_template->setDesignConfig( array('area' => 'frontend', 'store' => Mage::app()->getStore()->getId() ))
                ->sendTransactional(
                        $template,
                        Mage::getStoreConfig( Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, Mage::app()->getStore()->getId() ),
                        $customer->getEmail(),
                        $customer->getName(),
                        array(
                            'customer'  => $customer,
                        )
                );
            $translate->setTranslateInline(true); 
            return $this;
        } catch (Exception $e) {
            return false;
        }
    }

}