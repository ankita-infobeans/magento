<?php
/**
 * @copyright   Copyright (c) 2009-2012 Amasty (http://www.amasty.com)
 */ 
class Amasty_Acart_Model_Schedule extends Mage_Core_Model_Abstract
{
    const EMAIL_TEMPLATE_XML_PATH = 'amacart/template/main_template';
    const NAME_XML_PATH = 'amacart/template/name';
    const EMAIL_XML_PATH = 'amacart/template/email';
    const CC_XML_PATH = 'amacart/template/cc';
    
    const LAST_EXECUTED_PATH = 'amacart/common/last_executed';
    
    
    const DEFAULT_TEMPLATE_CODE = 'Amasty: Abandoned Cart Reminder';
    
    protected static $_actualGap = 172800; //2 days
    protected static $_abandonedGap = 600; //10 minutes

    public function _construct()
    {
        $this->_init('amacart/schedule');
    }
        
    function getDays(){
        return $this->getDelayedStart() > 0 ? 
                floor($this->getDelayedStart() / 24 / 60 / 60) :
                NULL;
    }
    
    function getHours(){
        $days = $this->getDays();
        $time = $this->getDelayedStart() - ($days * 24 * 60 * 60);
        
        return $time > 0 ? 
                floor($time / 60 / 60) :
                NULL;
    }
    
    function getMinutes(){
        $days = $this->getDays();
        $hours = $this->getHours();
        $time = $this->getDelayedStart() - ($days * 24 * 60 * 60) - ($hours * 60 * 60);
        
        return $time > 0 ? 
                floor($time / 60) :
                NULL;
    }
    
    
    function run(){
        
        $this->_prepare();
        $this->_process();
        $this->_checkCanceledQuotes();
    }
    
    protected function _sendEmail($history){
        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));
        
        $mail = new Zend_Mail('utf-8');
        
        $mail->addTo($history->getEmail());
        
        $mail->setBodyHTML($history->getBody());
        
        $mail->setSubject('=?utf-8?B?' . base64_encode($history->getSubject()) . '?=');
        
        $senderName = Mage::getStoreConfig(self::NAME_XML_PATH, $history->getStoreId());//Mage::getStoreConfig('trans_email/ident_general/name'); 
        
        $senderEmail = Mage::getStoreConfig(self::EMAIL_XML_PATH, $history->getStoreId());//Mage::getStoreConfig('trans_email/ident_general/email');

        $cc = Mage::getStoreConfig(self::CC_XML_PATH, $history->getStoreId());
        
        $mail->addCc($cc);
        
        $mail->setFrom($senderEmail, $senderName);
        
        try {
            if ((string)Mage::getConfig()->getNode('modules/Aschroder_SMTPPro/active') == 'true') {
                $transport = Mage::helper('smtppro')->getTransport();
                $mail->send($transport);
            } else {
                $mail->send();
            }
        }
        catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        
        return true;
    }
    
    protected function _process(){
        $resource = Mage::getSingleton('core/resource');
        
        $historyCollection = Mage::getModel('amacart/history')->getCollection();
        
        $historyCollection->addQuoteData();
        
        $historyCollection->addFieldToFilter('scheduled_at', array('lteq' => $this->date(time())));
        $historyCollection->addFieldToFilter('status', array('eq' => Amasty_Acart_Model_History::STATUS_PENDING));
        
        foreach($historyCollection as $history){
            $this->processHistoryItem($history);
        }
    }
    
    function processHistoryItem($history){
        $history->setExecutedAt($this->date(time()));
        $history->setStatus(Amasty_Acart_Model_History::STATUS_PROCESSING);
        $history->save();

        if ($this->_sendEmail($history)){
            $history->setFinishedAt($this->date(time()));
            $history->setStatus(Amasty_Acart_Model_History::STATUS_SENT);
            $history->save();
        }
    }
    
    protected function _cancelQuote($quoteId, $historyId, $status, $override = FALSE){
        
        $canceled = Mage::getModel('amacart/canceled')->load($quoteId, 'quote_id');

        if ($override || $canceled->getId() == NULL){
            $canceled->setData(array(
                'canceled_id' => $canceled->getId(),
                'quote_id' => $quoteId,
                'history_id' => $historyId,
                'created_at' => $this->date(time()),
                'reason' => $status
            ));
            $canceled->save();
        }
        
        return $canceled;
        
    }
    
    protected function _checkCanceledQuotes(){
        
        /*
         * CHECK ELAPSED QUOTES
         */
        $resource = Mage::getSingleton('core/resource');
        
        $pendingCollection = Mage::getModel('amacart/history')->getCollection();
        
        $pendingCollection->addFieldToFilter('status', array('eq' => Amasty_Acart_Model_History::STATUS_PENDING));
        $pendingIds = array();
        
        foreach($pendingCollection->getData() as $item){
            $pendingIds[] = $item['quote_id'];
        }
        
        $canceledCollection = Mage::getModel('amacart/history')->getCollection();
        
        $canceledCollection->addCanceledData();
        
        if (count($pendingIds) > 0){
            $canceledCollection->addFieldToFilter('main_table.quote_id', array('nin' => $pendingIds));
        }
        
        foreach($canceledCollection as $history){
            $canceled = $this->_cancelQuote(
                $history->getQuoteId(),
                NULL,
                Amasty_Acart_Model_Canceled::REASON_ELAPSED,
                FALSE
            );
            
            $history->setCanceledId($canceled->getId());
            $history->save();
        }
        /*
         * CHECK BLACK LIST QUOTES
         */
        $blacklistCollection = Mage::getModel('amacart/history')->getCollection();
        $blacklistCollection->addBlacklistData();
        
        $blacklistCollection->addFieldToFilter('status', array('eq' => Amasty_Acart_Model_History::STATUS_PENDING));
        
        foreach($blacklistCollection as $history){
            $canceled = $this->_cancelQuote(
                $history->getQuoteId(),
                NULL,
                Amasty_Acart_Model_Canceled::REASON_BALCKLIST,
                TRUE
            );
            
            $history->setStatus(Amasty_Acart_Model_History::STATUS_BLACKLIST);
            $history->setCanceledId($canceled->getId());
            $history->save();
        }
    }
    
    protected function _getQuoteCollection(){
        
        $gt = $this->getLastExecuted();
        $lt = time() - self::$_abandonedGap;
        
        $this->setLastExecuted($lt);
        
        $resource = Mage::getSingleton('core/resource');
        
        $quoteCollection = Mage::getModel('sales/quote')->getCollection();

        $quoteCollection->getSelect()->joinLeft(
            array('canceled' => $resource->getTableName('amacart/canceled')), 
            'main_table.entity_id = canceled.quote_id', 
        array('canceled.canceled_id')
        );

        $quoteCollection->getSelect()->joinLeft(
            array('quote2email' => $resource->getTableName('amacart/quote2email')), 
            'main_table.entity_id = quote2email.quote_id', 
            array('ifnull(main_table.customer_email, quote2email.email) as target_email')
        );

        $quoteCollection->getSelect()->joinLeft(
            array('history' => $resource->getTableName('amacart/history')), 
            'main_table.entity_id = history.quote_id', 
            array('history.quote_id')
        );
        
        $quoteCollection->getSelect()->group('main_table.entity_id');
        
        $quoteCollection->addFieldToFilter('history.history_id', array('null' => true));
        $quoteCollection->addFieldToFilter('canceled.canceled_id', array('null' => true));
        $quoteCollection->addFieldToFilter('main_table.updated_at', array('gt' => $this->date($gt)));
        $quoteCollection->addFieldToFilter('main_table.updated_at', array('lt' => $this->date($lt)));
        $quoteCollection->addFieldToFilter('main_table.is_active', array('eq' => 1));
        $quoteCollection->addFieldToFilter('main_table.items_count', array('gt' => 0));

        $quoteCollection->getSelect()->where('IFNULL(main_table.customer_email, quote2email.email) is not null');
        
        return $quoteCollection;
    }
    
    
    protected function _getScheduleCollection($rule){
        $scheduleCollection = Mage::getModel('amacart/schedule')->getCollection();
        
        $scheduleCollection->addFilter('rule_id', $rule->getId());
        $scheduleCollection->addFieldToFilter('delayed_start', array('gt' => 0));
        
        return $scheduleCollection;
    }
    
    protected function _getRuleCollection(){
        $ruleCollection = Mage::getModel('amacart/rule')->getCollection();
        $ruleCollection->addFilter('is_active', 1);
        $ruleCollection->setOrder('priority', 'DESC');
        
        return $ruleCollection;
    }
    
    protected function _prepare(){

        $ruleCollection = $this->_getRuleCollection();
        
        $quoteCollection = $this->_getQuoteCollection();
        
        $completedQuotes = array();
        
        foreach($quoteCollection as $quote){
            foreach($ruleCollection as $rule){
                if (!in_array($quote->getId(), $completedQuotes) && $rule->validate($quote)){
                    $this->_checkUpdated($quote);
                    
                    $scheduleCollection = $this->_getScheduleCollection($rule);
                    
                    foreach($scheduleCollection as $schedule){
                        Mage::app()->setCurrentStore($quote->getStoreId());
                        $this->createHistoryItem($quote, $schedule, $schedule->getDelayedStart());
                    }
                    
                    $completedQuotes[] = $quote->getId();   
            }
                
        }
    }
    
    
        
    }
    
    function createHistoryItem($quote, $schedule, $delayedStart = 0){
        $history = Mage::getModel('amacart/history');

        $history->setData(array(
           'quote_id'  => $quote->getId(),
           'store_id'  => $quote->getStoreId(),
           'email'  => $quote->getTargetEmail() ? $quote->getTargetEmail() : $quote->getCustomerEmail(),
           'customer_id' => $quote->getCustomerId(),
           'customer_name' => $quote->getCustomerFirstname(). ' ' .$quote->getCustomerLastname(),
           'public_key' => uniqid(),
           'schedule_id' => $schedule->getId(),
           'rule_id' => $schedule->getRuleId(),
           'created_at' => $this->date(time()),
           'scheduled_at' => $this->date(time() + $delayedStart),
           'status' => Amasty_Acart_Model_History::STATUS_PENDING
        ));

        $history->save();

        $messages = $this->_getQuoteItemsMessage($schedule->getEmailTemplateId(), $history, $quote);

        $history->setBody($messages['body']);
        $history->setSubject($messages['subject']);
        $history->save();
        
        return $history;
    }
    
    protected function _getCustomer($quote){
        $customer = new Varien_Object();
        
        $customer->setFirstname($quote->getCustomerFirstname());
        $customer->setMiddlename($quote->getCustomerMiddlename());
        $customer->setLastname($quote->getCustomerLastname());
        $customer->setSuffix($quote->getCustomerSuffix());
        
        return $customer;
    }
    
    protected function _getCouponTotal($total, $items, $type, $amount){
        $coreHelper = Mage::helper('core');
        
        $ret = $total;
        
        switch ($type){
            case "by_percent":
                    $ret -= $ret * $amount / 100;
                break;
            case "by_fixed":
                    $ret -= count($items) * $amount;
                break;
            case "cart_fixed":
                    $ret -= $amount;
                break;
        }
        
        return $coreHelper->currency($ret, true, false);
    }
    
    protected function _getTotal($total, $items, $type, $amount){
        $coreHelper = Mage::helper('core');
        
        $ret = $total;
        
        return $coreHelper->currency($ret, true, false);
    }

    protected function _initCustomQuoteVars(&$quote, $history){
        $sceduleId = $history->getScheduleId();
        
        $schedule = Mage::getModel('amacart/schedule')->load($sceduleId);
        
        $totalWith = $this->_getCouponTotal($quote->getSubtotal(), $quote->getAllVisibleItems(), $schedule->getCouponType(), $schedule->getDiscountAmount());
        $totalWithout = $this->_getTotal($quote->getSubtotal(), $quote->getAllVisibleItems(), $schedule->getCouponType(), $schedule->getDiscountAmount());
        
        $quote->setSubtotalWithCoupon($totalWith);
        $quote->setSubtotalWithoutCoupon($totalWithout);
    }

    protected function _getQuoteItemsMessage($templateId, $history, $quote){

        $ret = array(
            'body' => '',
            'subject' => ''
        );
        
        if ($templateId === NULL)
            $templateId = Mage::getStoreConfig(self::EMAIL_TEMPLATE_XML_PATH); //'amacart_notification_sent_template';
        
        $storeId = $history->getStoreId();
        
        $this->_initCustomQuoteVars($quote, $history);
        $customer = $this->_getCustomer($quote);
        
        $variables = array(
            'quote' => $quote,
            'customer' => $customer,
            'history' => $history,
            'schedule' => $this,
            'store' => Mage::app()->getStore($storeId)
        );
        $emailTemplate = Mage::getModel('core/email_template');
        $emailTemplate->setDesignConfig(array(
            'area' => 'frontend', 
            'store' => $storeId
        ));

        if (is_numeric($templateId)) {
            $emailTemplate->load($templateId);
        } else {
            $localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
            $emailTemplate->loadDefault($templateId, $localeCode);
        }

        if (!$emailTemplate->getId()) {
            throw Mage::exception('Mage_Core', Mage::helper('core')->__('Invalid transactional email code: ' . $templateId));
        }

        $ret['body'] = $emailTemplate->getProcessedTemplate($variables, true);
        $ret['subject'] = $emailTemplate->getProcessedTemplateSubject($variables);
        
        return $ret;
    }
    
    function unsubscribe($history){
        $blacklist = Mage::getModel('amacart/blist')->load($history->getEmail(), 'email');
        
        $blacklist->setData(array(
            'blacklist_id' => $blacklist->getId(),
            'email' => $history->getEmail(),
            'created_at' => $this->date(time()),
        ));
        $blacklist->save();
        
        $canceled = $this->_cancelQuote(
            $history->getQuoteId(),
            $history->getId(),
            Amasty_Acart_Model_Canceled::REASON_BALCKLIST,
            TRUE
        );

        $otherCollection = Mage::getModel('amacart/history')->getCollection();
        $otherCollection->addFieldToFilter('email', array('eq' => $history->getEmail()));
        $otherCollection->addFieldToFilter('status', array('eq' => Amasty_Acart_Model_History::STATUS_PENDING));
        
        foreach($otherCollection as $otherItem){
            $otherItem->setStatus(Amasty_Acart_Model_History::STATUS_BLACKLIST);
            $otherItem->setCanceledId($canceled->getId());
            $otherItem->save();
        }
    }
    
    function massCancel($ids){
        
        $cancelCollection = Mage::getModel('amacart/history')->getCollection();
        $cancelCollection->addFieldToFilter('history_id', array('in' => $ids));
        foreach($cancelCollection as $cancelItem){
            
            $canceled = $this->_cancelQuote(
                $cancelItem->getQuoteId(),
                NULL,
                Amasty_Acart_Model_Canceled::REASON_ADMIN,
                TRUE
            );
            
            $cancelItem->setStatus(Amasty_Acart_Model_History::STATUS_DONE);
            $cancelItem->setCanceledId($canceled->getId());
            $cancelItem->save();
        }
    }
    
    function clickByLink($history){
        $rule = Mage::getModel('amacart/rule')->load($this->getRuleId());
        if ($rule->getCancelRule() == Amasty_Acart_Model_Rule::CANCEL_RULE_LINK){
            
            $canceled = $this->_cancelQuote(
                $history->getQuoteId(),
                $history->getId(),
                Amasty_Acart_Model_Canceled::REASON_LINK,
                TRUE
            );
                        
            $otherCollection = Mage::getModel('amacart/history')->getCollection();
            $otherCollection->addFieldToFilter('email', array('eq' => $history->getEmail()));
            $otherCollection->addFieldToFilter('status', array('eq' => Amasty_Acart_Model_History::STATUS_PENDING));
            
            foreach($otherCollection as $otherItem){
                $otherItem->setStatus(Amasty_Acart_Model_History::STATUS_DONE);
                $otherItem->setCanceledId($canceled->getId());
                $otherItem->save();
            }
        }
    }
    
    protected function _checkUpdated($quote){
        
//        $history = Mage::getModel('amacart/history')->load($quoteId, 'quote_id');
        
        $historyCollection = Mage::getModel('amacart/history')->getCollection();
        
        $historyCollection->addFieldToFilter('email', array('eq' => $quote->getTargetEmail()));
        $historyCollection->addFieldToFilter('quote_id', array('neq' => $quote->getId()));
        $historyCollection->addFieldToFilter('status', array('eq' => Amasty_Acart_Model_History::STATUS_PENDING));
        
        if ($historyCollection->getSize() > 0){
            foreach($historyCollection as $historyItem){
                
                $canceled = $this->_cancelQuote(
                    $historyItem->getQuoteId(),
                    NULL,
                    Amasty_Acart_Model_Canceled::REASON_UPDATED,
                    TRUE
                );

                $historyItem->setStatus(Amasty_Acart_Model_History::STATUS_DONE);
                $historyItem->setCanceledId($canceled->getId());
                $historyItem->save();
            }
        }
    }
    
    function buyQuote($quote){
        
//        $history = Mage::getModel('amacart/history')->load($quoteId, 'quote_id');
        
        $historyCollection = Mage::getModel('amacart/history')->getCollection();
        
        $historyCollection->addFieldToFilter('email', array('eq' => $quote->getCustomerEmail()));
        $historyCollection->addFieldToFilter('status', array('eq' => Amasty_Acart_Model_History::STATUS_PENDING));

        $canceled = $this->_cancelQuote(
        $quote->getId(),
            NULL,
            Amasty_Acart_Model_Canceled::REASON_BOUGHT,
            TRUE
        );
           
        if ($historyCollection->getSize() > 0){
            
            foreach($historyCollection as $historyItem){
                $historyItem->setStatus(Amasty_Acart_Model_History::STATUS_DONE);
                $historyItem->setCanceledId($canceled->getId());
                $historyItem->save();
            }
        }
    }
    
    function date($timestamp){
        return date('Y-m-d H:i:s', $timestamp);
    }
   
    
    function getLastExecuted(){
        $ret = (string) Mage::getStoreConfig(self::LAST_EXECUTED_PATH);
        if (empty($ret)){
            $ret = time() - self::$_actualGap;
        }
        return $ret;
    }
    
    function setLastExecuted($time){
        Mage::getConfig()->saveConfig(self::LAST_EXECUTED_PATH, $time);
        Mage::getConfig()->cleanCache();
    }
   
}