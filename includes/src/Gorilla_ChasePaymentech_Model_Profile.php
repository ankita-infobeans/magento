<?php
/**
 * Class for interacting with CIM profile management via SOAP
 *
 * For documentation:
 * @see http://download.chasepaymentech.com/docs/orbital/orbital_gateway_web_service_specification.pdf
 * @see https://wsvar.paymentech.net/PaymentechGateway/wsdl/PaymentechGateway.wsdl
 */
class Gorilla_ChasePaymentech_Model_Profile extends Mage_Core_Model_Abstract
{
    const VERSION = "2.6";      // CIM GATEWAY version
    const BIN = "000002";       // transaction routing definition
    const TERMINAL_ID = "001";

    const CARD_TYPE_NEW = 'NEWCARD';

    const AMERICAN_EXPRESS = 'AE';
    const AMERICAN_EXPRESS_VALUE = 'AX';

    // add
    const CUST_CONT_AUTO = "A";
    const CUST_CONT_PASS_VALUE = "S";
    const CUST_CONT_ORDER_ID = "O";
    const CUST_CONT_COMMENTS = "D";
    // end add

    // Customer Payment Profile Transactions
    const TRANS_PROFILE_CREATE = "ProfileAdd";
    const TRANS_PROFILE_GET = "ProfileFetch";
    const TRANS_PROFILE_UPDATE  = 'ProfileChange';
    const TRANS_PROFILE_DELETE = "ProfileDelete";

    // Customer Payment Profile Transactions Requests
    const TRANS_PROFILE_CREATE_REQUEST = 'profileAddRequest';
    const TRANS_PROFILE_GET_REQUEST = 'profileFetchRequest';
    const TRANS_PROFILE_UPDATE_REQUEST = 'profileChangeRequest';
    const TRANS_PROFILE_DELETE_REQUEST = 'profileDeleteRequest';

    // Payment Transaction Type Codes
    const TRANS_TYPE_AUTH_ONLY      = 'A';
    const TRANS_TYPE_AUTH_CAPTURE   = 'AC';
    const TRANS_TYPE_CAPTURE        = 'MFC';    // internal use only - no meaning/requirements/binding with Paymentech
    const TRANS_TYPE_REFUND         = 'R';
    const TRANS_TYPE_VOID           = 'V';      // internal use only

    // Payment Transaction Types
    const TRANS_CREATE_TRANS    = 'NewOrder';
    const TRANS_AUTH_ONLY       = 'NewOrder';
    const TRANS_AUTH_CAPTURE    = 'NewOrder';
    const TRANS_CAPTURE_ONLY    = 'MFC';
    const TRANS_REFUND          = 'Reversal';
    const TRANS_VOID            = 'Reversal';

    // Payment Transactions Requests
    const TRANS_CREATE_TRANS_REQUEST = 'newOrderRequest';
    const TRANS_CAPTURE_TRANS_REQUEST = 'mfcRequest';
    const TRANS_REFUND_TRANS_REQUEST = 'reversalRequest';

    // add
    const RESPONSE_PROC_STATUS_SUCCESS = "0";
    const RESPONSE_AUTH_APPROVE = "00";
    const RESPONSE_AUTH_DECLINED = "05";
    const RESPONSE_INVALID_CARD_NUMBER = "14";
    const RESPONSE_INVALID_EXP_DATE = "54";
    const RESPONSE_AUTH_APPROVE_AUTH_NEEDED = "08";
    const RESPONSE_AUTH_APPROVE_VIP = "11";
    const RESPONSE_AUTH_APPROVE_VALIDATED = "24";
    const RESPONSE_CVV_MATCH = "M";

    const RESPONSE_APPROVAL_STATUS_PAYMENT_ERROR = '0';
    const RESPONSE_APPROVAL_STATUS_SYSTEM_ERROR = '2';

    const SOAPFAULT_LOCKED_DOWN = "882";
    const SOAPFAULT_ERROR_VALIDATING = "801";
    // end add

    // Debug Settings
    const LOG_FILE              = 'chasepaymentech_payment_certification_01';
    const MESSAGES_NODE         = 'errors';

    // syste.xml
    const XML_PATH_TESTMODE     = 'payment/chasepaymentech/test';
    const XML_PATH_STRIPCENTS   = 'payment/chasepaymentech/strip_cents';

    /**
     * The following is not being used but should probably be considered
     */

    // Success Codes
    protected $_successCodes = array(
        'I00001',
        'I00003'
    );

    // Error Codes - Any of these prevent the profile from saving
    protected $_cvvResponses = array(
        'M' => 'CVV Match',
        'N' => 'CVV Mismatch',
        'P' => 'CVV Not Present',
        'S' => 'CVV Not Present',
        'I' => 'CVV Invalid'
    );

    // Error Codes - Any of these prevent the profile from saving
    protected $_errorCodes = array(
        'E00001', 'E00002', 'E00003', 'E00004', 'E00005', 'E00006', 'E00007',
        'E00008', 'E00009', 'E00010', 'E00011', 'E00013', 'E00014', 'E00015',
        'E00016', 'E00019', 'E00027', 'E00029', 'E00039', 'E00040', 'E00041',
        'E00042', 'E00043', 'E00044', 'E00051',
    );


    // Error Messages - Messages associated with above codes
    protected $_errorMessages = array(
        'E00001' => 'A system error has occurred. Please try again.',
        'E00002' => 'Unsupported Content Type',
        'E00003' => 'Invalid XML',
        'E00004' => 'Invalid XML',
        'E00005' => 'Invalid Transaction Key',
        'E00006' => 'Invalid API Key',
        'E00007' => 'Invalid gateway credentials',
        'E00008' => 'Invalid gateway credentials',
        'E00009' => 'Method cannot be executed in Test Mode',
        'E00010' => 'The user does not have permission to call the API',
        'E00011' => 'The user does not have permission to call the API method',
        'E00013' => 'One or more field values are not valid',
        'E00014' => 'Required value missing',
        'E00015' => 'Invalid length on required value',
        'E00016' => 'Field Type Not Valid',
        'E00019' => 'Tax ID or Driver\'s License Required',
        'E00027' => ' An approval was not returned for the transaction',
        'E00029' => 'Payment information is required when creating a payment profile',
        'E00039' => 'A duplicate record already exists',
        'E00040' => 'Customer record could not be found',
        'E00041' => 'All fields were empty',
        'E00042' => 'You have reached the maximum number of payment methods that may be created',
        'E00043' => 'You have reached the maximum number of shipping addresses that may be created',
        'E00044' => 'The gateway is not enabled for CIM',
        'E00051' => 'Payment profiles do not match'
    );

    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_init('chasepaymentech/profile');
    }

    /**
     * Fetch the Chase Paymentech CIM ID for a particular Magento customer
     *
     * @param $customer
     * @return Gorilla_ChasePaymentech_Model_Profile
     */
    public function loadInfoByCustomer($customer)
    {
        $this->getResource()->loadInfoByCustomer($customer);
        return $this;
    }

    /**
     * Returns list of customer saved profiles
     *
     * @param $customerId
     * @return array
     */
    public function getCustomerCards($customerId)
    {
        $collection = $this->getCollection()->addFilter('customer_id', $customerId)->load();
        $profiles = array();
        foreach ($collection as $profile) {
            $temp = $this->getCustomerPaymentProfile($profile->getCustomerRefNum());
            if ($temp) {
                // Construct the object
                $card = new Varien_Object();
                $card->setId($profile->getId())
                    ->setIsDefault($profile->getIsDefault())
                    ->setCcNumber($temp->ccAccountNum)
                    ->setCcExpiry(sprintf("%s/%d", substr($temp->ccExp, -2), substr($temp->ccExp, 0, 4)))
                    ->setCustomerRefNum($temp->customerRefNum)
                    ->setCustomerName($temp->customerName)
                    ->setAddress($temp->customerAddress1)
                    ->setCity($temp->customerCity)
                    ->setState($temp->customerState)
                    ->setZip($temp->customerZIP)
                    ->setCountry(isset($temp->customerCountryCode) ? $temp->customerCountryCode : "US");
                $profiles[] = $card;
            }
        }

        return $profiles;
    }

    /**
     * Get the URL to the Auth.net WSDL
     *
     * @return string $wsdl_url
     */
    public function getWsdlUrl()
    {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_gateway_wsdl');
        } else {
            return $this->_getConfig('gateway_wsdl');
        }
    }

    /**
     * Get the URL to the Auth.net Gateway API
     *
     * @return string $gateway_url
     */
    public function getGatewayUrl()
    {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_gateway_url');
        } else {
            return $this->_getConfig('gateway_url');
        }
    }

    /**
     * Get the API login
     *
     * @return string $api_login
     */
    public function getApiLogin()
    {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_login');
        } else {
            return $this->_getConfig('login');
        }
    }

    /**
     * Get the API password
     *
     * @return string $api_password
     */
    public function getApiPassword()
    {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_password');
        } else {
            return $this->_getConfig('password');
        }
    }

    /**
     * Get the API merchant_id
     *
     * @return string $api_merchant_id
     */
    public function getApiMerchantId()
    {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_merchant_id');
        } else {
            return $this->_getConfig('merchant_id');
        }
    }

    /**
     * Are we in test mode?
     *
     * @return bool
     */
    public function isTestMode()
    {
        return $this->_getConfig('test');
    }

    /**
     * Get config settings for the gateway
     *
     * @param string $key
     * @return mixed
     */
    protected function _getConfig($key)
    {
        return Mage::getSingleton('chasepaymentech/gateway')->getConfigData($key);
    }

    /**
     * Log debug settings
     *
     * @param mixed $debugData
     */
    public function debugData($debugData)
    {
        Mage::getModel('chasepaymentech/gateway')->debugData($debugData);
    }

    /**
     * Wrapper for retrieving SoapClient. Will return it if it already exists
     * or create it if it does not.
     *
     * @return SoapClient
     */
    public function getSoapClient()
    {
        if (!$this->getData('soap_client') instanceof SoapClient) {
            try {
                $soap_client = new SoapClient($this->getWsdlUrl(),
                    array(
                        'connection_timeout' => 2, // If Authorize.net isn't responding within two seconds, it's down
                        'exceptions' => true, // Throw exceptions if encountered - these should be caught by code
                        'trace' => ($this->_getConfig('debug')) ? 1 : 0, // If debug is enabled, use the trace
                        'compression'=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP // request a compressed response
                    )
                );
                $soap_client->__setLocation($this->getGatewayUrl());
                $this->setData('soap_client', $soap_client);
            } catch (SoapFault $sf) {
                //Log SOAP fault from connection
                if ($this->_getConfig('debug')) {
                    $this->debugData($sf);
                }
                return false;
            } catch (Exception $e) {
                //Log Exception from Connection
                if ($this->_getConfig('debug')) {
                    $this->debugData($e);
                }
                return false;
            }
        }

        return $this->getData('soap_client'); // Return the client to the user
    }

    /**
     * @return array
     */
    protected function _getAuthentication()
    {
        return array(
            'version' => Gorilla_ChasePaymentech_Model_Profile::VERSION,
            'orbitalConnectionUsername' => $this->getApiLogin(),
            'orbitalConnectionPassword' => $this->getApiPassword(),
            'merchantID' => $this->getApiMerchantId(),
            'bin' => Gorilla_ChasePaymentech_Model_Profile::BIN,
            'terminalID' => Gorilla_ChasePaymentech_Model_Profile::TERMINAL_ID,
            'industryType' => "EC"
        );
    }

    /**
     * Builds unique order id based on order increment id + last 4 digits of credit card
     *
     * @param $payment
     * @param $order
     * @return string
     */
    protected function _getUniqueOrderId($payment, $order)
    {
        $last4Digits = substr($payment->getCcNumber(), -4);
        if (!$last4Digits) {
            $cardObj = $this->getCustomerPaymentProfile($payment->getChasePaymentechCustomerRefNum());
            $last4Digits = substr($cardObj->ccAccountNum, -4);
        }

        return $order->getIncrementId() . $last4Digits;
    }


    /**
     * Create a payment profile for the given customer
     *
     * @param $object
     * @return SoapClient::Response
     */
    public function createCustomerPaymentProfile($object)
    {
        $data = array(
            'customerName' => substr($object->getCcName(), 0, 30),
            'avsName' => substr($object->getCcName(), 0, 30),
            'customerRefNum' => "",
            'customerAddress1' => substr($object->getCcBillingAddress1(), 0, 30),
            'customerAddress2' => substr($object->getCcBillingAddress2(), 0, 30),
            'customerCity' => $object->getCcBillingCity(),
            'customerState' => Mage::helper('chasepaymentech')->convertIdToCode($object->getCcBillingState()),
            'customerZip' => $object->getCcBillingZip(),
            'customerCountryCode' => $object->getCcCountryId(),
            'customerPhone' => "",
            'customerProfileOrderOverideInd' => "NO",
            'customerProfileFromOrderInd' => self::CUST_CONT_AUTO,
            'customerAccountType' => "CC",
            'ccAccountNum' => $object->getCcNumber(),
            'ccExp' => $object->getCcExpYear() . $object->getCcExpMonth(),
            'status' => "A"
        );

        $soap_env = array(self::TRANS_PROFILE_CREATE_REQUEST => array_merge($this->_getAuthentication(), $data));

        $response = $this->doCall(self::TRANS_PROFILE_CREATE, $soap_env)->return;

        if (!$response)
            return false;

        $hasErrors = $this->_checkErrors($response);
        if (!$hasErrors) {
            return $response;
        } else {
            return false;
        }
    }

    /**
     * Get the details of the customer payment profile
     *
     * @param string $customerRefNum
     * @return stdClass|boolean
     */
    public function getCustomerPaymentProfile($customerRefNum)
    {
        $data = array(
            'customerRefNum'  => $customerRefNum,
        );
        $soap_env = array(self::TRANS_PROFILE_GET_REQUEST => array_merge($this->_getAuthentication(), $data));

        $response = $this->doCall(self::TRANS_PROFILE_GET, $soap_env)->return;

        if (!$response)
            return false;

        $hasErrors = $this->_checkErrors($response);
        if (!$hasErrors) {
            return $response;
        } else {
            return false;
        }
    }

    /**
     * Update the Customer's payment profile
     *
     * @param $object
     * @return SoapClient::Response
     */
    public function updateCustomerPaymentProfile($object)
    {
        $data = array(
            'customerName' => $object->getCcName(),
            'avsName' => $object->getCcName(),
            'customerRefNum' => "",
            'customerAddress1' => $object->getCcBillingAddress1(),
            'customerAddress2' => $object->getCcBillingAddress2(),
            'customerCity' => $object->getCcBillingCity(),
            'customerState' => Mage::helper('chasepaymentech')->convertIdToCode($object->getCcBillingState()),
            'customerZip' => $object->getCcBillingZip(),
            'customerCountryCode' => $object->getCcCountryId(),
            'customerPhone' => "",
            'customerProfileOrderOverideInd' => "NO",
            'customerAccountType' => "CC",
            'ccAccountNum' => $object->getCcNumber(),
            'ccExp' => $object->getCcExpYear() . $object->getCcExpMonth()
        );

        $soap_env = array(self::TRANS_PROFILE_CREATE_REQUEST => array_merge($this->_getAuthentication(), $data));

        $response = $this->doCall(self::TRANS_PROFILE_CREATE, $soap_env)->return;

        if (!$response)
            return false;

        $hasErrors = $this->_checkErrors($response);
        if (!$hasErrors) {
            return $response;
        } else {
            return false;
        }
    }

    /**
     * Delete the customer's payment profile
     *
     * @param string $customerRefNum
     * @return SoapClient::Response
     */
    public function deleteCustomerPaymentProfile($customerRefNum)
    {
        $data = array(
            'customerRefNum'  => $customerRefNum,
        );
        $soap_env = array(self::TRANS_PROFILE_DELETE_REQUEST => array_merge($this->_getAuthentication(), $data));
        $response = $this->doCall(self::TRANS_PROFILE_DELETE, $soap_env);

        $response = $response->return;

        if (!isset($response->error)) {
            return $response;
        }

        return false;
    }

    /**
     * Perform transaction (auth, capture, etc.)
     *
     * @param Mage_Payment_Model_Info $payment
     * @param bool $addCustomerProfile
     * @return string $directResponse|bool
     */
    public function createOrderTransaction($payment, $addCustomerProfile = false)
    {
        /**
         * Set the order in its own object
         */
        $order = $payment->getOrder();
        $amount = $payment->getAmount() * 100;   // Must set 100.00 to 10000 per api

        $customerName = $order->getBillingAddress()->getFirstname() . " " . $order->getBillingAddress()->getLastname();
        $street = $order->getBillingAddress()->getStreet();
        $addy1 = $street[0];
        $addy2 = isset($street[1]) ? $street[1] : "";

        /**
         * Paymentech uses specific cent values to trigger specific error codes.
         * This can get annoying.
         * If we're in test mode and want to strip cents, let's get rid of them.
         */

        $testmode   = Mage::getStoreConfig(self::XML_PATH_TESTMODE);
        $stripcents = Mage::getStoreConfig(self::XML_PATH_STRIPCENTS);
        if ($testmode == 1 && $stripcents == 1) {
            $amount = floor($payment->getAmount()) * 100;
        }


        /**
         * Create the transaction request data
         */
        /**
         * For split requests where we auth the second request
         */
        /*if ($payment->getChasePaymentechTransType() == self::TRANS_TYPE_AUTH_ONLY && $payment->getSplitAuthTransId()) {
            $data = array(
                'transType' => $payment->getChasePaymentechTransType(),
                'ccAccountNum' => null,
                'orderID' => $this->_getUniqueOrderId($payment, $order),
                'amount' => $amount,
                'txRefNum' => $payment->getSplitAuthTransId(),
                'partialAuthInd' => 'Y',
                'comments' => "New Order Auth"
            );
        } else*/
        if ($payment->getChasePaymentechTransType() == self::TRANS_TYPE_REFUND) {
            $data = array(
                'transType' => $payment->getChasePaymentechTransType(),
                'ccAccountNum' => null,
                'orderID' => $this->_getUniqueOrderId($payment, $order),
                'amount' => $amount > 0 ? $amount : null,
                'txRefNum' => $payment->getTransId(),
                'comments' => "New Order Refund"
            );
        } else {
            $data = array(
                'transType' => $payment->getChasePaymentechTransType(),
                'amount' => $amount,
                'orderID' => $this->_getUniqueOrderId($payment, $order),
                'customerRefNum' => $payment->getChasePaymentechCustomerRefNum(),
                'customerName' => substr($customerName, 0, 30),
                'avsName' => substr($customerName, 0, 30),
                'avsAddress1' => substr($addy1, 0, 30),
                'avsAddress2' => substr($addy2, 0, 30),
                'avsCity' => $order->getBillingAddress()->getCity(),
                'avsState' => Mage::helper('chasepaymentech')->convertStateNameToCode($order->getBillingAddress()->getRegion()),
                'avsZip' => $order->getBillingAddress()->getPostcode(),
                'avsCountryCode' => $order->getBillingAddress()->getCountryId(),
                'avsPhone' => substr($order->getBillingAddress()->getTelephone(), 0, 14),
                'customerRefNum' => ""
            );

            // Saved account - send ref num instead of card data
            if ($payment->getChasePaymentechCustomerRefNum()) {
                $additionalData = array(
                    'customerRefNum' => $payment->getChasePaymentechCustomerRefNum()
                );
            } else {
                $additionalData = array(
                    'ccAccountNum' => $payment->getCcNumber(),
                    'ccExp' => sprintf('%04d%02d', $payment->getCcExpYear(), $payment->getCcExpMonth()),
                    'cardBrand' => $payment->getCcType() == self::AMERICAN_EXPRESS ? self::AMERICAN_EXPRESS_VALUE : $payment->getCcType(),
                    'ccCardVerifyNum' => $payment->getCcCid(),
                    'ccCardVerifyPresenceInd' => in_array($payment->getCcType(), array('DI', 'VI')) ? 1 : null,     // ccv has been entered
                    'addProfileFromOrder' => $addCustomerProfile ? "A" : "",
                    'profileOrderOverideInd' => $addCustomerProfile ? "NO" : "",
                );
            }

            // Merge data arrays
            $data = array_merge($data, $additionalData);

            /**
             * If this is a prior auth capture, void, or refund add the transaction id
             */
            if ($payment->getChasePaymentechTransType() == self::TRANS_TYPE_CAPTURE || $payment->getChasePaymentechTransType() == self::TRANS_TYPE_REFUND) {
                $data['txRefNum'] = $payment->getTransId();
            }

            /**
             * Send Minimum Auth Amount request, parse the response to check cvv, then send secondary, full, request.
             *
             * $0.00 Auth verification can only be used for MC and Visa so there is no $ being held - does not seem to be working
             * $0.01 Authorization is the least amount you can send in for all other CC.
             */
            $true = false;
            if ($true && !$payment->getChasePaymentechCustomerRefNum()) {
                $data['transType'] = self::TRANS_TYPE_AUTH_ONLY;
                if (in_array($payment->getCcType(), array('MC', 'VI'))) {
                    $data['amount'] = '000';
                } else {
                    $data['amount'] = '001';
                }

                $soap_env = array(self::TRANS_CREATE_TRANS_REQUEST => array_merge($this->_getAuthentication(), $data));

                if (!($response = $this->doCall(self::TRANS_CREATE_TRANS, $soap_env)))
                    return false;

                $response = $response->return;

                //Mage::log('minimum response:');
                //Mage::log($response);

                if ($this->_checkErrors()) {
                    // For CLI testing
                    //Mage::log($this->getErrorMessages());
                    return false;
                }

                if ($response->cvvRespCode != self::RESPONSE_CVV_MATCH) {
                    //$this->setResponseMessages(array($this->_cvvResponses[$response->cvvRespCode]));
                    if ($response->cvvRespCode) {
                        $this->setErrorMessages(array($this->_cvvResponses[$response->cvvRespCode]));
                    } else {
                        $this->setErrorMessages(array(Mage::helper('paygate')->__('Error with payment info')));
                    }
                    // For CLI testing
                    //Mage::log($this->getErrorMessages());
                    return false;
                }

                // Reset the amount and trans type for full amount request
                $data['amount'] = $amount;
                $data['transType'] = $payment->getChasePaymentechTransType();
                //$data['partialAuthInd'] = 'Y';
            }
        }

        $soap_env = array(self::TRANS_CREATE_TRANS_REQUEST => array_merge($this->_getAuthentication(), $data));

        if (!($response = $this->doCall(self::TRANS_CREATE_TRANS, $soap_env)))
            return false;

        $response = $response->return;

        //Mage::log("full response");
        //Mage::log($response);

        $hasErrors = $this->_checkErrors();

        if ($hasErrors) {
            // For CLI testing
            //Mage::log($this->getErrorMessages());
        }

        if ($response) {
            if (!$hasErrors) {
                return $response;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Perform transaction (capture)
     *
     * @param Mage_Payment_Model_Info $payment
     * @return string $directResponse|bool
     */
    public function createMarkForCaptureTransaction($payment)
    {
        /**
         * Set the order in its own object
         */
        $order = $payment->getOrder();
        $amount = $payment->getAmount() * 100;   // Must set 100.00 to 10000 per api

        /**
         * Create the transaction
         */
        $data = array(
            'amount' => $amount,
            'orderID' => $this->_getUniqueOrderId($payment, $order),
            'txRefNum' => $payment->getTransId()
        );

        $soap_env = array(self::TRANS_CAPTURE_TRANS_REQUEST => array_merge($this->_getAuthentication(), $data));

        if (!($response = $this->doCall(self::TRANS_CAPTURE_ONLY, $soap_env)))
            return false;

        $response = $response->return;

        //Mage::log("full response");
        //Mage::log($response);

        $hasErrors = $this->_checkErrors();
        if ($response) {
            if (!$hasErrors) {
                return $response;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Perform transaction for void/Reversal
     *
     * @param Mage_Payment_Model_Info $payment
     * @return string $directResponse|bool
     */
    public function createReversalTransaction($payment)
    {
        /**
         * Set the order in its own object
         */
        $order = $payment->getOrder();
        $amount = $payment->getAmount() * 100;   // Must set 100.00 to 10000 per api

        /**
         * Create the transaction
         */
        $data = array(
            'txRefNum' => $payment->getTransId(),
            //'txRefIdx' => $payment->getTransRefIdx() ? $payment->getTransRefIdx() : null,                      // needs to be set to the value returned in response for partial capture
            'txRefIdx' => null,                      // needs to be set to the value returned in response for partial capture
            'orderID' => $this->_getUniqueOrderId($payment, $order),
            'adjustedAmount' => $amount > 0 ? $amount : null,         // amount for a partial void
            'reversalRetryNumber' => null,
            //'onlineReversalInd' => $amount > 0 || $payment->getTransRefIdx() ? "N" : "Y"
            'onlineReversalInd' => "N"
        );

        $soap_env = array(self::TRANS_REFUND_TRANS_REQUEST => array_merge($this->_getAuthentication(), $data));

        if (!($response = $this->doCall(self::TRANS_REFUND, $soap_env)))
            return false;

        $response = $response->return;

        //Mage::log("full response");
        //Mage::log($response);

        /*
        if ($response->error != null) {
            foreach ($response->error as $error) {
                if (strpos($error, self::SOAPFAULT_LOCKED_DOWN)) {
                    $this->debugData($error);
                    $this->c($paymentInfo, $amount);
                } else {
                    //$this->addError($this->_response->procStatusMessage);
                    $this->addError($error);
                }
            }
        }
        */

        $hasErrors = $this->_checkErrors();
        if ($response) {
            if (!$hasErrors) {
                $response->return->respCode = '0';  // set for successful request (this is not set on a reversal...)
                return $response;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Check the response for Errors
     *
     * @return bool
     */
    protected function _checkErrors()
    {
        $errors = array();
        if (is_array($this->getResponseMessages())) {
            foreach ($this->getResponseMessages() as $message) {
                $errors[] = $message;
                /*if (in_array($message->code, $this->_errorCodes)) {
                     $errors[] = $this->_errorMessages[$message->code];
                }*/
            }
        }

        if (!empty($errors)) {
            $this->setErrorMessages($errors);
            return true;
        } else {
            return false;
        }
    }

    protected function _respDefinition(array $haystack)
    {
        $_definition = '';
        switch ($haystack['respCode'])
        {
            case 'M1': $_definition = Mage::helper('paygate')->__("You have entered an incorrect credit card billing address.  Please enter a valid billing address that appears on your credit card statement."); break;
            default: $_definition = Mage::helper('paygate')->__("Sorry, error approving payment. ").$haystack['procStatusMessage'];
        }

        return $_definition;
    }

    /**
     * We have to go through this because the response node is named differently
     * for every SOAP response but we just want the messages block. This will set
     * data in the object under the key response_messages. It will be an array
     * of any responses.
     *
     * @param array $haystack
     * @param string $needle
     */
    public function retrieveResponseMessages(array $haystack, $needle = self::MESSAGES_NODE)
    {
        // Typecast to (array) automatically converts stdClass -> array.
        $haystack = (array) $haystack;
        $messages = array();

        // If no soapfault error reported - check response codes for valid messages
        if (!isset($haystack['error'])) {
            if ($haystack['procStatus'] != self::RESPONSE_PROC_STATUS_SUCCESS) {
                $messages[$haystack['procStatus']] = $haystack['procStatusMessage'];
            } else if (isset($haystack['approvalStatus']) && $haystack['approvalStatus'] == self::RESPONSE_APPROVAL_STATUS_SYSTEM_ERROR) {
                $messages[$haystack['respCode']] = Mage::helper('paygate')->__("Sorry, there is a problem with the system. Please try again.");
            } else if (isset($haystack['approvalStatus']) && $haystack['approvalStatus'] == self::RESPONSE_APPROVAL_STATUS_PAYMENT_ERROR) {
                $messages[$haystack['respCode']] = $this->_respDefinition($haystack);
            }
        } else {
            $messages = $haystack['error'];
        }

        foreach ($messages as $key => $message) {
            $messages[$key] = $message;
        }

        $this->setResponseMessages($messages);
    }

    /**
     * Perform SOAP call
     *
     * @param string $transaction
     * @param array $data
     * @return boolean
     */
    public function doCall($transaction, $data)
    {
        try {
            $response = null;
            if ($this->getSoapClient() instanceof SoapClient) {
                //$response = $this->getSoapClient()->__call($transaction, $data);
                $response = $this->getSoapClient()->$transaction($data);
            } else {
                $response = false;
            }
        } catch (SoapFault $sf) {
            if (!preg_match('/Fetch/', $transaction)) {
                //$this->debugData($sf);      // causing a timeout logging to large of an object
                $this->debugData(array(
                    '_request' => $transaction,
                    '_requestData' => $data,
                    '_code' => $sf->getCode(),
                    '_message' => $sf->getMessage()
                ));
            }
            //$response = false;
            $response->return->error[$sf->getCode()] = $sf->getMessage();
        } catch (Exception $e) {
            $this->debugData($e);
            //$response = false;
            $response->return->error[$e->getCode()] = $e->getMessage();
        }

        if (!preg_match('/Fetch/', $transaction)) {
            if ($this->_getConfig('debug') && $this->getSoapClient() instanceof SoapClient) {
                $this->logSoapTransaction();
            }
        }

        // set response messages
        $this->retrieveResponseMessages((array) $response->return);
        return $response;
    }

    /**
     * Logs the full soap transaction request/response/etc.
     */
    public function logSoapTransaction()
    {
        $debug_message = '';
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->getSoapClient()->__getLastRequest());

        $debug_message .= "Request:\n\n";
        $debug_message .= $dom->saveXML();

        $this->debugData($debug_message);

        $debug_message = '';
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->getSoapClient()->__getLastResponse());

        $debug_message .= "Response:\n\n";
        $debug_message .= $dom->saveXML();

        $this->debugData($debug_message);
    }
}
