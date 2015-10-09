<?php
class ICC_CheckAndNotify_Model_Observer
{
    const EMAIL_TO_PATH = 'check_and_nofity_email/email/email_field';

    public function checkDownloadableLinks(){
	/** Added log for cron takinng.	added by anil 28 jul START **/
	$controllrName = Mage::app()->getRequest()->getControllerName();
	$actionName = Mage::app()->getRequest()->getActionName();
	$currDate = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
	$fileName = date("Y-m-d", Mage::getModel('core/date')->timestamp(time()));
	Mage::log("Controller Name : CheckAndNotify/Observer , Action Name : checkDownloadableLinks, Start Time : $currDate",null,$fileName);
        /** END **/
        $resource = Mage::getModel('core/resource');
        $connection = $resource->getConnection('core_read');

        $sinceDate = date($connection::TIMESTAMP_FORMAT, strtotime(now() . '-45 day'));

        $query = $connection->select()
            ->from(array(
                'order' => $resource->getTableName("sales/order")),
                array('order.increment_id', 'order.customer_firstname', 'order.customer_lastname'))
            ->joinLeft(array(
                'order_item' => $resource->getTableName("sales/order_item")),
                'order.entity_id = order_item.order_id',
                '')
            ->joinLeft(array(
                'dlpi' => $resource->getTableName("downloadable/link_purchased_item")),
                'dlpi.order_item_id = order_item.item_id',
                '')
            ->where('order_item.product_type = "downloadable"')
            ->where('dlpi.item_id IS NULL')
            ->where('order.created_at > ?', $sinceDate);

        $result = $connection->fetchAll($query);
        $this->_sendNotification($result);
	/** Added log for cron takinng. added by anil 28 jul START **/
	$currDate = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        Mage::log("Controller Name :  CheckAndNotify/Observer , Action Name : checkDownloadableLinks , End Time : $currDate",null,$fileName);
	/** End **/
    }

    protected function _sendNotification($result){
        $subject = 'Cronjob report for missed links on the client\'s eCodes pages.';
        $from     = 'ashrivastav@iccsafe.org';
        $to_email = Mage::getStoreConfig(self::EMAIL_TO_PATH);
        if (strpos($to_email, ',')){
            $to_email = explode(',', $to_email);
        }
	
        $message_content = $this->getMessage($result);
        
        $email = Mage::getModel('core/email');
        $email->setBody($message_content);
        $email->setSubject($subject);
        $email->setToEmail($to_email);
        $email->setFromName('Website Queue');

        $email->setFromEmail($from);

        return $email->send();
    }

    public function getMessage($main_content)
    {
        if (empty($main_content)){
            return 'No missed links were found.';
        }

        $message = 'The following clients have missed downloadable links:';
        foreach($main_content as $key => $link){
            $message .= "\r\n";
            $message .= $key + 1 . ') ' . $link['customer_firstname'] . ' ' . $link['customer_lastname'] . ', order #' . $link['increment_id'];
        }
        return $message;
    }
}
