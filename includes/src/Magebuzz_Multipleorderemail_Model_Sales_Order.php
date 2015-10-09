<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Model_Sales_Order extends Mage_Sales_Model_Order {
   
    /**
     * XML configuration paths
     */
    const XML_PATH_EMAIL_TEMPLATE               = 'sales_email/order/template';
    const XML_PATH_EMAIL_GUEST_TEMPLATE         = 'sales_email/order/guest_template';
    
    public function queueNewOrderEmail($forceMode = false)
    { 
        Mage::log('ordreemail', null, 'order.log');
        $storeId = $this->getStore()->getId();  
        if (!Mage::helper('sales')->canSendNewOrderEmail($storeId)) {
            return $this;
        }   
        $itemArray = array();
        $ruleArray = array();
        $allItem = array();
        $products = array();
        $attributeSet = array();
        $appStore = Mage::app()->getStore()->getStoreId();
        $stores = array(1, $appStore);
        $ruleResource = Mage::getResourceModel('multipleorderemail/multipleorderemailrule')->getlistRuleIds($stores);    
        $ordeEmailRule = Mage::getModel('multipleorderemail/multipleorderemailrule');
        $ruleModel = $ordeEmailRule->getCollection()->addFieldToFilter('rule_id',array('in'=> $ruleResource))->AddFieldToFilter('status',1)->setOrder('sort_order', 'asc');
        foreach ($this->getAllItems() as $item) {      
            $products[] = $item->getProductId();
        }
        $productsCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addIdFilter($products)
                ->load();
        /**
         * GORILLA ADDITION
         * Loop through products and collect additional departments that need 
         * copies of this email.
         */
      
        $product_types = Mage::helper('icc_orderfiltering')->getTypes(true);
        $emails = array();
        $tyepArray = array();
        foreach ($this->getAllItems() as $item) {   
            $product = $productsCollection->getItemById($item->getProductId());
            $itemType = $product->getData('item_type');
            if (isset($itemType)) {
                $attrVal = $product_types [$itemType];
                $attrVal = strtolower($attrVal);
                $tyepArray[$item->getItemId()]['item_type'] = $attrVal; 
                switch ($attrVal) {
                    case "certification" :
                        $emails [] = Mage::getStoreConfig("sales_email/emailcertification", $this->getStoreId());
                        break;
                    case "downloadable_ecodes" :
                        $emails [] = Mage::getStoreConfig("sales_email/emaildownloadableecodes", $this->getStoreId());
                        break;
                    case "premium_access" :
                        $emails [] = Mage::getStoreConfig("sales_email/emailpremiumecodes", $this->getStoreId());
                        break;
                    case "membership" :
                        $emails [] = Mage::getStoreConfig("sales_email/emailmembership", $this->getStoreId());
                        break;
                    case "training_education" :
                        $emails [] = Mage::getStoreConfig("sales_email/emailtraining", $this->getStoreId());
                        break;
                }
            }
            if (!$item->getParentItemId())
                $attributeSet[] = $product->getAttributeSetId();
        }
        
        $emails = array_unique($emails); // no need to send to the same person twice
        /**
         * END GORILLA ADDITION
         */
        
        $dynamic_block = '';
        foreach ($ruleModel as $rule) {
            $itemArray = array();
            $items = array();
            $attributeSetIds = explode(',', $rule->getAttributeSetId());
            $difference = array_merge(array_diff($attributeSetIds, $attributeSet), array_diff($attributeSet, $attributeSetIds));//print_R($difference);
            $parentItemId = '';
            foreach($this->getAllItems() as $item) {     
                $items[$item->getItemId()] = $item->getProductId();
                $result = $rule->getActions()->validate($item); 
                $allItem[$item->getProductId()] =  $item->getProductId();
                if ($result == true) {
                    $itemArray[$item->getProductId()] =  $item->getProductId();
                }
                $itemType = $tyepArray[$item->getItemId()]['item_type'];
                if ( $itemType == 'membership') {
                    $parentItemId = $item->getItemId();
                }
                if (($item->getParentItemId()) && ($item->getParentItemId() != $parentItemId)) {
                    unset($itemArray[$items[$item->getParentItemId()]]);
                }
            }
            $ordeEmailRule->load($rule->getRuleId());
            $customerGroup = unserialize($ordeEmailRule->getUserGroup());
            if (!empty($itemArray)) {
                $ruleArray[$rule->getRuleId()]= $itemArray;
                $dynamic_block .= $rule->getOrderEmailBlock();
            }
        }  

        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);            
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
        try {                
            $paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            throw $exception;
        }
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            // Retrieve corresponding email template id and customer name
            if ($this->getCustomerIsGuest()) {
                $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
                $customerName = $this->getBillingAddress()->getName();
            } else {
                $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
                $customerName = $this->getCustomerName();
            }
        $mailer = Mage::getModel('core/email_template_mailer');
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($this->getCustomerEmail(), $customerName);
        if ($copyTo && $copyMethod == 'bcc') {
            foreach ($copyTo as $email) {
                $emailInfo->addBcc($email);
            }
        }
                
        /**
         * GORILLA ADDITION
         * bcc relavant departments
         */
        // specific dept copies determined above
        if ($emails && $copyMethod == 'bcc') {
            foreach ($emails as $email) {
                $emailArray = explode(',', $email ['email']);
                foreach ($emailArray as $_email) {
                    $emailInfo->addBcc($_email);
                }
            }
        }
        /**
         * END GORILLA ADDTION
        */
        
        $mailer->addEmailInfo($emailInfo);
        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $emailInfo = Mage::getModel('core/email_info');
                $emailInfo->addTo($email);
                $mailer->addEmailInfo($emailInfo);
            }
        }
        
       /**
        * GORILLA ADDITION
        * cc or new email relavant departments
        */
        // specific dept copies determined above
        if ($emails && $copyMethod == 'copy') {
            foreach ($emails as $email) {
                $emailInfo = Mage::getModel('core/email_info');
                $emailArray = explode(',', $email ['email']);
                foreach ($emailArray as $_email) {
                    $emailInfo->addTo($_email);
                }
                $mailer->addEmailInfo($emailInfo);
            }
        }
        /**
         * END GORILLA ADDTION
         */
        
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
            'order'        => $this,
            'dynamic_block' => $dynamic_block,
            'billing'      => $this->getBillingAddress(),
            'payment_html' => $paymentBlockHtml
            )
        );
        /** @var $emailQueue Mage_Core_Model_Email_Queue */
        $emailQueue = Mage::getModel('core/email_queue');
        $emailQueue->setEntityId($this->getId())
            ->setEntityType(self::ENTITY)
            ->setEventType(self::EMAIL_EVENT_NAME_NEW_ORDER)
            ->setIsForceCheck(!$forceMode);

        $mailer->send();  
	//$mailer->setQueue($emailQueue)->send();
        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');
        return $this;      
    }
    
    /**
     * Queue email with order update information
     *
     * @param boolean $notifyCustomer
     * @param string $comment
     * @param bool $forceMode if true then email will be sent regardless of the fact that it was already sent previously
     *
     * @return Mage_Sales_Model_Order
     */
    public function queueOrderUpdateEmail($notifyCustomer = true, $comment = '', $forceMode = false)
    {
        $storeId = $this->getStore()->getId();

        if (!Mage::helper('sales')->canSendOrderCommentEmail($storeId)) {
            return $this;
        }
        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_UPDATE_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_COPY_METHOD, $storeId);
        // Check if at least one recipient is found
        if (!$notifyCustomer && !$copyTo) {
            return $this;
        }

        // Retrieve corresponding email template id and customer name
        if ($this->getCustomerIsGuest()) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE, $storeId);
            $customerName = $this->getBillingAddress()->getName();
        } else {
            $templateId = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_TEMPLATE, $storeId);
            $customerName = $this->getCustomerName();
        }

        /** @var $mailer Mage_Core_Model_Email_Template_Mailer */
        $mailer = Mage::getModel('core/email_template_mailer');
        if ($notifyCustomer) {
            /** @var $emailInfo Mage_Core_Model_Email_Info */
            $emailInfo = Mage::getModel('core/email_info');
            $emailInfo->addTo($this->getCustomerEmail(), $customerName);
            if ($copyTo && $copyMethod == 'bcc') {
                // Add bcc to customer email
                foreach ($copyTo as $email) {
                    $emailInfo->addBcc($email);
                }
            }
            $mailer->addEmailInfo($emailInfo);
        }

        // Email copies are sent as separated emails if their copy method is
        // 'copy' or a customer should not be notified
        if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
            foreach ($copyTo as $email) {
                $emailInfo = Mage::getModel('core/email_info');
                $emailInfo->addTo($email);
                $mailer->addEmailInfo($emailInfo);
            }
        }

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'order'   => $this,
                'comment' => $comment,
                'billing' => $this->getBillingAddress()
            )
        );

        /** @var $emailQueue Mage_Core_Model_Email_Queue */
        $emailQueue = Mage::getModel('core/email_queue');
        $emailQueue->setEntityId($this->getId())
            ->setEntityType(self::ENTITY)
            ->setEventType(self::EMAIL_EVENT_NAME_UPDATE_ORDER)
            ->setIsForceCheck(!$forceMode);
       // $mailer->setQueue($emailQueue)->send();
        $mailer->send();
        return $this;
    }
    
}
