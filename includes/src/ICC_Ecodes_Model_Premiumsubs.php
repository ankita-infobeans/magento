<?php
class ICC_Ecodes_Model_Premiumsubs extends Mage_Core_Model_Abstract {

  	protected $users = null;

    // we want to send these in opposite order because we increment #emails sent POST sending the email
    private $email_types = array(
        'third'  => array('number_emails_sent' => 2, 'no_longer_valid' => 'renewal_grace'),
        'second' => array('number_emails_sent' => 1, 'no_longer_valid' => 'third'),
        'first'  => array('number_emails_sent' => 0, 'no_longer_valid' => 'second'),
    );

    protected function _construct() {
        $this->_init('ecodes/premiumsubs');
    }

	public function getUsers() {
		if ($this->users == null) {
			$this->users = Mage::getModel('ecodes/premiumusers')->getCollection()->getBySubscriptionId($this->getId());
		}
		return $this->users;
	}

    public function sendRenewalEmails()
    {
        if (Mage::getStoreConfig('catalog/renew_expire_date/cron_enabled', Mage::app()->getStore()->getId()))
        {
            Mage::log("SRE-1",null,"renewal_email.log");
            foreach($this->email_types as $type => $info_array )
            {
                Mage::log("SRE-2",null,"renewal_email.log");
                $premium_subs = $this->getPremiumSubsForEmail($type);
                $template_config_path = $this->getTemplateConfigPathByType($type);
                $this->processPremiumsubEmails($premium_subs, $template_config_path);
            }
            Mage::log("SRE-3",null,"renewal_email.log");
        }
    }

    private function getPremiumSubsForEmail($type)
    {
        Mage::log("GPSFE-1",null,"renewal_email.log");
        $number = $this->email_types[$type]['number_emails_sent']; // how many emails have already been sent
        $offset_date = $this->getDateByType($type);
        $premium_subs = $this->getCollection()
                ->addFieldToFilter('emails_sent', $number)
                ->addFieldToFilter('registered', 1);
        $no_longer_valid_date = $this->getNoLongerValidDateByType($type);
        Mage::log("GPSFE-2: " . $premium_subs->getSelect()->__toString(),null,"renewal_email.log");
        Mage::log("GPSFE-3: " . $premium_subs->getSelect()->where('DATE_FORMAT( expiration, "%Y-%m-%d" ) <= "' . $offset_date . '" && DATE_FORMAT( expiration, "%Y-%m-%d" ) >= "'. $no_longer_valid_date .'"' )->__toString(),null,"renewal_email.log");
        return $premium_subs;
    }

    private function getTemplateConfigPathByType($type)
    {
        return 'catalog/renew_expire_date/ecodes_' . $type . '_renewal_email_template';
    }

    private function processPremiumsubEmails($ps_collection, $template_config_path)
    {
        Mage::log("PPSE-1",null,"renewal_email.log");
        foreach($ps_collection as $ps )
        {
        Mage::log("PPSE-2",null,"renewal_email.log");
            $email_result = $this->sendEmail($ps, $template_config_path);
            if($email_result)
            {
        Mage::log("PPSE-3",null,"renewal_email.log");
                $this->incrementEmailsSent($ps);
            }
        Mage::log("PPSE-4",null,"renewal_email.log");
        }
        Mage::log("PPSE-5",null,"renewal_email.log");
    }

    private function getNoLongerValidDateByType($type)
    {
        // we want to basically set it one step forward
        $not_valid_date = $this->getDateByType( $this->email_types[$type]['no_longer_valid'] );
        return $not_valid_date;
    }


    private function getDateByType($type)
    {
        $prefix = '+';
        switch($type)
        {
            case 'third':
                $prefix = '-';
                $num_days = Mage::getStoreConfig('catalog/renew_expire_date/email_after_days');
                break;
            case 'second':
                $num_days = 0;
                break;
            case 'first':
                $num_days = Mage::getStoreConfig('catalog/renew_expire_date/email_before_days');
                break;
            case 'renewal_grace':
                $prefix = '-';
                $num_days = Mage::getStoreConfig('catalog/renew_expire_date/renewal_grace_days');
                break;
            default:
                Mage::logException('Could not match the email type (first, second, or third) to create offset date for renewal subscriptions email.');
                break;
        }

        $expire_time_offset = strtotime($prefix . $num_days . ' day', time());
        $expire_date_offset = date('Y-m-d', $expire_time_offset);
        return $expire_date_offset;
    }

    private function incrementEmailsSent($premium_sub)
    {
        Mage::log("IES-1",null,"renewal_email.log");
        $emails_sent = $premium_sub->getEmailsSent();
        $emails_sent += 1;
        $premium_sub->setEmailsSent($emails_sent);
        $premium_sub->save();
        Mage::log("IES-2",null,"renewal_email.log");
    }

    private function sendEmail($premium_sub, $template_config_path)
    {
        Mage::log("SE-1",null,"renewal_email.log");
        $renew = Mage::getModel('ecodes/renewal');
        $customer = Mage::getModel('customer/customer')->load($premium_sub->getCustomerId());
        $product = Mage::getModel('catalog/product')->load($premium_sub->getProductId());

        $renewal_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$product->getRenewSku());
        if(empty($renewal_product))
        {
        Mage::log("SE-2: " . $premium_sub->getSku(),null,"renewal_email.log");
            Mage::logException( $this->__('The renewal sku does not seem to be set correctly: ' . $product->getRenewSku() ));
            return;
        }
        Mage::log("SE-3: " . $product->getRenewSku(),null,"renewal_email.log");
        $sent_email = $renew->sendNotificationEmail($customer, $renewal_product, $template_config_path, $premium_sub);
        Mage::log("SE-4: " . $sent_email,null,"renewal_email.log");
        return $sent_email;
    }
}
