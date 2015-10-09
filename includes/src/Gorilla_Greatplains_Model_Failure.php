<?php


class Gorilla_Greatplains_Model_Failure
{
    //const XML_PATH_GREATPLAINS_GENERAL_ACTIVE = 'greatplains/general/active';
    const XML_PATH_GREATPLAINS_NEWORDERS_FAILURE_RECIPIENT_EMAIL = 'greatplains/new_orders/failure_notification_recipient_email';
    const XML_PATH_GREATPLAINS_NEWORDERS_FAILURE_SENDER_EMAIL = 'greatplains/new_orders/failure_notification_sender_email';
    const XML_PATH_GREATPLAINS_NEWORDERS_FAILURE_USE_STATUS = 'greatplains/new_orders/failure_use_status';
    const CODE_PROCESS_ORDER = 'process-order';


    public function sendFailedOrderNotifications()
    {
        // Get items from queue with failures at oer over the max attempts
        if($collection = $this->getFailuresCollection()) {
            $failureMessages = array();

            foreach($collection as $item) {
                $failureMessages[$item->getQueueId()] = $item->getShortDescription() ." (Queue ID: ".$item->getQueueId().", Created: " . $item->getCreatedAt();
                if($item->getLastAttempt()) $failureMessages[$item->getQueueId()] .= ", Last Attempt: ". $item->getLastAttempt().")";
            }
            $cntFailed = count($failureMessages);
            if($cntFailed) {
                $to = (string)Mage::getStoreConfig(self::XML_PATH_GREATPLAINS_NEWORDERS_FAILURE_RECIPIENT_EMAIL);
                $from = (string)Mage::getStoreConfig(self::XML_PATH_GREATPLAINS_NEWORDERS_FAILURE_SENDER_EMAIL);
                $subject = "Great Plains New Order Failure Notification: " . date('Y-m-d');
                Mage::log(__METHOD__ . ": Sending notification of $cntFailed failed orders");

                $message = "Magento was unable to send the following $cntFailed orders to Great Plains. \r\n\r\n";
                foreach($failureMessages as $queueId => $msg) {
                    $message .= "$msg\r\n";
                }

                // Send the email
                if($this->sendEmail($to, $message, null, $subject, $from)) {
                    // Update status
                    foreach($collection as $item) {
                        if(isset($failureMessages[$item->getQueueId()])) {
                            $item->changeStatus(Gorilla_Queue_Model_Queue::STATUS_ABORTED_NOTIFIED);
                        }
                    }
                }
            }
        }
    }

    public function getFailuresCollection()
    {
        $useStatus = (bool)(int)Mage::getStoreConfig(self::XML_PATH_GREATPLAINS_NEWORDERS_FAILURE_USE_STATUS);
        $collection = Mage::getModel('gorilla_queue/queue')->getCollection()
            ->addFieldToFilter('number_attempts', array('gteq' => Mage::helper('greatplains')->getNewOrderMaxSendAttempts()))
            ->addFieldToFilter('code', array('eq' => self::CODE_PROCESS_ORDER))
            ->addFieldToFilter('is_manually_removed', array(
                array('null' => 1),
                array('lt' => 1)
            ));
        if($useStatus) {
            $collection->addFieldToFilter('status', array('eq' => Gorilla_Queue_Model_Queue::STATUS_ABORTED));
        }
        else {
            $collection->addFieldToFilter('status', array('neq' => Gorilla_Queue_Model_Queue::STATUS_SUCCESS));
            $collection->addFieldToFilter('status', array('neq' => Gorilla_Queue_Model_Queue::STATUS_ABORTED_NOTIFIED));
        }

        return $collection;
    }


    public function sendEmail($to_email, $message_content, $to_name = null,  $subject = null, $from = null)
    {
        if( trim($message_content) == '' ) {
            throw new Exception('There must be content in this email message.');
            return false;
        }
        if( trim($to_email) == '' ) {
            throw new Exception('An email address to send this message to was not set. Please set a "to" email address.');
            return false;
        }
        if( is_null($from) ) {
            $from = 'no-reply@' . $_SERVER['SERVER_NAME'];
        }

        $email = Mage::getModel('core/email');
        $email->setBody($message_content);
        $email->setSubject($subject);
        $email->setToEmail($to_email);
        $email->setFromName('Website Queue');
        if( ! is_null($to_name)) {
            $email->setToName($to_name);;
        }

        $email->setFromEmail($from);

        return $email->send();
    }

}
