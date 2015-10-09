<?php

class ICC_Orderfiltering_Model_Sales_Order extends Mage_Sales_Model_Order {

    public function sendNewOrderEmail() 
    {
        /**
         * GORILLA ADDITION
         * Loop through products and collect additional departments that need 
         * copies of this email.
         */
        $product_types = Mage::helper('icc_orderfiltering')->getTypes(true);
        $emails = array();

        $items = $this->getItemsCollection();
        foreach ($items as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $itemType = $product->getData('item_type');
            if (isset($itemType)) {
                $attrVal = $product_types [$itemType];

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
        }
        $emails = array_unique($emails); // no need to send to the same person twice
        /**
         * END GORILLA ADDITION
         */

        $storeId = $this->getStore()->getId();

        if (!Mage::helper('sales')->canSendNewOrderEmail($storeId)) {
            return $this;
        }

        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);

        // Start store emulation process
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {

            // Stop store emulation process
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            throw $exception;
        }

        // Stop store emulation process
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

        // bcc
        // default bccs
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
                $emailInfo->addBcc($email ['email']);
            }
        }
        /**
         * END GORILLA ADDTION
         */
        
        $mailer->addEmailInfo($emailInfo);

        // Email copies are sent as separated emails if their copy method is
        // 'copy'
        // Magento default copies
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
                $emailInfo->addTo($email ['email']);
                $mailer->addEmailInfo($emailInfo);
            }
        }
        /**
         * END GORILLA ADDTION
         */

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array('order' => $this, 'billing' => $this->getBillingAddress(), 'payment_html' => $paymentBlockHtml));

        $mailer->send();

        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');

        return $this;
    }

}

