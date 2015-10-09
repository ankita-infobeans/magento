<?php


class ICC_Ecodes_Model_Renewal
{

    public function sendNotificationEmail($customer, $renewal_product, $template_config_path, $premium_sub)
    {
        Mage::log("SNE-1",null,"renewal_email.log");
        if ( empty($customer) || empty($renewal_product) || empty($template_config_path) )
        {
            Mage::log("SNE-2",null,"renewal_email.log");
            Mage::logException( new Exception('Ecodes Renewal Subscription email not sent because one of the required parameters was not set.' ));
            return;
        }
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $mail_template = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        $template = Mage::getStoreConfig($template_config_path, Mage::app()->getStore()->getId());
        //$template = 28;
        //Zend_Debug::dump(Mage::app()->getStore()->getId());

        $mail_template->setDesignConfig( array('area'=>'frontend', 'store'=>Mage::app()->getStore()->getId() ));
        if (Mage::getStoreConfig('catalog/renew_expire_date/send_email_enabled', Mage::app()->getStore()->getId()))
        {
            $result = $mail_template->sendTransactional(
                $template,
                Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, Mage::app()->getStore()->getId()),
                $customer->getEmail(),
                $customer->getName(),
                array(
                    'customer'  => $customer,
                    'product' => $renewal_product,
                    'premiumsub' => $premium_sub,
                )
            );
            Mage::log("SNE-3: " . $result->getSentSuccess(),null,"renewal_email.log");
        }

        $translate->setTranslateInline(true);
        Mage::log("SNE-4",null,"renewal_email.log");

        return $this;
    }

    public function isRenewalProduct($prod)
    {
        $parent_sku = $prod->getRenewParentSku();
        return ! empty( $parent_sku );
    }

    /**
     * ECODE premium subscription skus follow the convention below:
     *
     * Configurable product, such as IC-P-2006-000019 would have associated virtual subscriptions such as
     *  IC-P-2006-000019-5u3y. Renewals have skus such as IC-P-2006-000019-5u3yR.  Any configurable skus appearing
     *  in the ecodes_premium_subs table are old test cases and need not be considered in this query.
     *
     * - Developers: bjoly, rberrill, rsuess and omccormack, other?
     *
     * @param $prod
     * @param null $customer
     * @return bool|int
     */
    public function hasAccessToRenewal($prod, $customer = null)
    {
        $customer = (is_null($customer))? ( Mage::getSingleton('customer/session')->getCustomer() ) : ($customer);
        if(empty($customer)) {
            return false;
        }

        // Renewal product sku is expected to have a trailing "R", such as IC-P-2006-000019-5u3yR
        // The sku of the original subscription item does not have the trailing "R", such as IC-P-2006-000019-5u3y
        $sku = $prod->getRenewParentSku();

        // If there is no renew "parent" sku, then remove the trailing R from the renewal subscription sku to get the "parent".
        if(empty($sku) && substr($prod->getSku(), -1) == 'R') {
            $sku = substr($prod->getSku(), 0, -1);
        }

        $premiumsubs = Mage::getModel('ecodes/premiumsubs')->getCollection();
        $premiumsubs->addFieldToFilter('customer_id', $customer->getId() );
        $premiumsubs->addFieldToFilter('sku', $sku);
        $premiumsubs->addFieldToFilter('registered', 1 );

        $renewal_grace_days = Mage::getStoreConfig('catalog/renew_expire_date/renewal_grace_days');
        $exp_minus_overagetime = date('Y-m-d H:i:s', (time() - (60*60*24*$renewal_grace_days)) );

        $premiumsubs->addFieldToFilter('expiration', array('gt' => $exp_minus_overagetime) );
        return (bool) $premiumsubs->count();
    }

}