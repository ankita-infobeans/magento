<?php
/*
Product code: ecodes product id like IC-P-2009-000002, IC-P-2012-000003
*/

class ICC_Ecodes_Model_Api {

    private $__add_to_queue = true;
    private $__add_to_queue_functions = array( // default functions that are always added to Queue on failure
        'AppendProduct',
    );
    
	protected $_wsdl;
	protected $_login;
	protected $_password;
	protected $_partnerId;
	protected $_portalId;
    protected $_wait_time;
    protected $_has_connection = null;
    
	protected function getWsdl()
    {
            //return 'http://ecodes.citation.com/does/not/exist/webservices/iccconnect/service.asmx?WSDL';
		if (!$this->_wsdl) $this->_wsdl = Mage::getStoreConfig('iccconnect_options/configfields/apiurl');
		return $this->_wsdl;
	}
	public function getLogin()
    {
		if (!$this->_login) $this->_login = Mage::getStoreConfig('iccconnect_options/configfields/apiusername');
		return $this->_login;
	}
	public function getPassword()
    {
		if (!$this->_password) $this->_password = Mage::getStoreConfig('iccconnect_options/configfields/apipassword');
		return $this->_password;
	}
	public function getPartnerId()
    {
		if (!$this->_partnerId) $this->_partnerId = Mage::getStoreConfig('iccconnect_options/configfields/apipartnerid');
		return $this->_partnerId;
	}
	public function getPortalId()
    {
		if (!$this->_portalId) $this->_portalId = Mage::getStoreConfig('iccconnect_options/configfields/apiportalid');
		return $this->_portalId;
	}
    protected function getWaitTime()
    {
        if (!$this->_wait_time) $this->_wait_time = Mage::getStoreConfig('iccconnect_options/configfields/waittime');
    return $this->_wait_time;
	}
    public function hasConnection()
    {
        if(is_null($this->_has_connection)) $this->_has_connection = $this->testConnection();
        return $this->_has_connection;
    }
    protected function testConnection()
    {
        $curl = curl_init($this->getWsdl());
//        $curl = curl_init('http://local.iccsafe.org/timeouttest.php');
        curl_setopt($curl, CURLOPT_VERBOSE, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, '<Params></Params>');
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->getWaitTime());
        
        $response = curl_exec($curl);

		if($response === false)
		{
		    Mage::log(print_r(curl_error($curl),1),null,"api_test.log");
		}

        curl_close($curl);
        return (bool) $response;
    }
    
	public function makeApiCall($function, $xml) {
			Mage::log("FUNCTION: " . $function,null,"ecode_api.log");
			Mage::log("XML: " . $xml,null,"ecode_api.log");
            $call_args = array( 'function' => $function, 'xml' => $xml);
            try {
                $client = new SoapClient( $this->getWsdl(), array( 'exceptions' => 1, 'trace'=> 1, 'connection_timeout' => $this->getWaitTime() ));

                $result = $client->__soapCall($function, array(array('sxXml' => $xml)));
            	Mage::log("RESULT: " .print_r( $result,true),null,"ecode_api.log");
                $xmlObj = $this->validateResponse($result, $function);
                
                if ($xmlObj === false) {
                    $return_array = array('success' => false, 'message' => 'Invalid response from ICC Connect service');
                } else {
                    if ($xmlObj->getName() == "Error") {
                        $return_array =  array('success' => false, 'message' => (string)$xmlObj);
                    } else {
                        $return_array =  array('success' => true, 'obj' => $xmlObj);
                    }
                }
                if( ! $return_array['success'] &&  in_array($function, $this->__add_to_queue_functions) && $this->__add_to_queue )
                {
                    $this->_addToQueue($call_args, $return_array['message']);
                }
    			Mage::log("STATUS: " . var_export($return_array,true),null,"ecode_api.log");
                return $return_array;
            } catch (Exception $ex) {
                Mage::log('ICC Connect API Exception ' . (string)$ex, null, 'icc_connect.log');
                if($function !="CreateGuid") {
	                if(in_array($function, $this->__add_to_queue_functions) || $this->__add_to_queue )
	                {
	                    $this->_addToQueue($call_args, (string)$ex );
	                    $return_array = array('success' => true, 'message' => (string)$ex);
	                }
	                else {
		                $return_array = array('success' => false, 'message' => (string)$ex);
		            }
		        }
    			Mage::log("STATUS: " . var_export($return_array,true),null,"ecode_api.log");
                return $return_array;
            }
            $return_array = array('success' => false, 'message' => 'Could not reach server.');
			Mage::log("STATUS: " . var_export($return_array,true),null,"ecode_api.log");
            return $return_array;
	}

    protected function _addToQueue( $api_call_args, $description )
    {   
        $q = Mage::getModel('gorilla_queue/queue');
        $q->addToQueue('ecodes/apiQueue', 'processApi', $api_call_args, $api_call_args['function'] )->setShortDescription( $description )->save();
    }
    
    public function setAddToQueueFlag($bool)
    {
        $this->__add_to_queue = $bool;
    }
	//Processes the result from the service
	//Returns simpleXml object on success, false on failure
	protected function validateResponse($result, $function) {
		$resultAry = get_object_vars($result);
		$resultXml = $resultAry[$function .'Result'];
		$resultXmlObj = simplexml_load_string($resultXml);

		if ($resultXmlObj !== false) {
			return $resultXmlObj;
		} 

		//Fix bugs found in responses from service
		if (strpos($resultXml, 'Code"') !== false) $resultXml = str_replace('Code"', 'Code="', $resultXml);
		if (strpos($resultXml, 'Id=""r') !== false) $resultXml = str_replace('Id=""r', 'Id=""', $resultXml);

		$resultXmlObj = simplexml_load_string($resultXml);

		if ($resultXmlObj !== false) {
			return $resultXmlObj;
		} 

		Mage::log('ICC Connect invalid xml - ' . $resultXml, null, 'icc_connect.log');

		return false;
	}

	public function createGuid() {
		$xml = '<Params>';
		$xml .= '<User>' . $this->getLogin() . '</User>';
		$xml .= '<Pass>' . $this->getPassword() . '</Pass>';
		$xml .= '<PartnerId>' . $this->getPartnerId() . '</PartnerId>';
		$xml .= '</Params>';
		$result = $this->makeApiCall('CreateGuid', $xml);
		if ($result['success']) {
			return (string)$result['obj'];
		} else {
            Mage::log('could not create guid: ' . $result['message'], null, 'ecodes-api.log');
			throw new Exception($result['message']);
		}
	}

	public function createCompany($username, $password, $firstName, $lastName, $email) {            
		$xml = '<Params>';
		$xml .= '<Guid>' . $this->createGuid() . '</Guid>';
		$xml .= '<CompanyName>' . $username . '</CompanyName>';
		$xml .= '<PortalId>' . $this->getPortalId() . '</PortalId>';
		$xml .= '<ExpireDate>' . date('Y-m-d', time() + (60*60*24*365*20)) . '</ExpireDate>';
		$xml .= '<MasterUser>';
		$xml .= '  <FirstName>' . $firstName . '</FirstName>';
		$xml .= '  <LastName>' . $lastName . '</LastName>';
		$xml .= '  <Email>' . $email . '</Email>';
		$xml .= '  <User>' . $username . '</User>';
		$xml .= '  <Pass>' . $password . '</Pass>';
		$xml .= '</MasterUser>';
		$xml .= '</Params>';

                return $this->makeApiCall('CreateCompany', $xml);
	}

	public function updateCompany($username, $password) {
		$xml = '<Params>';
		$xml .= '<Guid>' . $this->createGuid() . '</Guid>';
		$xml .= '<MUser>' . $username . '</MUser>';
		$xml .= '<MPass>' . $password . '</MPass>';
		$xml .= '<PartnerId>' . $this->getPartnerId() . '</PartnerId>';
		$xml .= '<CompanyName>' . $username . '</CompanyName>';
		$xml .= '<PortalId>' . $this->getPortalId() . '</PortalId>';
		$xml .= '<ExpireDate>' . date('Y-m-d', time() + (60*60*24*365*20)) . '</ExpireDate>';
		$xml .= '</Params>';

		return $this->makeApiCall('UpdateCompany', $xml);
	}

	public function deleteCompany($username, $password) {
		$xml = '<Params>';
		$xml .= '<Guid>' . $this->createGuid() . '</Guid>';
		$xml .= '<User>' . $username . '</User>';
		$xml .= '<Pass>' . $password . '</Pass>';
		$xml .= '</Params>';

		return $this->makeApiCall('DeleteCompany', $xml);
	}

	public function checkMasterCredentials($username, $password) {
		return $this->updateSelf($username, $password);
	}

	public function checkUserCredentials($username, $password) {
		return $this->updateSelf($username, $password);
	}

	public function doesUserExist($username) {
		$xml = '<Params>';
		$xml .= '<Guid>' . $this->createGuid() . '</Guid>';
		$xml .= '<User>' . $username . '</User>';
		$xml .= '</Params>';

		return $this->makeApiCall('DoesUserExist', $xml);
	}

	public function updateSelf($username, $password, $newPassword = null) {
		$xml = '<Params>';
		$xml .= '<Guid>' . $this->createGuid() . '</Guid>';
		$xml .= '<User>' . $username . '</User>';
		$xml .= '<Pass>' . $password . '</Pass>';
		if ($newPassword) $xml .= '<NewPass>' . $newPassword . '</NewPass>';
		$xml .= '</Params>';

		return $this->makeApiCall('UpdateSelf', $xml);
	}

	public function updateUser($mUsername, $mPassword, $username, $password, $firstname, $lastname, $email) {
        
            $xml = '<Params>';
            $xml .= '<Guid>' . $this->createGuid() . '</Guid>';  //Required
//            $xml .= '<MUser>' . $mUsername . '</MUser>'; // Required
//            $xml .= '<MPass>' . $mPassword . '</MPass>'; // Required
            $xml .= '<FirstName>' . $firstname . '</FirstName>'; // l
            $xml .= '<LastName>' . $lastname . '</LastName>'; 
            $xml .= '<Email>' . $email . '</Email>';
            $xml .= '<User>' . $username . '</User>'; // Required
            $xml .= '<Pass>' . $password . '</Pass>';
            //$xml .= '<MakeMaster>' .''. '</MakeMaster>'; // Default (0)
            $xml .= '<PortalId>' . $this->getPortalId() . '</PortalId>'; // Required
            $xml .= '<ExpireDate>' .date('Y-m-d', time() + (60*60*24*365*20)). '</ExpireDate>'; // Required
            $xml .= '</Params>';
            
            return $this->makeApiCall('UpdateUser', $xml);
        }
	public function createUser($mUsername, $mPassword, $username, $password, $firstname, $lastname, $email) {
		$xml = '<Params>';
		$xml .= '<Guid>' . $this->createGuid() . '</Guid>';
//		$xml .= '<MUser>' . $mUsername . '</MUser>';
//		$xml .= '<MPass>' . $mPassword . ' </MPass>';
		$xml .= '<Partnerld>' . $this->getPartnerId() . '</Partnerld>';
		$xml .= '<FirstName>' . $firstname . '</FirstName>';
		$xml .= '<LastName>' . $lastname . '</LastName>';
		$xml .= '<Email>' . $email . '</Email>';
		$xml .= '<User>' . $username . '</User>';
		$xml .= '<Pass>' . $password . '</Pass>';
		$xml .= '<PortalId>' . $this->getPortalId() . '</PortalId>';
		$xml .= '<ExpireDate>' . date('Y-m-d', time() + (60*60*24*365*20)) . '</ExpireDate>';
		$xml .= '</Params>';

		return $this->makeApiCall('CreateUser', $xml);
	}

	public function deleteUser($mUsername, $mPassword, $username) {
		$xml = '<Params>';
		$xml .= '<Guid>' . $this->createGuid() . '</Guid>';
//		$xml .= '<MUser>' . $mUsername . '</MUser>';
//		$xml .= '<MPass>' . $mPassword . '</MPass>';
		$xml .= '<User>' . $username . '</User>';
		$xml .= '</Params>';

		return $this->makeApiCall('DeleteUser', $xml);
	}

	public function appendProduct($mUsername, $mPassword, $sku, $expiration) {
		$xml = '<Params>';
		$xml .= '<Guid>' . $this->createGuid() . '</Guid>';
//		$xml .= '<MUser>' . $mUsername . '</MUser>';
//		$xml .= '<MPass>' . $mPassword . '</MPass>';
        $xml .= '<User>' . $mUsername . '</User>';
		$xml .= '<PortalId>' . $this->getPortalId() . '</PortalId>';
		$xml .= '<Products>';
		$xml .= '  <Product>';
		$xml .= '    <Code>' . $sku . '</Code>';
		$xml .= '    <ExpireDate>' . $expiration . '</ExpireDate>';
		$xml .= '  </Product>';
		$xml .= '</Products>';
		$xml .= '</Params>';

		return $this->makeApiCall('AppendProduct', $xml);
	}

	public function appendUserProduct($username, $sku, $expiration) {
		$xml = '<Params>';
		$xml .= '<Guid>' . $this->createGuid() . '</Guid>';
		$xml .= '<User>' . $username . '</User>';
		$xml .= '<PortalId>' . $this->getPortalId() . '</PortalId>';
		$xml .= '<Products>';
		$xml .= '  <Product>';
		$xml .= '    <Code>' . $sku . '</Code>';
		$xml .= '    <ExpireDate>' . $expiration . '</ExpireDate>';
		$xml .= '  </Product>';
		$xml .= '</Products>';
		$xml .= '</Params>';

		return $this->makeApiCall('AppendProduct', $xml);
	}
        
        public function createSid($username, $password)
        {
            $xml = '<Params>';
            $xml .= '<Guid>' . $this->createGuid() . '</Guid>'; 
            $xml .= '<User>' . $username . '</User>'; 
            $xml .= '<Pass>' . $password . '</Pass>'; 
            $xml .= '<PortalId>' . $this->getPortalId() . '</PortalId>';
            $xml .= '</Params>';
            
            $sid_result = $this->makeApiCall('CreateSid', $xml);
            
            if($sid_result['success'])
            {
                return (string)$sid_result['obj'];
            }
            return false;
                    
        }

//Xml Output: 
//On Success:
//<Sid>2009012206410023480</Sid> 
//On Error:
//Error> Error Message</Error>
    
    public function setAddToQueue( $add_to_queue )
    {
        $this->__add_to_queue = (bool) $add_to_queue;
    }
    
    
    
    
}
