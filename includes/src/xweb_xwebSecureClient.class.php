<?php
/*
Jan 25th, 2007 release
James Michael-Hill
james.michael-hill@utc.org
The United Telecom Council

xWebSecureClient Class - an extension of the php SoapClient class to provide for
seamless sliding token based authentication with Avectra's netFORUM.  xWeb 
documentation is provided with your system and at http://demo.avectra.com/xWebHelp/
Read more about netFORUM at www.avectra.com 

Licensed under the BSD license, a copy of which should have been included with this file.  If
you are not familiar with the BSD license please take a moment to read it - it is short!

Developed under php 5.1.6/apache 2.  PHP 5 is REQUIRED.

Where possible functionality is parallel to the regular SoapClient with the exception of the
constructor and the __soapCall() method - which will not take any soap headers.

The constructor requires xwebUserName and xwebUserPass to be set.  
These can be your regular user name and password, but that is NOT RECOMMENDED.  See the
Avectra documentation for best practices, but in a nutshell there should be a dedicated user, and the
password is set in the usr_pwd field of the fw_user table.  For more examples see the included
xwebExample.php file.

Updated since Nov 27th 2006 release:
I've added in response caching as an option.  Right now it only works with mysql, but there are stubs 
where you can add in your own database of choice.   The three functions you need to modify are 
cacheTestConnection, cacheStore, and cacheRetreive.

The caching method serializes the request and response and relies on mysql to match this.  If you're
issuing long requests you may incur a performance hit, and if you're feeling adventerous there may be
a performance benefit to hashing the request and storing it in the database instead of the serialized
parameter array.

To use caching refer to the example file provided with this for detailed examples, but here are the 
new functions:
setCaching($dbh, $dbtype, $expire_time)
$dbh  - Required.  This is the DB handle for whatever database you plan on using.  Right now, only mysql.
$dbtype - Required.  Right now the only option is 'mysql'
$expire_time - Think of this as the cutoff time when content is deemed old and expired.  
	By default it is set to four hours ago, so anything added or cached within the last four hours is 
	still fresh and will be retreived from the local db instead of via xweb.

cacheTestConnection() - returns true and turns on caching if the connection can be verified, false otherwise

cachingOn() - returns true if caching enabled

cachedLastResponse() - returns true if the last response came from the cache

disableCaching() - turns caching off.  Call cacheTestConnection or setCaching to turn the cache on again.

Here is the table I created.  This could probably benefit from some optimization.
create table nf_cached_responses(
	id int auto_increment primary key,
	user_name varchar(255),
	wsdl_loc varchar(255),
	request varchar(255),
	arguments text,
	response text,
	add_date datetime not null);

*/
class xwebSecureClient extends SoapClient {
   private $userName;
   private $userPass;
   private $authToken;
   private $xwebNamespace;
   private $overloadedWsdlFunctions= Array();
   private $wsdlNonOverloadFunctions = Array('Authenticate');
   
   //new for response caching
   private $cacheExpireTime = ""; //the default is 4 hours ago, and it is set in the constructor
   private $cachedFunctions = Array('GetDynamicQuery','GetFacadeObject','GetIndividualInformation','GetOrganizationInformation','GetQuery','GetUserAccountInformation');
   private $cacheWsdlLoc = "";
   private $cacheDbType = '';
   private $cacheDbh = false;
   private $cachingOn = false;
   private $cachedResponse = false;
   
   
   //for debugging purposes.  Not perfect, but if something is going awry it gives some insight
   public $log;
   
   function __construct($wsdl, $options = null) {
	if (isset($options['xwebUserName'], $options['xwebUserPass'])){
		$this->userName = $options['xwebUserName'];
		$this->userPass = $options['xwebUserPass'];
		parent::__construct($wsdl, $options);
		$this->setOverloadedWsdlFunctions();
		$this->cacheWsdlLoc = $wsdl;
	}
	else{
		//throw constructor error if we don't have the needed parameters
		throw new Exception("Invalid parameters in xwebSecureClient constructor:  xwebUserName and xwebUserPass are required");
	}
	$this->cacheExpireTime = strftime("%Y-%m-%d %H:%M",strtotime("-4 hours"));
	$this->log .= "Finished constructor\n";
   }
      
   function __call($method, $arguments){
	//the _call method is executed for every method call in this class.  We're using it to wrap every wsdl function call in the xweb authentication scheme
	
	//we're only overloading the functions that the wsdl defines here, so check to see if it is in our list
	if (in_array($method,$this->overloadedWsdlFunctions) && ! in_array($method, $this->wsdlNonOverloadFunctions)){
		$this->log .= "Overloading the call to $method method\n";
		//note that this is the overloaded soap call method that adds the auth tokens
		return $this->__soapCall1($method, $arguments);
	}
   }

   function __soapCall1($fname, $arguments=Array()){
	//overload the soap call function to only take a wsdl function name and an array of arguments, inject our auth token, and save the response auth token
	$this->log .= "Beginning __SoapCall\n";
	$responseHeaders = '';
	$this->cachedResponse = false;
	
	if ($this->cachingOn === true){
		$response = $this->cacheRetreive($fname,$arguments);
		if ($response){
			$this->log .= "Returning cached response to call\n";
			$this->cachedResponse = true;
			return $response ;
		}
	}
	try{
        // Ticket#2014041810000281: Temporarily use curl for all WEBIndividualUpdate requests to remove ind_dob and ind_pin_ext as well as other unwanted nodes
        if($fname == 'WEBIndividualUpdate') {
            $responseHeaders = array();
            $authHeaders = $this->getAuthHeaders();
            $response = $this->_webIndividualUpdate($arguments, $authHeaders, $responseHeaders);
        }
		else {
            $response = parent::__soapCall($fname, $arguments, null, $this->getAuthHeaders(), $responseHeaders);
        }
		$this->authToken = $responseHeaders['AuthorizationToken']->Token;
		if ($this->cachingOn === true && in_array($fname,$this->cachedFunctions)){
			$this->log .= "Cached response to soap call for future use\n";
			$this->cacheStore($fname, $arguments, $response);
		}
	} catch(SoapFault $exception){
		// if it is a bad token try re-authenticating - but only once
		if (stristr($exception->faultstring, "Invalid Token Value")){
			$this->log .= "Caught exception with invalid token value, re-authenticating and trying one more time\n";
			$this->authToken = '';
			try{
				$response = parent::__soapCall($fname, $arguments, null, $this->getAuthHeaders(), $responseHeaders);
				$this->authToken = $responseHeaders['AuthorizationToken']->Token;
				if ($this->cachingOn === true && in_array($fname,$this->cachedFunctions)){
					$this->log .= "Cached response to soap call for future use\n";
					$this->cacheStore($fname, $arguments, $response);
				}
			}
			catch(SoapFault $exception){
				$this->log .= "Caught exception in soap call to $fname again - bad authentication token\n";
				throw $exception;
			}
		}
		else{
			$this->log .= "Caught exception in soap call to $fname \n";
			//reset the auth token since a bad request invalidates any previous auth token.  This will save us a step if we try again.
			$this->authToken = '';
			throw $exception;
		}
	}
	
	return $response;
   }

    /**
     * Ticket#2014041810000281: Temporary override to remove ind_dob and ind_pin_ext from WEBIndividualUpdate posts
     * 
     * @see https://av.iccsafe.org/nficctest/xweb/secure/netforumxml.asmx?op=WEBIndividualUpdate
     * @see ICC_Avectra_Model_AvectraCommunication::updateAvectraName()
     *
     * Logs errors to avectra-communication-indup.log
     *
     * @param array $arguments array(0 => array('oFacadeObject' => [SimpleXMLElement]))
     * @param SoapHeader $authHeaders Has namespace, name, data (array), etc
     * @param $responseHeaders array passed by reference. Populated with 'AuthorizationToken' object
     * @throws SoapFault  In event of a failed request, a SoapFault is thrown. Not all SoapFaults are handled
     */
    protected function _webIndividualUpdate($arguments, $authHeaders, array &$responseHeaders)
    {

        // Facade object - this argument should always be set for this method call
        $oFacadeObject = $arguments[0]['oFacadeObject'];

        // Individual
        $individual = <<<IND
<ns1:Individual>
   <ns1:ind_cst_key>{$oFacadeObject->Individual->ind_cst_key}</ns1:ind_cst_key>
   <ns1:ind_first_name>{$oFacadeObject->Individual->ind_first_name}</ns1:ind_first_name>
   <ns1:ind_last_name>{$oFacadeObject->Individual->ind_last_name}</ns1:ind_last_name>
</ns1:Individual>
IND;

        // Email
        $email = '<ns1:Email/>';
        if(isset($oFacadeObject->Email)) {
            $email = <<<EMAIL
<ns1:Email>
    <ns1:eml_key>{$oFacadeObject->Email->eml_key}</ns1:eml_key>
    <ns1:eml_cst_key>{$oFacadeObject->Email->eml_cst_key}</ns1:eml_cst_key>
    <ns1:eml_address>{$oFacadeObject->Email->eml_address}</ns1:eml_address>
</ns1:Email>
EMAIL;

        }

        // SOAP Content
        $content = <<<SOAP
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.avectra.com/2005/">
    <SOAP-ENV:Header>
        <ns1:AuthorizationToken SOAP-ENV:mustUnderstand="1">
        <ns1:Token>{$authHeaders->data['Token']}</ns1:Token></ns1:AuthorizationToken>
    </SOAP-ENV:Header><SOAP-ENV:Body>
    <ns1:WEBIndividualUpdate>
      <ns1:oFacadeObject>
        <ns1:CurrentKey>{$oFacadeObject->CurrentKey}</ns1:CurrentKey>
        {$individual}
        {$email}
        <ns1:Customer/>
        <ns1:Address_Change_Log/>
        <ns1:TransferToCustomer/>
        <ns1:Organization_XRef/>
        <ns1:Organization/>
        <ns1:Website/>
        <ns1:Messaging/>
        <ns1:Business_Address/>
        <ns1:Business_Address_XRef/>
        <ns1:Business_Address_State/>
        <ns1:Business_Address_Country/>
        <ns1:Business_Phone/>
        <ns1:Business_Phone_Country/>
        <ns1:Business_Phone_XRef/>
        <ns1:Business_Fax/>
        <ns1:Business_Fax_Country/>
        <ns1:Business_Fax_XRef/>
        <ns1:Home_Address/>
        <ns1:Home_Address_State/>
        <ns1:Home_Address_Country/>
        <ns1:Home_Address_XRef/>
        <ns1:Home_Phone/>
        <ns1:Home_Phone_Country/>
        <ns1:Home_Phone_XRef/>
        <ns1:Home_Fax/>
        <ns1:Home_Fax_Country/>
        <ns1:Home_Fax_XRef/>
        <ns1:Individual_Custom_Demographics/>
        <ns1:Social_Links/>
        <ns1:source_code/>
      </ns1:oFacadeObject>
    </ns1:WEBIndividualUpdate>
 </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
SOAP;

        // Parse URL from wsdl
        $url = substr($this->cacheWsdlLoc, 0, strpos($this->cacheWsdlLoc, '?'));

// Example Headers from logged request
//POST /nficctest/xweb/secure/netforumxml.asmx HTTP/1.1
//Host: av.iccsafe.org
//Connection: Keep-Alive
//User-Agent: PHP-SOAP/5.3.10-1ubuntu3.10
//Content-Type: text/xml; charset=utf-8
//SOAPAction: "http://www.avectra.com/2005/WEBIndividualUpdate"
//Content-Length: 6916

        // Headers
        $headers = array(
            'POST ' . str_replace('https://av.iccsafe.org', '', $url) . ' HTTP/1.1',
            'Host: av.iccsafe.org',
            'Connection: Keep-Alive',
            'Content-Type: text/xml; charset=utf-8',
            "SOAPAction: \"{$this->xwebNamespace}WEBIndividualUpdate\"",
            "Content-Length: " . strlen($content),
            'Accept:',  // We don't need curl adding unnecessary Accept headers. This will remove the header.
            'Expect:'   // We don't need curl adding unnecessary Expect headers. This will remove the header.
        );


        // POST using curl rather than SoapClient
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $response = @curl_exec($ch);
        $curlInfo = curl_getinfo($ch); // Request headers at element request_header
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        curl_close($ch);

        // Curl Error
        if($response == false) {
            Mage::log("
            Last Request Headers:
            " . $curlInfo['request_header'] . "\n
            Last Request :
            " . $content . "\n
            Last Response Headers :
            UNAVAILABLE AT THIS TIME\n
            Last Response :
            " . $response . "\n\n", null, 'avectra-communication-indup.log'
            );
            throw new SoapFault('CURLERROR', "CURL Error ($curlErrNo): $curlError");
        }

        // Parse AuthorizationToken from response
        $pattern = '/\<Token\>(.*)\<\/Token\>/';
        if(preg_match($pattern, $response, $matches)) {
            $responseHeaders['AuthorizationToken'] = (object)array('Token' => $matches[1]);
         }

        // Parse result and return response if found
        $pattern = '/\<WEBIndividualUpdateResult\>(true|false|TRUE|FALSE)\<\/WEBIndividualUpdateResult\>/';
        if(preg_match($pattern, $response, $matches)) {
            return (object)array('WEBIndividualUpdateResult' => (bool)$matches[1]);
        }

        Mage::log("
            Last Request Headers:
            " . $curlInfo['request_header'] . "\n
            Last Request :
            " . $content . "\n
            Last Response Headers :
            UNAVAILABLE AT THIS TIME\n
            Last Response :
            " . $response . "\n\n", null, 'avectra-communication-indup.log'
        );
        throw new SoapFault("SoapFault", "Exception response from WEBIndividualUpdate");
    }

   function __doRequest1($request, $location, $action, $version) {
     //really, this is only overloaded for debugging purposes - I want to be able to see what the final soap call is for each step,
     //that way we can track the auth tokens.  Feel free to remove this as needed
     $this->log .= "Beginning __doRequest\n";
     $this->log .= "Params for __doRequest: \nRequest: $request\nLocation: $location\nAction: $action\nVersion: $version\n\n";
     return parent::__doRequest($request, $location, $action, $version);
   }


    public function __doRequest ($request, $location, $action, $version, $one_way = 0)
    {
//        if(strpos($request, 'WEBIndividualUpdate') !== false) {
//            Mage::log(__METHOD__ . ':BEFORE:' . $request, null, 'avectra_xweb_dorequest.log');
//            $request = str_replace(array('<ns1:ind_dob></ns1:ind_dob>', '<ns1:ind_pin_ext>0</ns1:ind_pin_ext>'), array('<ns1:ind_dob xsi:nil="true"/>', '<ns1:ind_pin_ext xsi:nil="true"/>'), $request);
//            Mage::log(__METHOD__ . ':AFTER:' . $request, null, 'avectra_xweb_dorequest.log');
//
//        }
        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }

  public  function clearLog(){
	$this->log = '';
   }
   
   //Turn on caching for requests.  It takes the db handle, the db type, and the expire time.  By default this is four hours ago, but can be overridden.
   //it should come as a regular unixtime seconds from 1970, since we'll later call stftime("format",$expire_time) on it.
   public function setCaching($dbh = '', $dbtype = '', $expire_time = ''){
	if ($dbh == '' || $dbtype == ''){
		$this->log .= "Cannot cache without a dbh and db type\n";
		return false;
	}
	else{
		$this->cacheDbh = $dbh;
		$this->cacheDbType = $dbtype;
		if ($expire_time != ''){
			$this->cacheExpireTime = strftime("%Y-%m-%d %H:%M", $expire_time);
		}
		//cacheTestConnection will flip the cachingOn flag for us
		return $this->cacheTestConnection();
	}
   }
   
   //turns the caching off
   public function disableCaching(){
	$this->cachingOn = false;
   }
   
   //returns true or false depending on the availability of the provided database connection, and will set the 
   //cachingOn flag accordingly
   public function cacheTestConnection(){
	$status = '';
	
	if ($this->cacheDbType != '' && $this->cacheDbh != false){
		if (strtolower($this->cacheDbType) == 'mysql'){
			$status = mysql_stat($this->cacheDbh);
			if ($status && $status != ''){
				$this->cachingOn = true;
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
	else{
		return false;
	}
   }
   
   public function cachingOn(){
	return $this->cachingOn;
   }
   
   //If the last response returned was cached, this will return true.
   public function cachedLastResponse(){
	return $this->cachedResponse;
   }
   
   private function getAuthHeaders(){
	//this function is used to get the proper headers for inclusion in our own __soapCall method
	
	// if we don't have a saved auth token get one
	if ( (! isset($this->authToken)) || trim($this->authToken == '')){
		$this->log .= "Fetching new authToken\n";
		//these are the params set in the constructor
		$authReqParams = Array('userName'=>$this->userName, 'password' => $this->userPass);
		$responseHeaders = '';
		try{
			//run the soap call to get it - with the headers.  Use the parent soap call in case we overload our soap method
			$response = parent::__SoapCall("Authenticate", array('parameters'=>$authReqParams), null,null, $responseHeaders);
			$this->authToken = $responseHeaders['AuthorizationToken']->Token;
			$this->xwebNamespace = $response->AuthenticateResult;
		} catch(SoapFault $exception) {
			throw $exception;
		}
	}
	
	//return the header we oh so want.
	return new SoapHeader($this->xwebNamespace, 'AuthorizationToken', Array('Token'=>$this->authToken), true);
   }
   
   private function setOverloadedWsdlFunctions(){
	//this method will grab a list of wsdl defined functions that we will be overloading using the magic __call() method
	$functions = parent::__getFunctions(); 
	foreach ($functions as $fname){
		//strip the actual function name out for our uses
		$start = strpos($fname,' ');
		$end = strpos($fname,'(');
		//append the name of the function to our internal list, which we check in every __call()
		$this->overloadedWsdlFunctions[] = trim(substr($fname, $start, ($end - $start)));
	}
   }
   
   
   //this will cache the request and for a given function call and arguments.  By default it uses mysql but can be expanded.
   private function cacheStore($fname='', $arguments='', $response=''){
	if ( $fname == '' || $arguments == '' || (! is_object($response) && $response == '')){
		$this->log .= "Could not store response, invalid parameters passed\n";
		return false;
	}
	if ( ! in_array($fname,$this->cachedFunctions)){
		$this->log .= "Could not store response, $fnanme is not on the list of cacheable functions\n";
		return false;
	}
	if (strtolower($this->cacheDbType) == 'mysql'){
		$res = mysql_query("INSERT INTO nf_cached_responses (user_name, wsdl_loc, request, arguments, response, add_date)
					    VALUES ('".mysql_real_escape_string($this->userName)."',
					    '".mysql_real_escape_string($this->cacheWsdlLoc)."',
					    '".mysql_real_escape_string($fname)."',
					    '".mysql_real_escape_string(serialize($arguments))."',
					    '".mysql_real_escape_string(serialize($response))."',
					    '".strftime("%Y-%m-%d %H:%M")."')", $this->cacheDbh);
		if ($res){
			$this->log .= "Cached call to $fname with mysql db, cache id number ".mysql_insert_id()."\n";
			return true;
		}
		else{
			$this->log .= "Error on mysql insert - ".mysql_error()."\n";
			return false;
		}
	}
	//to add other db types just add in an if/else here for what you want.  Be sure to return true or false on success, and log as you see fit
	//remember to add the types in to cacheRetreive() and cacheTestConnection() too
	else{
		$this->log .= "No cache db type set or matched in cacheStore function, nothing cached.\n";
		return false;
	}
   
   }
   
   private function cacheRetreive($fname='', $arguments=''){
	if ($fname == '' || $arguments == '' ){
		$this->log .= "Could not fetch response from cache, invalid parameters passed\n";
		return false;
	}
	if ( ! in_array($fname,$this->cachedFunctions)){
		$this->log .= "Could not fetch response, $fnanme is not on the list of cacheable functions\n";
		return false;
	}
	
	if (strtolower($this->cacheDbType) == 'mysql'){
		$res = mysql_query("SELECT response FROM nf_cached_responses
					    WHERE user_name = '".mysql_real_escape_string($this->userName)."' AND
					    wsdl_loc = '".mysql_real_escape_string($this->cacheWsdlLoc)."' AND
					    request = '".mysql_real_escape_string($fname)."' AND
					    arguments = '".mysql_real_escape_string(serialize($arguments))."' AND
					    add_date >= '".$this->cacheExpireTime."' 
					    ORDER BY add_date DESC LIMIT 1", $this->cacheDbh);
		if ($res && mysql_num_rows($res) > 0){
			$this->log .= "Found cached response to $fname, returning from mysql database\n";
			$response = mysql_fetch_row($res);
			return unserialize($response[0]); //since fetch row returns an array, we just want the plain ol' response
		}
		else{
			$this->log .= "No cached response found for $fname in mysql database\n";
			return false;
		}
	}
	//to add other db types just add in an if/else here for what you want.  Be sure to return true or false on success, and log as you see fit
	//remember to add the types in to cacheStore() and cacheTestConnection() too
	else{
		$this->log .= "No cache db type set or matched in cacheRetreive function, nothing returned.\n";
		return false;
	}
   }
   
}

