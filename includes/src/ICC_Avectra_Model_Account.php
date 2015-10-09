<?php


class ICC_Avectra_Model_Account extends Mage_Core_Model_Abstract
{
    private $__av_customer = null;
    private $__mage_customer = null;

    /**
     * @var ICC_Avectra_Model_AvectraCommunication
     */
    private $__av_comm = null;
    private $__has_avectra_connection = null;

    protected $_billMemberAddressCxa;
    protected $_hasBillMemberStatusBeenCalled = false;

    public function _construct()
    {
        $this->__av_comm = Mage::getModel('icc_avectra/avectraCommunication');
        $this->setHasAvectraConnection($this->__av_comm->hasConnection());
    }

    public function hasAvectraConnection()
    {
        return $this->__has_avectra_connection;
    }

    private function setHasAvectraConnection($boolean)
    {
        $this->__has_avectra_connection = $boolean;
    }

    /**
     * @return ICC_Avectra_Model_AvectraCommunication
     */
    public function getAvComm()
    {
        return $this->__av_comm;
    }

    /**
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getMageCustomer()
    {
        return $this->__mage_customer;
    }

    public function setMageCustomer($customer)
    {
        $this->__mage_customer = $customer;
    }


    public function createNewUser($key)
    {
        if (!$this->hasAvectraConnection()) {
            $av_queue = Mage::getModel('icc_avectra/avectraQueue');
            $av_queue->addCreateUser($key, 'Could not connect to avectra to create the user with key: ' . $key);
            return false;
        }
        $existing_customer = $this->getUserByAvectraKey($key); // check for a pre-existing customer
        if ($existing_customer) {
            $this->__mage_customer = $existing_customer;
            return $existing_customer;
        }

        try {
            $this->__av_customer = $this->getAvComm()->getUserInfo($key);
            $existing_email_customer = $this->getByEmail((string)$this->__av_customer->Customer->cst_eml_address_dn);
            if ($existing_email_customer) { // ensure no duplicate email address
                $customer = $existing_email_customer;
            } else {
                $customer = Mage::getModel('customer/customer');
            }
            $customer->setAvectraKey($key);
            $email = isset($this->__av_customer->Customer->cst_eml_address_dn) ? ((string)$this->__av_customer->Customer->cst_eml_address_dn) : '';
            $customer->setEmail($email);
            $email_key = isset($this->__av_customer->Customer->cst_eml_key) ? ((string)$this->__av_customer->Customer->cst_eml_key) : '';
            $customer->setEmailAvectraKey($email_key);
			$pass = $this->generateRandomPassword();
            $customer->setPassword($pass);
			$customer->setConfirmation($pass);      

			/*$first_name = isset($this->__av_customer->Individual->ind_first_name) ? ((string)$this->__av_customer->Individual->ind_first_name) : 'ICC';
            $customer->setFirstname($first_name);
            $last_name = isset($this->__av_customer->Individual->ind_last_name) ? ((string)$this->__av_customer->Individual->ind_last_name) : 'Customer';
            $customer->setLastname($last_name);*/
            
            //Update Customer First Name if first name found in response
            if(isset($this->__av_customer->Individual->ind_first_name)){                
                $customer->setFirstname($this->__av_customer->Individual->ind_first_name);
            }
            
            //Update Customer Last Name if last name found in response
            if(isset($this->__av_customer->Individual->ind_last_name)){
                $customer->setLastname($this->__av_customer->Individual->ind_last_name);
            }  
            
            $customer->save();
            $this->__mage_customer = $customer;
        } catch (Exception $e) {
            // check to see if this is from historical update
            $existing_email_customer = $this->getByEmail((string)$this->__av_customer->Customer->cst_eml_address_dn);
            if ($existing_email_customer && $existing_email_customer->getAvectraKey() == 'Customer-Not-Updated-Yet-Yo') {
                $customer = $this->updateUser($key);
                $this->__mage_customer = $customer;
                return $customer;
            }
            Mage::logException($e);
            $av_queue = Mage::getModel('icc_avectra/avectraQueue');
            $av_queue->addCreateUser($key, $e->getMessage());
            return false;
        }
        if (!$this->hasAvectraConnection()) {
            $av_queue = Mage::getModel('icc_avectra/avectraQueue');
            $av_queue->addUpdateUser($key);

        } else {
            $customer = $this->updateUser($key);
            $this->__mage_customer = $customer;
        }
        return $customer;
    }

    public function getByEmail($email)
    {
        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $customer->loadByEmail($email);
        if ($customer->getId()) {
            return $customer;
        }
        return false;
    }

    public function updateUser($avectra_key)
    {  
        $set_customer_result = $this->setCustomer($avectra_key);
        try {
            $this->updateUserAddress($avectra_key);
            $org_customer_number = $this->getUserOrgCustomerNo($avectra_key);
            $mageCustomer = $this->getMageCustomer();
            $mageCustomer->setData('updating_from_avectra', true);
            
            //Update Customer First Name if first name found in response
            if(isset($this->__av_customer->Individual->ind_first_name)){
                //$first_name = isset($this->__av_customer->Individual->ind_first_name) ? ((string)$this->__av_customer->Individual->ind_first_name) : 'ICC';
                $mageCustomer->setFirstname($this->__av_customer->Individual->ind_first_name);
            }else{
                $this->updateUser($avectra_key);
                Mage::log('Avectra Key : '. $avectra_key, null, 'update-user-failed-request.log', true);
            }
            
            //Update Customer Last Name if last name found in response
            if(isset($this->__av_customer->Individual->ind_last_name)){
                //$last_name = isset($this->__av_customer->Individual->ind_last_name) ? ((string)$this->__av_customer->Individual->ind_last_name) : 'Customer';
                $mageCustomer->setLastname($this->__av_customer->Individual->ind_last_name);
            }else{
                $this->updateUser($avectra_key);
                Mage::log('Avectra Key : '. $avectra_key, null, 'update-user-failed-request.log', true);
            }
            
            if (isset($this->__av_customer->Customer->cst_eml_address_dn) && $this->__av_customer->Customer->cst_eml_address_dn) {
                $mageCustomer->setEmail((string)$this->__av_customer->Customer->cst_eml_address_dn);
                $mageCustomer->setEmailAvectraKey((string)$this->__av_customer->Customer->cst_eml_key);            
            }
            $taxExempt = $this->getTaxExemptStatus($avectra_key);            
            $mageCustomer->setTaxExemptStatus($taxExempt);
            $mageCustomer->setMemberStatus($this->recievesMemberBenefits($avectra_key)); //  (bool)(int)(string) $this->__av_customer->Customer->cst_receives_member_benefits_flag);
            $mageCustomer->setCreditLimit($this->getCreditLimit($avectra_key));
            $mageCustomer->setCreditHold((string)(int)$this->hasCreditHold($avectra_key)); //$this->__av_customer->Customer->cst_credit_hold_flag);
            // after calling hasBillMemberStatus we sould know which cxa key to set as the bill member address to sync 
            if ($mageCustomer->getMemberStatus()) {
                $mageCustomer->setBillmemberAddrCxa($this->getBillMemberAddressCxa());
            } else {
                // reset to blank if this has been removed
                $mageCustomer->setBillmemberAddrCxa('');
            }
            // customer group:
            if ($this->recievesMemberBenefits($avectra_key)) {
//            if($this->hasBillMemberStatus($avectra_key)){
				$data = array(	'mage_customer' => $mageCustomer,
								'avectra_key' => $avectra_key,
								'group' => '2'
							);
				Mage::dispatchEvent('avectra_account_update_user_group', $data );
                $mageCustomer->setGroupId('2');
            }
			
            $orgKeys = $this->getAffiliatedOrgAvKeys($avectra_key);
            $mageCustomer->setAffiliatedOrgAvKey(serialize($orgKeys));
            //member number
            $mageCustomer->setCustomerNo((string)$this->__av_customer->Customer->cst_recno);
            // org number
            if ($org_customer_number) {
                $mageCustomer->setOrgCustomerNo($org_customer_number);
            }


            // check demographics form - must have filled out at least two
            $demos_present = 0;
            $indiv = $this->__av_customer->Individual;
            if (isset($indiv->ind_industry_ext) && trim((string)$indiv->ind_industry_ext) != '') $demos_present += 1;
            if (isset($indiv->ind_trade_ext) && trim((string)$indiv->ind_trade_ext) != '') $demos_present += 1;
            if (isset($indiv->ind_specialty_ext) && trim((string)$indiv->ind_specialty_ext) != '') $demos_present += 1;
            $mageCustomer->setHasUpdatedDemo(($demos_present > 1));
            try {
                $mageCustomer->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        return $mageCustomer;
    }

    public function getAffiliatedOrgAvKeys($customer_av_key)
    {
        return $this->getAvComm()->getAffiliationKeys($customer_av_key);
    }

    public function updateUserAddress($avectra_key)
    {
        $this->setCustomer($avectra_key);
        $mageCustomer = $this->getMageCustomer();
        $addresses = $mageCustomer->getAddresses();
        $addresses_by_keys = array();
        foreach ($addresses as $address) {
            if ($address->hasAvectraKey() && $address->getAvectraKey() != 'garbage') {
                $addresses_by_keys[$address->getAvectraKey()] = $address;
            }
        }

        // user addresses
        $av_simple_addresses = $this->getAvComm()->getCustomerAddresses($avectra_key);
        $av_address_keys = array();
        foreach ($av_simple_addresses as $av_simple_address) {
            $cxa_key = (string)$av_simple_address->cxa_key;
            $av_address_keys[] = $cxa_key;
            $this->updateAddress($cxa_key, $addresses_by_keys, $mageCustomer, false, $avectra_key);
        }

        $affiliatedOrgs = $this->getUserAffiliatedOrganizations($avectra_key, true); // this key is the customer key

        // affiliated org addresses
        foreach ($affiliatedOrgs as $affiliatedOrg) {
            if (isset($affiliatedOrg->Billing_Address_XRef_1->cx2__cxa_key)) {
                $cxa_key = (string)$affiliatedOrg->Billing_Address_XRef_1->cx2__cxa_key;
            } else if (isset($affiliatedOrg->Address_XRef_1->cxa_key)) {
                $cxa_key = (string)$affiliatedOrg->Address_XRef_1->cxa_key;
            } else {
                continue;
            }
            $av_address_keys[] = $cxa_key;
            $this->updateAddress($cxa_key, $addresses_by_keys, $mageCustomer, true, $avectra_key);
        }

        // delete addresses no longer in icc
        $mage_av_keys = array_keys($addresses_by_keys);
        $delete_keys = array_diff($mage_av_keys, $av_address_keys);
        foreach ($delete_keys as $key) {
            $address = $addresses_by_keys[$key];
            $address->delete();
        }
        // delete addresses without av keys
        $mageCustomer = $this->getMageCustomer();
        $addresses = $mageCustomer->getAddresses();
        foreach ($addresses as $address) {
            if (!$address->getAvectraKey()) {
                try {
                    $address->delete();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    public function updateAddress($av_cxa_key, $addresses_by_keys, $mageCustomer, $isAffiliatedOrg, $avectra_key)
    {
        $av_address = $this->getAvComm()->getAddressByKey($av_cxa_key);
        $address = (isset($addresses_by_keys[$av_cxa_key]) ? ($addresses_by_keys[$av_cxa_key]) : (null));
        if (empty($address)) $address = Mage::getModel('customer/address');

        $address->setData('updating_from_avectra', true); // note this doesn't save on the address - only a temp flag
        $address->setIsAffiliatedOrg($isAffiliatedOrg);
        $address->setAvectraKey($av_cxa_key);
        $isPrimary = (isset($av_address->Address_XRef->cxa_primary)) ? (bool)(int)$av_address->Address_XRef->cxa_primary : false;
        $address->setIsPrimary($isPrimary);
        $firstName = isset($av_address->Individual->ind_first_name) ? (string)$av_address->Individual->ind_first_name : $mageCustomer->getFirstname();
        $address->setFirstname($firstName);
        $lastName = isset($av_address->Individual->ind_last_name) ? (string)$av_address->Individual->ind_last_name : $mageCustomer->getLastname();
        $address->setLastname($lastName);
        $av_street = array();
        $av_street[] = isset($av_address->Address->adr_line1) ? (string)$av_address->Address->adr_line1 : '';
        if (isset($av_address->Address->adr_line2)) {
            $av_street[] = (string)$av_address->Address->adr_line2;
        }
        $address->setStreet($av_street);
        if (isset($av_address->Address->adr_city)) $address->setCity((string)$av_address->Address->adr_city);
        if (isset($av_address->Address->adr_post_code)) $address->setPostcode((string)$av_address->Address->adr_post_code);
        $country_code = (isset($av_address->Country->cty_fips_code)) ? ((string)$av_address->Country->cty_fips_code) : ('US');

        if (isset($av_address->Address->adr_state)) {
            $region_code = (string)$av_address->Address->adr_state;
            $regionModel = Mage::getModel('directory/region')->loadByCode($region_code, $country_code);
            $region_id = $regionModel->getId();
            if ($region_id) {
                $address->setRegionId($region_id);
            }
        }

        $address->setCountryId($country_code);
        $address->setCustomer($mageCustomer);
        $telephone = $address->getTelephone();
        if (empty($telephone)) {
            $av_primary_phone = $this->getPrimaryPhone($avectra_key);
            if (isset($av_primary_phone->Phone_XRef)) {
                $address->setTelephone((string)$av_primary_phone->Phone_XRef->cph_phn_number_complete);
                $address->setPhoneAvectraKey((string)$av_primary_phone->Phone_XRef->cph_key);
            } else {
                $address->setTelephone('(123)555-0000'); // just default to something
            }
        }
        try {
            $address->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        if ($isAffiliatedOrg) {
            return;
        }
        // if it is set to the billing address, use this
        if ((int)$av_address->Address_XRef->cxa_billing) {
            $mageCustomer->setDefaultBilling($address->getId());
            $mageCustomer->setData('set_default_billing_flag', true);
            try {
                $address->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        // if this is a primary and we haven't yet set based off of billing being flagged then use as default billing
        if ((int)$av_address->Address_XRef->cxa_primary && !$mageCustomer->getData('set_default_billing_flag')) {
            $mageCustomer->setDefaultBilling($address->getId());
            try {
                $address->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function getPrimaryPhone($customer_avectra_key)
    {
        // returns a simple xml object
        $primary_phone = $this->getAvComm()->getPrimaryPhone($customer_avectra_key);
        return $primary_phone;
    }

    private function setCustomer($avectra_key = '') // init our customer
    {
        if (!(is_null($this->__av_customer) || is_null($this->__mage_customer))) {
            return true; // we have already set these
        }
        if (trim($avectra_key) == '') {
            return false;
        }
        if (!$this->hasAvectraConnection()) {
            return false;
        }
        $this->__mage_customer = $this->getUserByAvectraKey($avectra_key); // check for a pre-existing customer
        if (!$this->__mage_customer) {
            return false;
        }
        $this->__av_customer = $this->getAvComm()->getUserInfo($avectra_key);
        if (!$this->__av_customer) {
            return false;
        }
        return true;
    }

    public function updateAvectra($av_key)
    {
        try {
            if (!$this->hasAvectraConnection()) {
                return false;
            }
            $avectra_update_result = $this->getAvComm()->updateAvectra($av_key);
            return $avectra_update_result;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    public function updateAvectraDemographics($update_info)
    {
        try {
            $demo_update = $this->getAvComm()->updateDemographics($update_info);
            return $demo_update;
        } catch (Exception $e) {
            return false;
        }
    }

    private function generateRandomPassword()
    {
        return substr(md5(mt_rand()), 0, 10);
    }

    public function getBillingArray($avectra_key) // not used
    {
        $this->setCustomer($avectra_key);
        $customer = $this->__mage_customer; // $this->getUserByAvectraKey($avectra_key); //$this->updateUser($avectra_key); //
        if ($customer === false || !(bool)(int)$customer->getMemberStatus()) {
            return false;
        }
        $primary_billing = $customer->getPrimaryBillingAddress();

        $billing_array = array(
            'address_id' => $primary_billing->getId(),
            'firstname' => $primary_billing->getFirstname(),
            'lastname' => $primary_billing->getLastname(),
            'company' => '',
            'street' => Array
            (
                '0' => $primary_billing->getStreet(1),
                '1' => $primary_billing->getStreet(2)
            ),

            'city' => $primary_billing->getCity(),
            'region_id' => $primary_billing->getRegionId(),
            'region' => $primary_billing->getRegionCode(), // $primary_billing->getRegionCode()
            'postcode' => $primary_billing->getPostcode(),
            'country_id' => $primary_billing->getCountry(),
            'telephone' => $primary_billing->getTelephone(),
            //         'fax' => '',
//            'use_for_shipping' => '1',
        );
        return $billing_array;
    }

    public function getUserByAvectraKey($av_key)
    {
        if (!is_null($this->__mage_customer)) {
            return $this->getMageCustomer();
        }
        $customers = Mage::getModel('customer/customer')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('avectra_key', $av_key);

        if ($customers->count() === 1) {
            $this->setMageCustomer($customers->getFirstItem());
            return $this->getMageCustomer();
        } else {
            return false;
        }
    }

    public function getAvCustomer($avectra_key)
    {
        if (is_null($this->__av_customer)) {
            $this->__av_customer = $this->getUserInfo($avectra_key);
        }
        return $this->__av_customer;
    }


    public function getUserInfo($avectra_key)
    {
        $user = $this->__av_comm->getUserInfo($avectra_key);
        return $user;
    }

    public function getWebUser($av_key)
    {
        $av_web_user = $this->__av_comm->getWebUser($av_key);
        return $av_web_user;
    }

    /**
     * Does the individual have own member status
     *
     * @param $avectra_key string
     * @return bool
     */
    public function recievesMemberBenefits($avectra_key)
    {
        $av_customer = $this->getAvCustomer($avectra_key);

        if (isset($av_customer->Customer->cst_receives_member_benefits_flag) && (int)(string)$av_customer->Customer->cst_receives_member_benefits_flag) {
            return true;
        }
        return false;
    }

    /**
     * Does the user or any of the affiliated organizations have bill member status
     *
     * @param $avectra_key string
     * @return bool
     */

    public function hasBillMemberStatus($avectra_key)
    {
        $affiliations = $this->getUserAffiliatedOrganizations($avectra_key, true);
        $affiliation_has_billmember = false;

        if ($affiliations) {
            foreach ($affiliations as $affiliation) {
                $affiliation_has_billmember = (bool)(int)$affiliation->Customer->cst_receives_member_benefits_flag;
                if ($affiliation_has_billmember) {
                    $this->setBillMemberAddressCxa($affiliation->Customer->cst_cxa_key);
                    $this->setBillmemberOrgKey($affiliation->Customer->cst_key);
                    break;
                }
            }
        }
        $av_customer = $this->getAvCustomer($avectra_key);
        $has_own = (bool)(int)$av_customer->Customer->cst_member_flag;
        if ($has_own && isset( $av_customer->Customer->cst_cxa_key ) && (string) $av_customer->Customer->cst_cxa_key ) {
            $this->setBillMemberAddressCxa('');
            $this->setBillmemberOrgKey('');
            if (isset($av_customer->Customer->cst_cxa_key)) {
                $this->setBillmemberAddressCxa($av_customer->Customer->cst_cxa_key);
            }
        }
        $this->setHasBillMemberStatusBeenCalled(true);
        $this->setHasBillMemberStatusResults(($has_own || $affiliation_has_billmember));
        return ($has_own || $affiliation_has_billmember); //getBillMemberStatus
    }

    /**
     * Does any of the user's affiliated orginzations get a tax exempt status?
     * (Per Tim, don't need to check the individual. see: https://tracker.gorillagroup.com/issues/11448#note-12 )
     *
     * @param $avectra_key
     * @return bool
     */
    public function getTaxExemptStatus($avectra_key)
    {
        $affiliations = $this->getUserAffiliatedOrganizations($avectra_key, true);
        
        $affiliation_is_tax_exempt = false;
        if ($affiliations) {
            foreach ($affiliations as $affiliation) {
                
                $affiliation_is_tax_exempt = (bool)(int)$affiliation->Customer->cst_tax_exempt_flag;
                if ($affiliation_is_tax_exempt) {
                    break;
                }
            }
        }
        return $affiliation_is_tax_exempt;
    }


    public function getCreditLimit($avectra_key)
    {
        // Fix when customer is not set
        $this->setCustomer($avectra_key);
        if (!$this->getHasBillMemberStatusBeenCalled()) {
            $this->hasBillMemberStatus($this->getMageCustomer()->getAvectraKey());
        }
        if ($this->getBillmemberOrgKey()) {
            $credit_limit = $this->getOrgCreditLimit($this->getBillmemberOrgKey());
        } else {
            $credit_limit = $this->getUserCreditLimit($this->getMageCustomer()->getAvectraKey());
        }
        return $credit_limit;
    }

    private function getUserCreditLimit($avectra_key)
    {
        $av_customer = $this->getUserInfo($avectra_key);
        if (isset($av_customer->Customer->cst_credit_limit)) {
            if ((float)$av_customer->Customer->cst_credit_limit > 0) {
                return (float)$av_customer->Customer->cst_credit_limit;
            }
        }
        return false;
    }

    private function getOrgCreditLimit($org_key)
    {
        $av_org = $this->getOrganizationInformation($org_key);
        if (isset($av_org->Customer->cst_credit_limit)) {
            if ((float)$av_org->Customer->cst_credit_limit > 0) {
                return (float)$av_org->Customer->cst_credit_limit;
            }
        }
        return false;
    }

    public function hasCreditHold($avectra_key)
    {
        $av_customer_credit_hold = $this->getCreditHold($avectra_key);
        if ($av_customer_credit_hold) {
            return true;
        }
        $org_keys = $this->getAffiliatedOrgAvKeys($avectra_key);
        foreach ($org_keys as $org_key) {
            $organization_credit_hold = $this->getCreditHold($org_key, true);
            if ($organization_credit_hold) {
                return true;
            }
        }
        return false; // did not find a credit hold
    }

    public function getCreditHold($avectra_key, $isOrg = false)
    {
        if ($isOrg) {
            $av_customer = $this->getOrganizationInformation($avectra_key);
        } else {
            $av_customer = $this->getUserInfo($avectra_key);
        }
        return (bool)(isset($av_customer->Customer->cst_credit_hold_flag)) ? (string)$av_customer->Customer->cst_credit_hold_flag : false;
    }

    public function getPhone($phone_key)
    {
        $phone = $this->getAvComm()->getPhoneByKey($phone_key);
        return (string)$phone;
    }

    public function getAvectraKeyByUserPass($user, $pass)
    {
        $key = (string)$this->getAvComm()->getUserKey($user, $pass);
        return $key;
    }

    public function getUserByRecNo($rec_no)
    {
        $user = $this->getAvComm()->getUserByRecNo($rec_no);
        return $user;
    }

    public function getOrganizationInformation($org_key)
    {
        $org_info = $this->getAvComm()->getOrganizationInformation($org_key);
        return $org_info;
    }
    public function getUserAffiliation($av_key)
    {
        $affiliation = $this->getAvComm()->getUserAffiliation($av_key);
        return $affiliation;
    }

    public function getUserAffiliatedOrganizations($av_key, $primary = false)
    {
        $_avComm = $this->getAvComm();
        $affiliations = $_avComm->getUserAffiliatedOrganizations($av_key, $primary);
        return $affiliations;
    }

    public function getUserOrgCustomerNo($av_key)
    {
        // if no org affiliation then it will return false
        $customer_no = $this->getAvComm()->getUserOrgCustomerNo($av_key);

        // fix for wonky return of org number that returns false when there is actually an org attach

        if(!$customer_no)
        {
           $customer_no =  $this->getAvComm()->getUserOrgCustomerNoRollback($av_key);
        }
        return $customer_no;
    }

    public function getPrimaryAddress($customer_av_key)
    {
        $primary_billing = $this->getAvComm()->getPrimaryAddress($customer_av_key);
        return $primary_billing;
    }

    public function getGetObjectList()
    {
        //GetFacadeObjectList
        $list = $this->getAvComm()->getGetObjectList();
        return $list;
    }

    public function getBillMemberAddressCxa()
    {
        return $this->_billMemberAddressCxa;
    }

    public function setBillMemberAddressCxa($key)
    {
        $this->_billMemberAddressCxa = $key;
    }

    public function getHasBillMemberStatusBeenCalled()
    {
        return $this->_hasBillMemberStatusBeenCalled;
    }

    public function setHasBillMemberStatusBeenCalled($bool)
    {
        $this->_hasBillMemberStatusBeenCalled = $bool;
    }

    /**
     *
     * @param string $avectra_key
     * @param string $customer_av_key
     * @return bool
     */
    public function deleteAvectraAddress($avectra_key, $customer_av_key)
    {
        return $this->getAvComm()->deleteAvectraAddress($avectra_key, $customer_av_key);
    }

    public function getUserByIndToken($ind_token)
    {
        $user = $this->getAvComm()->getUserByIndToken($ind_token);
        return $user;
    }
    
    /**
     * Method for updating user from Avectra while Add To Cart
     * @param string $avectra_key     
     * @return object Magento Customer
     */
    public function quickUpdateUser($avectra_key)
    {  
        $set_customer_result = $this->setCustomer($avectra_key);
        try {
            $mageCustomer = $this->getMageCustomer();
            $mageCustomer->setData('updating_from_avectra', true);
            $taxExempt = $this->getTaxExemptStatus($avectra_key);            
            $mageCustomer->setTaxExemptStatus($taxExempt);
            $mageCustomer->setMemberStatus($this->recievesMemberBenefits($avectra_key));
            $mageCustomer->setCreditLimit($this->getCreditLimit($avectra_key));
            $mageCustomer->setCreditHold((string)(int)$this->hasCreditHold($avectra_key));
            try {
                $mageCustomer->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        return $mageCustomer;
    }
}
