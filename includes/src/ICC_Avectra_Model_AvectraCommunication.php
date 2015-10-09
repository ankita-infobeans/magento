<?php

$lib_path = Mage::getBaseDir('lib');
//require_once $lib_path . '/xweb/xwebSecureClient.class.php';
require_once $lib_path . '/xweb/XwebSoapClient.php';
require_once Mage::getBaseDir() . '/app/code/local/ICC/Avectra/Model/Soap/Typemap/Individual/Individual/DataObjectType.php';
require_once Mage::getBaseDir() . '/app/code/local/ICC/Avectra/Model/Soap/Typemap/IndividualAddress/Address/DataObjectType.php';

class ICC_Avectra_Model_AvectraCommunication extends Mage_Core_Model_Abstract
{

    private $__client;
    private $__nf_user_name; // = 'iccxweb1';
    private $__nf_user_pass; // = 'fvroIs6Q';
    private $__user_info = null;
    private $__has_connection = null;
    private $__url = null;
    
    private $__token_path = null;
    private $__prefixCall = 'ICC';
    

    public function _construct()
    {
        $use_live_url = (bool) Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/useliveurl');
        //$this->__url = Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/avectraallurl');
        
        if ($use_live_url) {
			$this->__url = Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/avectraallurl'); // live url
            //$this->__token_path = 'wsdl/avectra_eweb_login.wsdl';
	     $this->__token_path = '/var/www/html/wsdl/avectra_eweb_login.wsdl';	
        } else {
			$this->__url = Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/avectraallurl'); // test url
           		$this->__token_path = '/var/www/html/wsdl/avectra-test-wsdl.wsdl';            
        }
        
        $this->__nf_user_name = Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/avectraapiusername');
        $this->__nf_user_pass = Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/avectraapipassword');
        try {
            $this->__client = new XwebSoapClient($this->__url, Array(
                'trace'        => true, //turning on trace = true will let us grab the headers and responses
                'exceptions'   => true, // this throws exceptions instead of allowing us to trace the entire response
                'xwebUserName' => $this->__nf_user_name,
                'xwebUserPass' => $this->__nf_user_pass,
                'typemap'      => array(
                    array(
                        'type_ns'   => 'http://www.avectra.com/2005/',
                        'type_name' => 'Individual_Individual_DataObjectType',
                        'to_xml'    => 'ICC_Avectra_Model_Soap_Typemap_Individual_Individual_DataObjectType::toXml'
                    ),
                    array(
                        'type_ns'   => 'http://www.avectra.com/2005/',
                        'type_name' => 'IndividualAddress_Address_DataObjectType',
                        'to_xml'    => 'ICC_Avectra_Model_Soap_Typemap_IndividualAddress_Address_DataObjectType::toXml'
                    ),
                )
            ));

            $this->__client->disableCaching();
            $this->setHasConnection(true);
        } catch (Exception $e) {
            $this->setHasConnection(false);
        }
    }

    public function hasConnection()
    {
        return $this->__has_connection;
    }

    private function setHasConnection($boolean)
    {
        $this->__has_connection = $boolean;
    }

    private function logSoapDebug()
    {
        Mage::log("
            Last Request Headers:
            " . $this->__client->__getLastRequestHeaders() . "\n
            Last Request :
            " . $this->__client->__getLastRequest() . "\n
            Last Response Headers :
            " . $this->__client->__getLastResponseHeaders() . "\n
            Last Response :
            " . $this->__client->__getLastResponse() . "\n\n", null, 'avectra-communication.log', true
        ); /* */
    }

    /*
     * Use the returned value from the login function to find the avectra_key
     */

    public function getUserKeyByLoginAuthKey($login_result)
    {
        try {
            $validate_result = $this->__client->WebValidate(array(
                'authenticationToken' => $login_result
            ));
            return $validate_result->WebValidateResult;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function loginUser($email, $password)
    {
        try {
            $login_result = $this->__client->WebLogin(array(
                'userLoginPlain' => $email,
                'passwordPlain'  => $password,
                'keyOverride'    => ''
            ));
            return $login_result;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getUserKey($email, $password)
    {
        $login_res = $this->loginUser($email, $password);
        $user_key = $this->getUserKeyByLoginAuthKey($login_res->WebLoginResult);
        return $user_key;
    }

    public function getFacadeInd($key)
    {
        try {
            $fac_ind = $this->__client->GetFacadeObject(array(
                'szObjectKey'  => $key,
                'szObjectName' => "Individual",
            ));
            return $fac_ind;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    //getOrganizationByRec
    public function getUserByRecNo($rec_no)
    {
        try {
            $user = $this->__client->WEBWebUserGetByRecno_Custom(array(
                'cst_recno' => $rec_no
            ));
            $this->logSoapDebug();
            return $user;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getUserByIndToken($token)
    {   
        $authResult = $this->getAuthToken(); 
        $xwebNamespace = $authResult['xwebNamespace'];
        $authToken = $authResult['authToken'];

        $input_headers = new SoapHeader($xwebNamespace, 'AuthorizationToken', array('Token'=>$authToken), true);
	
        //Queue Object Intiation for Avectra Error log entry
        $updateQueue = $this->getQueueInstance()->addToQueue('icc_avectra/avectraQueue', 'authToken', array('auth_Token' => $authResult));
        $updateQueue->setCode('avectra-login');
         
        if ((bool) Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/useliveurl')) {
            $function_name = $this->__prefixCall."WEBWebUserValidateToken";
         }else{
            $function_name = "WEBWebUserValidateToken";
         }

        $arguments 		= array('parameters'=>array('authenticationToken' => $token));
        $options 		= null;
        $org_name = '';
        $output_headers = null;
        
        try
        {   
            $soapClientSetUp = array('trace'=>true,'exceptions'=>false);
            $soap_client = new SoapClient($this->__token_path,$soapClientSetUp);
            //$soap_client = new SoapClient('http://10.30.1.210/'.$this->__token_path,$soapClientSetUp);
	    $soapresponse = $soap_client->__SoapCall($function_name, $arguments, $options, $input_headers, $output_headers);
            
            //Dump Response error on Avectra Log In Call
            if ( isset($soapresponse->faultstring) ) {
                $updateQueue->setErrorMessage("SOAP Fault: (faultstring: {$soapresponse->faultstring})");
                $updateQueue->setStatus(Gorilla_Queue_Model_Queue::STATUS_ABORTED);
            }else{
               //Set the Successfull entry on inserted Queue and Save the updates
               $updateQueue->setStatus(Gorilla_Queue_Model_Queue::STATUS_SUCCESS); 
            }
        }catch (Exception $e) { 
            Mage::logException($e);             
            //Dump Response error on Avectra Log In Call
            if ($soapresponse->faultstring) {
                $updateQueue->setErrorMessage("SOAP Fault: (faultstring: {$soapresponse->faultstring})");
                $updateQueue->setStatus(Gorilla_Queue_Model_Queue::STATUS_ABORTED);
            }else{
               //Set the Successfull entry on inserted Queue and Save the updates
               $updateQueue->setStatus(Gorilla_Queue_Model_Queue::STATUS_SUCCESS); 
            }
        }
             
         //Added Request and Response on Queue Entry
         $updateQueue->setSoapRequest($soap_client->__getLastRequest());
         $updateQueue->setSoapResponse($soap_client->__getLastResponse());
                   
          //Save the Avectra login check data on Queue
          $updateQueue->setShortDescription('Avectra Login Check');
          $updateQueue->setNumberAttempts(1);
          $updateQueue->setLastAttempt(date('Y-m-d H:i:s'));
          $updateQueue->save();  
          define('WEBWebUserValidateTokenResult' , 'WEBWebUserValidateTokenResult');  
          
        if ((bool) Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/useliveurl') ) {
                $methodPrefix = $this->__prefixCall.WEBWebUserValidateTokenResult;
                define($methodPrefix , $methodPrefix);  
                $WEBWebUserValidateTokenResult = $soapresponse->$methodPrefix;              
         }else{                
                $WEBWebUserValidateTokenResult = $soapresponse->WEBWebUserValidateTokenResult;        
         }
        
        if ($soapresponse && $WEBWebUserValidateTokenResult)
        {  
            //load data from Avectra
            //Fetch the data from Avectra Response
            if ((bool) Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/useliveurl')) {
                $responseTokenPrefix = $this->__prefixCall.WEBWebUserValidateTokenResult;
                $email = $soapresponse->$responseTokenPrefix->Email->eml_address;
                $avectraKey = $soapresponse->$responseTokenPrefix->Individual->ind_cst_key;
                $emailKey = isset($soapresponse->$responseTokenPrefix->Customer->cst_eml_key) ? ($soapresponse->$responseTokenPrefix->Customer->cst_eml_key) : '';
                $responseFirstName = $soapresponse->$responseTokenPrefix->Individual->ind_first_name;
                $responseLastName = $soapresponse->$responseTokenPrefix->Individual->ind_last_name;
                $responseCustomerNumber = $soapresponse->$responseTokenPrefix->Customer->cst_recno;
            }else{
                $email = $soapresponse->WEBWebUserValidateTokenResult->Email->eml_address;
                $avectraKey = $soapresponse->WEBWebUserValidateTokenResult->Individual->ind_cst_key;
                $emailKey = isset($soapresponse->WEBWebUserValidateTokenResult->Customer->cst_eml_key) ? ($soapresponse->WEBWebUserValidateTokenResult->Customer->cst_eml_key) : '';
                $responseFirstName = $soapresponse->WEBWebUserValidateTokenResult->Individual->ind_first_name;
                $responseLastName = $soapresponse->WEBWebUserValidateTokenResult->Individual->ind_last_name;
                $responseCustomerNumber = $soapresponse->WEBWebUserValidateTokenResult->Customer->cst_recno;
            }        
            
            /*************************************************************/
                if($email){
                //$fields = array('response' => false, 'customer_exists' => 1);
                $account = Mage::getModel('icc_avectra/account');
                $avectra = Mage::getModel('icc_avectra/avectraCommunication');
                $customer =  $account->getByEmail($email);
                $avectra_key = '';
                if($customer){
                    if($avectra_key = $customer->getAvectraKey()){
                        $user_info = $avectra->getUserInfo($avectra_key);
                    } else{
                        $user_info = $avectra->getUserInfoByEmail($email);
                    }
                } else {
                    $fields['customer_exists'] = 0;
                    $user_info = $avectra->getUserInfoByEmail($email);
                }

                if($user_info){
                    $data = false;
                    if(isset($user_info->IndividualObject)){
                        $data = $user_info->IndividualObject;
                    } else if ($user_info->Individual){
                        $data = $user_info->Individual;
                    }

                    if($data){
                        $orgs = $avectra->getUserAffiliatedOrganizations($avectra_key, true);
                        if ($orgs) {                            
                            $org_name = $orgs[0]->Organization->org_name;
                            //$fields['orgname'] = (array)$org_name;
                        }
                    }
                }
               //$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($fields));
            }
            /************************************************************/
  
            $avectraData = array(
                'firstname' => $responseFirstName,
                'lastname' => $responseLastName,
                'email' => $email,
                'avectra_key' => $avectraKey,
                'customer_no' => $responseCustomerNumber,
                'email_avectra_key' => $emailKey,
                'org_name' => $org_name
            );
            
            //Counter Intilized for Execution tracking
            $timeStart = microtime(true);
                    
            $validator = new Zend_Validate_EmailAddress();
            if (empty($email) || !$validator->isValid($email))
                return null;

            //try getting customer by Avectra key in Magento
            $select = Mage::getModel('customer/customer')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('avectra_key',$avectraKey)->load();

            $customer = $select->getFirstItem();
             
            //no customer with this key in Magento
            if (!($customer && $customer->getId()))
            {
                //check if the email in Magento matches the one from Avectra
                $customer = Mage::getModel("customer/customer");
                $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                $customer->loadByEmail($email);

                if(!($customer && $customer->getId())) {
                    //Create new customer in Magento because it exists in Avectra
                    $customer = Mage::getModel('customer/customer');
                    $avectraData['password'] = substr(md5(rand()),0,10);
                    $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $customer->setConfirmation(null);
                }
            }
            $customer->addData($avectraData);
            $av_queue = Mage::getModel('icc_avectra/avectraQueue');
            try
            {
                $customer->save();
                //Retricted entry while user login
                //$av_queue->addUpdateUser($avectraKey);
                // Avectra call for syncronization of customer data
                $avectraAccount = Mage::getModel('icc_avectra/account');
                $avectraAccount->updateUser($avectraKey);

                //Customer Data Syncronize entry in queue
                $queueModel = Mage::getModel('gorilla_queue/queue');
                $this->_logSoapToQueue($queueModel, $avectraAccount);
                $queueModel->addToQueue($this->getMageModelClass(), 'updateAvectra', array('avectra_key' => $avectraKey), $code = 'update-avectra')
                    ->setShortDescription('Update User Data from Avectra : '. $email);

                $queueModel->setStatus(Gorilla_Queue_Model_Queue::STATUS_SUCCESS);
                $queueModel->setNumberOfTimesAttempted(0);
                $queueModel->save();

                //Process the Avectra Entries
                $queueProcess = Mage::getModel('gorilla_queue/queue');
                $queueProcess->load($queueModel->getId(), 'queue_id');
                $queueProcess->process();            
                $timeEnd = microtime(true);
                
                //Calculate the Execution script time on seconds
                $scriptExecutionTime = $timeEnd - $timeStart;

                //execution time of the script
                Mage::log('Total Execution Time:'.$scriptExecutionTime.' Seconds for : '. $email, null, 'avectra-communication.log', true);
            }
            catch (Exception $e) {
		$queueModel = Mage::getModel('gorilla_queue/queue'); 
		$avectraAccount = Mage::getModel('icc_avectra/account');
		$this->_logSoapToQueue($queueModel, $avectraAccount);
                $queueModel->addToQueue($this->getMageModelClass(), 'updateAvectra', array('avectra_key' => $avectraKey), $code = 'update-avectra')
                    ->setShortDescription('Update Avectra with Magento changes : '. $email); 
                $queueModel->setStatus(Gorilla_Queue_Model_Queue::STATUS_ABORTED);
                $queueModel->setErrorMessage('SOAP Fault:'.$e->getMessage());
                $queueModel->setNumberOfTimesAttempted(0);
                $queueModel->save();
            }
            
            if ($customer && $customer->getId())
                return $customer;
        }
    }

    public function _logSoapToQueue($q, $account)
    {
        $client = $account->getAvComm()->getClient();
        if ($client instanceof SoapClient) {
            $q->setSoapRequest($account->getAvComm()->getClient()->__getLastRequest());
            $q->setSoapResponse($account->getAvComm()->getClient()->__getLastResponse());
        }
    }
    
    public function getMageModelClass()
    {
        return 'icc_avectra/avectraQueue';
    }
    
    public function getQueueInstance()
    {
        return Mage::getModel('gorilla_queue/queue');
    }
    
    public function getUserInfo($avectra_key)
    {
        if (!is_null($this->__user_info)) return $this->__user_info;
        try {
            $av_user_info = $this->__client->WEBIndividualGet(array('key' => $avectra_key));
            //$this->logSoapDebug();
            $this->__user_info = $av_user_info->WEBIndividualGetResult;
            $this->logSoapDebug();
            return $this->__user_info;
        } catch (Exception $e) {
            $this->logSoapDebug();
            return false;
        }
        return false;
    }

    public function getPhoneByKey($key)
    {
        $params = array('key' => (string) $key);
        try {
            $phone_response = $this->__client->WEBPhoneGet($params);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        // inserted try catch as not all avectra users have a phone number.
        // not necessarily the best way to do this.
        try {
            $return = (string) $phone_response->WEBPhoneGetResult->Phone->phn_number;
        } catch (Exception $e) {
            $return = "5555555555";
        }
        return $return;
    }

    public function getNode($xml)
    {
        $o_update_node = new SoapParam("this", "doesntmatter");
        $o_update_node->any = $xml;
        return $o_update_node;
    }

    public function updateAvectraName($firstname, $lastname, $av_key, $email, $email_key)
    {
        $xml = "
            <oFacadeObject>
                <CurrentKey>$av_key</CurrentKey>
                <Individual>
                    <ind_cst_key>$av_key</ind_cst_key>
                    <ind_first_name>$firstname</ind_first_name>
                    <ind_last_name>$lastname</ind_last_name>
                </Individual>";
        if (!empty($email_key)) {
            $xml .= "
                <Email>
                    <eml_key>$email_key</eml_key>
                    <eml_cst_key>$av_key</eml_cst_key>
                    <eml_address>$email</eml_address>
                </Email>";
        }
        $xml .= "
            </oFacadeObject>
        ";

        $xml_node = simplexml_load_string($xml);

        try {
            $update_result = $this->__client->WEBIndividualUpdate(array(
                'oFacadeObject' => $xml_node
            ));
            $this->logSoapDebug();
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        return (bool) (int) $update_result->WEBIndividualUpdateResult;
    }

    public function deleteAvectraAddress($avectra_key, $customer_av_key, $add_to_queue = true)
    {
        $xml = "
            <oFacadeObject>
                <CurrentKey>$avectra_key</CurrentKey>
                <Address_XRef>
                    <cxa_cst_key>$customer_av_key</cxa_cst_key>
                    <cxa_delete_flag>1</cxa_delete_flag>
                    <cxa_key>$avectra_key</cxa_key>
                </Address_XRef>
            </oFacadeObject>
        ";
        $xml_node = simplexml_load_string($xml);

        try {
            $update_result = $this->__client->WEBAddressUpdate(array(
                'oFacadeObject' => $xml_node
            ));
            return (bool) (int) $update_result->WEBAddressUpdateResult;
        } catch (Exception $e) {
            Mage::logException($e);
            if ($add_to_queue) {
                $av_queue = Mage::getModel('icc_avectra/avectraQueue');
                $av_queue->addDeleteAvectraAddress($avectra_key, $customer_av_key, $e->getMessage());
            }
            return false;
        }
    }

    public function updateAvectra($avectra_key)
    {
        $account = Mage::getModel('icc_avectra/account');
        $customer = $account->getUserByAvectraKey($avectra_key);
        if (!$customer || !$this->hasConnection()) {
            return false;
        }
        try {
            $update_name_response = $this->updateAvectraName($customer->getFirstname(), $customer->getLastname(), $customer->getAvectraKey(), $customer->getEmail(), $customer->getEmailAvectraKey());

            $addresses = $customer->getAddresses();
            $return_success = true;
            foreach ($addresses as $address) {
                if ($address->getIsAffiliatedOrg()) {
                    continue;
                }
                $av_key = $address->getAvectraKey();
                if (empty($av_key)) {
                    $success = $this->addNewAvectraAddress($address);
                } else {
                    $success = $this->updateAvectraAddress($address);
                }
                if (!$success) {
                    $return_success = false; // if any fail we need to return false
                }
            }
            return ($return_success && $update_name_response);
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    public function addNewAvectraAddress($mage_address)
    {
        try {
            $xml_obj = $this->_getFacadeAddressObject($mage_address);
            $insert_response = $this->__client->WEBAddressInsert(array(
                'oFacadeObject' => $xml_obj,
            ));

            
            
            $avectra_cxa_key = isset($insert_response->WEBAddressInsertResult->Address_XRef->cxa_key);
            $mage_address->setAvectraKey($avectra_cxa_key);
            $mage_address->save();
            $this->logSoapDebug();
            return true;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    protected function _getFacadeAddressObject($mage_address)
    {
        $customer = $mage_address->getCustomer();
        $is_billing = (int) $customer->isAddressPrimary($mage_address);

        $country = '';
        $countryId = $mage_address->getCountryId();
        if (!empty($countryId)) {
            $countryModel = Mage::getModel('directory/country')->loadByCode($countryId);
            $country = strtoupper($countryModel->getName());
        }
        $regionCode = $mage_address->getRegionCode();

        // if we can load a region model with the Country and RegionCode then we have a valid combo
        // if not then the user entered an invalid region
        // if the country / region were added from Avectra they must already pass the join
        // if they were added from Magento then the country is a dropdown and the region is a text input
        $regionModel = Mage::getModel('directory/region')->loadByCode($regionCode, $countryId);
        if (!$regionModel->getRegionId()) {
            $regionCode = null; // valid region id
        }

        $xml = "
            <oFacadeObject>
                <CurrentKey>{$mage_address->getAvectraKey()}</CurrentKey>
                <Address_XRef>
                    <cst_cxa_key xsi:nil=\"true\"/>
                    <cxa_cst_key>{$customer->getAvectraKey()}</cxa_cst_key>
                    <cxa_billing>{$is_billing}</cxa_billing>
                    <cxa_adt_key>063cf4e2-1fa4-4e63-9f2b-42d684bc3fdd</cxa_adt_key>
                    <cxa_key>{$mage_address->getAvectraKey()}</cxa_key>
                </Address_XRef>
                <Address>
                    <adr_line1>{$mage_address->getStreet(1)}</adr_line1>";
        if ($mage_address->getStreet(2)) {
            $xml .= "<adr_line2>{$mage_address->getStreet(2)}</adr_line2>\n";
        }
        if (!is_null($regionCode)) {
            $xml .= "<adr_state>$regionCode</adr_state>";
        }
        $xml .= "   <adr_city>{$mage_address->getCity()}</adr_city>
                    <adr_post_code>{$mage_address->getPostcode()}</adr_post_code>
                    <adr_country>$country</adr_country>
                </Address>
            </oFacadeObject>
        ";
        $xml_obj = @simplexml_load_string($xml);
        return $xml_obj;
    }

    public function updateAvectraAddress($mage_address)
    {
        try {
            $xml_obj = $this->_getFacadeAddressObject($mage_address);

            $update_response = $this->__client->WEBAddressUpdate(array(
                'oFacadeObject' => $xml_obj,
            ));
            $this->logSoapDebug();
            return (bool) (int) $update_response->WEBAddressUpdateResult;
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    public function updateNewPhone($customer_av_key, $phone_number)
    {
        $plain_phone_number = preg_replace('/\D/', '', $phone_number);
        $xml = "
        <oFacadeObject>
            <Phone_XRef>
                <cph_cst_key>$customer_av_key</cph_cst_key>                
                <cph_primary>1</cph_primary>
            </Phone_XRef>
            <Phone>
                <phn_number>$plain_phone_number</phn_number>
                <phn_number_display>$phone_number</phn_number_display>
                <phn_cst_key_owner>$customer_av_key</phn_cst_key_owner>
            </Phone>
        </oFacadeObject>
        ";
        $xml_node = simplexml_load_string($xml);
        try {
            $update_result = $this->__client->WEBPhoneInsert(array(
                'oFacadeObject' => $xml_node
            ));
        } catch (Exception $e) {
            return false;
        }
        return $update_result->WEBPhoneInsertResult;
    }

    public function updateDemographics($update_info)
    {
        $xml = "
            <oFacadeObject>
                <Individuals>
                    <Individual>
                        <ind_cst_key>{$update_info['key']}</ind_cst_key>
                        <ind_industry_ext>{$update_info['ind_industry_ext']}</ind_industry_ext>
                        <ind_trade_ext>{$update_info['ind_trade_ext']}</ind_trade_ext>
                        <ind_specialty_ext>{$update_info['ind_specialty_ext']}</ind_specialty_ext>
                    </Individual>
                </Individuals>
           </oFacadeObject>
        ";

        $xml_node = $this->getNode($xml);
        try {
            $update_response = $this->__client->UpdateFacadeObject(array(
                'szObjectName' => 'Individual',
                'szObjectKey'  => $update_info['key'],
                'oNode'        => $xml_node,
            ));

            return $update_response;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    private function updateAvectraPhone($phone_key, $phone_number, $customer_av_key)
    {
        $plain_phone_number = preg_replace('/\D/', '', $phone_number);
        $xml = "
        <oFacadeObject>
            <Phone_XRef>
                <cph_cst_key>$customer_av_key</cph_cst_key>
                <cph_key>$phone_key</cph_key>
            </Phone_XRef>
            <Phone>
                <phn_key>$phone_key</phn_key>
                <phn_number>$plain_phone_number</phn_number>
                <phn_number_display>$phone_number</phn_number_display>
                <phn_cst_key_owner>$customer_av_key</phn_cst_key_owner>
            </Phone>
        </oFacadeObject>
        ";
        $xml_node = simplexml_load_string($xml);
        try {
            $update_result = $this->__client->WEBPhoneUpdate(array(
                'oFacadeObject' => $xml_node,
            ));
            return (bool) (int) $update_result->WEBPhoneUpdateResult;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getVersion()
    {
        $version = $this->__client->GetVersion();
        print_r($version);
        die;
    }

    public function getWebUser($av_key)
    {
        $params = array('cst_key' => (string) $av_key);
        try {
            $web_user = $this->__client->WEBWebUserGet($params);
            return $web_user;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getOrganizationInformation($org_key)
    {
        if (empty($org_key)) {
            return false;
        }
        try {
            $org_info = $this->__client->WEBOrganizationGet(array(
                'key' => $org_key
            ));

            return $org_info->WEBOrganizationGetResult;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getAffiliationKeys($av_key, $primary = NULL)
    {
        $keys = array();

        // GEMS change
        if ($primary) {
            $userInfo = $this->getUserInfo($av_key);
            if (is_object($userInfo) && isset($userInfo->Organization_XRef) && isset($userInfo->Organization_XRef->ixo_org_cst_key)) {
                $keys[] = (string) $userInfo->Organization_XRef->ixo_org_cst_key;
            }
        } else {
            $affiliation_object = $this->getUserAffiliation($av_key);
            $keys = array();
            if ($affiliation_object) {
                foreach ($affiliation_object->IndividualAffiliationObject as $obj) {
                    $keys[] = (string) $obj->org_cst_key;
                }
            }
        }
        return $keys;
    }

    public function getUserAffiliatedOrganizations($av_key, $primary)
    {
        $org_keys = $this->getAffiliationKeys($av_key, $primary);

        $orgObjects = array();
        foreach ($org_keys as $org_key) {
            if (!empty($org_key)) {
                $org_info = $this->getOrganizationInformation($org_key);
                $orgObjects[] = $org_info;
            }
        }

        return $orgObjects;
    }

    public function getUserOrgCustomerNo($av_key)
    {
        $org_obj = $this->getUserAffiliatedOrganization($av_key);
        if (is_object($org_obj)) {
            return $org_obj->OrganizationObject->cst_id;
        }
        return false;
    }

    public function getUserOrgCustomerNoRollback($av_key)
    {
        //   echo "asdfasdf";
        $org_obj = $this->getUserAffiliation($av_key);
        //    print_r($org_obj);
        if (is_object($org_obj)) {
            //        echo "is object";
            //       echo  $org_obj->IndividualAffiliationObject->cog__cst_recno;

            $recno = $org_obj->IndividualAffiliationObject->cog__cst_recno[0];
            //       echo $recno;
            // die;
            return array((string) $recno);
        }
        return false;
    }

    public function getUserAffiliation($av_key)
    {
        try {
            $affiliation = $this->__client->GetQuery(array(
                'szObjectName'  => 'IndividualAffiliation',
                'szColumnList'  => '*',
                'szWhereClause' => "ixo_ind_cst_key = '$av_key' and ixo_delete_flag = '0'"
            ));
            if (isset($affiliation->GetQueryResult->any)) {
                $affiliation->GetQueryResult->any = str_ireplace('xsi:schemaLocation="http://www.avectra.com/2005/ IndividualAffiliation.xsd"', 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"', $affiliation->GetQueryResult->any);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        try {
            $user_affiliation_object = simplexml_load_string($affiliation->GetQueryResult->any);
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
        if (isset($user_affiliation_object->IndividualAffiliationObject)) {
            return $user_affiliation_object;
        }
        return false; // no affiliation
    }

//    public function getPrimaryOrgFromObject($orgObj)
//    {
//        print_r($orgObj); die;
//        if(isset($orgObj))
//        {
//            foreach($orgObj as $org)
//            {
//                echo '<pre>';
//                print_r($org); 
//                echo "\n\n+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++\n\n";
//            }
//        }
//        ->IndividualAffiliationObject->org_cst_key
//    }


    public function getPrimaryAddress($customer_av_key)
    {
        $addresses = $this->getCustomerAddresses($customer_av_key);
        foreach ($addresses as $simple_address) {
            $address = $this->getAddressByKey($simple_address->cxa_key);
            if ((bool) (int) $address->Address_XRef->cxa_primary) {
                return $address;
            }
        }
        if (isset($address)) {
            return $address; // we're just defaulting to something if we can't find a primary
        }
        return false;
    }

    /** @return arrary of SimpleXML */
    public function getCustomerAddresses($customer_av_key)
    {
        try {
            $addresses_response = $this->__client->WEBAddressGetAddressesByCustomer(array(
                'CustomerKey' => $customer_av_key
            ));
            if (isset($addresses_response->WEBAddressGetAddressesByCustomerResult->any)) {
                $addresses_obj = simplexml_load_string($addresses_response->WEBAddressGetAddressesByCustomerResult->any);

                $addresses = array();
                foreach ($addresses_obj as $addy) {
                    if ($addy->adt_code == 'CERTIFICATION')
                        continue; // skip certification addresses (part of functional specs)
                    $addresses[] = $addy;
                }
                return $addresses;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /** @return SimpleXML */
    public function getAddressByKey($avectra_key)
    {
        try {
            $address_response = $this->__client->WEBAddressGet(array(
                'key' => $avectra_key
            ));
            return $address_response->WEBAddressGetResult;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getPrimaryPhone($customer_avectra_key)
    {
        $phones = $this->getCustomerPhones($customer_avectra_key);
        foreach ($phones as $simple_phone) {
            $phone = $this->getPhoneObjectByKey((string) $simple_phone->cph_key);
            if ((bool) (int) $phone->Phone_XRef->cph_primary) {
                return $phone;
            }
        }
        if (isset($phone)) {
            return $phone; // we're just defaulting to something if we can't find a primary
        }
        return false;
    }

    public function getCustomerPhones($customer_avectra_key)
    {
        try {
            $phones_response = $this->__client->WEBPhoneGetPhonesByCustomer(array(
                'CustomerKey' => $customer_avectra_key
            ));
            if (isset($phones_response->WEBPhoneGetPhonesByCustomerResult->any)) {
                $phones_obj = simplexml_load_string($phones_response->WEBPhoneGetPhonesByCustomerResult->any);
                $phones = array();

                foreach ($phones_obj->Result as $phone_obj) {
                    $phones[] = $phone_obj;
                }
                return $phones;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getPhoneObjectByKey($avectra_key)
    {
        try {
            $phone_response = $this->__client->WEBPhoneGet(array(
                'key' => $avectra_key
            ));
            return $phone_response->WEBPhoneGetResult;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getGetObjectList()
    {
        try {
            $list = $this->__client->GetFacadeObjectList();
            return simplexml_load_string($list->GetFacadeObjectListResult->any);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    //add by GEMS
    public function getUserInfoByEmail($email)
    {
        Mage::log("attempting to get user by email", NULL, 'avectra-communication.log', true);
        try {
            $result = $this->__client->GetQuery(array('szObjectName'  => 'Individual',
                'szColumnList'  => '*',
                'szWhereClause' => "eml_address='{$email}'"));
            $user_info = simplexml_load_string($result->GetQueryResult->any, "SimpleXMLElement", LIBXML_NOERROR + LIBXML_NOWARNING);
            $this->logSoapDebug();
            Mage::log("success", 'avectra-communication.log');
        } catch (Exeption $e) {
            Mage::logExeption($e);
            Mage::log("fail", 'avectra-communication.log');
            return false;
        }

        if (isset($user_info->IndividualObject)) {
            return $user_info;
        }
        return false;
    }

    //retrieves login token from Avectra which is valid for one soap request (uses local wsdl)
    public function getAuthToken()
    {
        $soapClientSetUp = array('trace'=>true,'exceptions'=>false);
        $soap_client = new SoapClient($this->__token_path,$soapClientSetUp);
        //$soap_client = new SoapClient('http://10.30.1.210/'.$this->__token_path,$soapClientSetUp);  
        $function_name 	= "Authenticate";

        $arguments 		= array('parameters'=> array('userName'=>$this->__nf_user_name, 'password' => $this->__nf_user_pass));
        $options 		= null;
        $input_headers 	= null;
        $output_headers = null;

        try
        {
            $soapresponse = $soap_client->__SoapCall($function_name, $arguments, $options, $input_headers, $output_headers);
            
        }
        catch(SoapFault $exception) { Mage::logException($exception); }

        if (!$soapresponse || !$soapresponse->AuthenticateResult)
            return null;

        $xwebNamespace = $soapresponse->AuthenticateResult;
        $authToken = $output_headers['AuthorizationToken']->Token;

        return array('xwebNamespace'=>$xwebNamespace, 'authToken'=>$authToken);
    }

    //makes a WEBIndividualGet request to retrieve all customer info (must use external wsdl)
    public function getCustomerInfoWithToken($avectra_key)
    {
        $authResult = $this->getAuthToken();
        $xwebNamespace = $authResult['xwebNamespace'];
        $authToken = $authResult['authToken'];

        $input_headers = new SoapHeader($xwebNamespace, 'AuthorizationToken', array('Token'=>$authToken), true);

        $function_name = "WEBIndividualGet";

        $arguments 		= array('parameters'=>array('key' => $avectra_key));
        $options 		= null;
        $output_headers = null;
        try
        {
            $soapresponse = $this->__client->__SoapCall($function_name, $arguments, $options, $input_headers, $output_headers);
        }
        catch (Exception $e) { Mage::logException($e); }
        $x = $soapresponse->WEBIndividualGetResult;
        if ($soapresponse && $soapresponse->WEBIndividualGetResult)
            return $soapresponse->WEBIndividualGetResult;
    }

    //returns the cst_delete_flag from a customer using their Avectra key
    public function getIsDeleted($avectra_key) {
        if($customerData = $this->getCustomerInfoWithToken($avectra_key))
            return $customerData->Customer->cst_delete_flag;
    }

    public function getClient()
    {
        return $this->__client;
    }

    //end add by GEMS
    
    public function getCustomerByIndToken($token) {
        $authResult = $this->getAuthToken();
        $xwebNamespace = $authResult['xwebNamespace'];
        $authToken = $authResult['authToken'];

        $input_headers = new SoapHeader($xwebNamespace, 'AuthorizationToken', array('Token' => $authToken), true);

        if ((bool) Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/useliveurl')) {
            $function_name = $this->__prefixCall . "WEBWebUserValidateToken";
        } else {
            $function_name = "WEBWebUserValidateToken";
        }

        $arguments = array('parameters' => array('authenticationToken' => $token));
        $options = null;
        $org_name = '';
        $output_headers = null;
        define('WEBWebUserValidateTokenResult', 'WEBWebUserValidateTokenResult'); 
        
        try {
            $soapClientSetUp = array('trace' => true, 'exceptions' => false);
            $soap_client = new SoapClient($this->__token_path, $soapClientSetUp);
	    //$soap_client = new SoapClient('http://10.30.1.210/' . $this->__token_path, $soapClientSetUp);
            $soapresponse = $soap_client->__SoapCall($function_name, $arguments, $options, $input_headers, $output_headers);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        if ((bool) Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/useliveurl')) {
            $methodPrefix = $this->__prefixCall.WEBWebUserValidateTokenResult;
            $WEBWebUserValidateTokenResult = $soapresponse->$methodPrefix;
        } else {
            $WEBWebUserValidateTokenResult = $soapresponse->WEBWebUserValidateTokenResult;
        }

        if ($soapresponse && $WEBWebUserValidateTokenResult) {
            //load data from Avectra
            //Fetch the data from Avectra Response
            if ((bool) Mage::getStoreConfig('avectraconnect_options/avectraconfigfields/useliveurl')) {
                $responseTokenPrefix = $this->__prefixCall . WEBWebUserValidateTokenResult;
                $email = $soapresponse->$responseTokenPrefix->Email->eml_address;
                $avectraKey = $soapresponse->$responseTokenPrefix->Individual->ind_cst_key;
                $emailKey = isset($soapresponse->$responseTokenPrefix->Customer->cst_eml_key) ? ($soapresponse->$responseTokenPrefix->Customer->cst_eml_key) : '';
                $responseFirstName = $soapresponse->$responseTokenPrefix->Individual->ind_first_name;
                $responseLastName = $soapresponse->$responseTokenPrefix->Individual->ind_last_name;
                $responseCustomerNumber = $soapresponse->$responseTokenPrefix->Customer->cst_recno;
            } else {
                $email = $soapresponse->WEBWebUserValidateTokenResult->Email->eml_address;
                $avectraKey = $soapresponse->WEBWebUserValidateTokenResult->Individual->ind_cst_key;
                $emailKey = isset($soapresponse->WEBWebUserValidateTokenResult->Customer->cst_eml_key) ? ($soapresponse->WEBWebUserValidateTokenResult->Customer->cst_eml_key) : '';
                $responseFirstName = $soapresponse->WEBWebUserValidateTokenResult->Individual->ind_first_name;
                $responseLastName = $soapresponse->WEBWebUserValidateTokenResult->Individual->ind_last_name;
                $responseCustomerNumber = $soapresponse->WEBWebUserValidateTokenResult->Customer->cst_recno;
            }

            /*********************************************************** */
            if ($email) {
                $account = Mage::getModel('icc_avectra/account');
                $avectra = Mage::getModel('icc_avectra/avectraCommunication');
                $customer = $account->getByEmail($email);
                if ($customer) {
                    if ($avectra_key = $customer->getAvectraKey()) {
                        $user_info = $avectra->getUserInfo($avectra_key);
                    } else {
                        $user_info = $avectra->getUserInfoByEmail($email);
                    }
                } else {
                    $fields['customer_exists'] = 0;
                    $user_info = $avectra->getUserInfoByEmail($email);
                }

                if ($user_info) {
                    $data = false;
                    if (isset($user_info->IndividualObject)) {
                        $data = $user_info->IndividualObject;
                    } else if (isset($user_info->Individual)) {
                        $data = $user_info->Individual;
                    }

                    if ($data) {
                        $orgs = $avectra->getUserAffiliatedOrganizations($avectra_key, true);
                        if ($orgs) {
                            $org_name = $orgs[0]->Organization->org_name;
                        }
                    }
                }
            }
            $avectraData = array(
                'firstname' => $responseFirstName,
                'lastname' => $responseLastName,
                'email' => $email,
                'avectra_key' => $avectraKey,
                'customer_no' => $responseCustomerNumber,
                'email_avectra_key' => $emailKey,
                'org_name' => $org_name
            );

            $validator = new Zend_Validate_EmailAddress();
            if (empty($email) || !$validator->isValid($email))
                return null;

            //try getting customer by Avectra key in Magento
            $select = Mage::getModel('customer/customer')
                            ->getCollection()
                            ->addAttributeToSelect('*')
                            ->addAttributeToFilter('avectra_key', $avectraKey)->load();

            $customer = $select->getFirstItem();

            //no customer with this key in Magento
            if (!($customer && $customer->getId())) {
                //check if the email in Magento matches the one from Avectra
                $customer = Mage::getModel("customer/customer");
                $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                $customer->loadByEmail($email);

                if (!($customer && $customer->getId())) {
                    //Create new customer in Magento because it exists in Avectra
                    $customer = Mage::getModel('customer/customer');
                    $avectraData['password'] = substr(md5(rand()), 0, 10);
                    $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $customer->setConfirmation(null);
                }
            }
            $customer->addData($avectraData);
            try {
                $customer->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
            if ($customer && $customer->getId())
                return $customer;
        }
    }

}
