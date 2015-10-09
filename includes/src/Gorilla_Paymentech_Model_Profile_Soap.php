<?php

/*
 * To change this template, choose Tools | Templates and open the template in
 * the editor.
 */

class Gorilla_Paymentech_Model_Profile_Soap extends Mage_Core_Model_Abstract {
	//
	const CUST_CONT_AUTO = "A";
	const CUST_CONT_PASS_VALUE = "S";
	const CUST_CONT_ORDER_ID = "O";
	const CUST_CONT_COMMENTS = "D";

	// Customer Account Transactions
	const TRANS_GET_PROFILE = "ProfileFetch";
	const TRANS_NEW_PROFILE = "ProfileAdd";
	const TRANS_DELETE_PROFILE = "ProfileDelete";

	const TRANS_REVERSAL = "Reversal";

	const TRANS_NEW_ORDER = "NewOrder";

	const SUCCESS = "0";

	const AUTH_DECLINED = "05";
	const INVALID_CARD_NUMBER = "14";
	const INVALID_EXP_DATE = "54";
	const AUTH_APPROVE = "00";
	const AUTH_APPROVE_AUTH_NEEDED = "08";
	const AUTH_APPROVE_VIP = "11";
	const AUTH_APPROVE_Validated = "24";

	const SOAPFAULT_LOCKED_DOWN = "882";
	const SOAPFAULT_ERROR_VALIDATING = "801";

	public $_response;
	private $_soap;
	private $_error;
	private $_customer;
	private $_address;
	private $_payment;
	public $txId;

	function __construct() {
		if (! Mage::registry ( 'soapclient' ) instanceof Gorilla_Paymentech_Model_Profile_Soap) {
			try {

				$this->_soap = new SoapClient ( $this->getWsdlUrl (), array ('connection_timeout' => 2, 				// If
				// Authorize.net
				// isn't
				// responding
				// within
				// two
				// seconds,
				// it's
				// down
				'exceptions' => true, 				// Throw exceptions if encountered - these
				// should be caught by code
				'trace' => (Mage::helper ( 'paymentech' )->isDebug ()) ? 1 : 0, 				// If
				// debug
				// is
				// enabled,
				// use
				// the
				// trace
				'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP ) )				// request
				// a
				// compressed
				// response
				;

				Mage::register ( 'soapclient', $this );
			} catch ( SoapFault $sf ) {
				// Log SOAP fault from connection
				Mage::helper ( 'paymentech' )->Log ( $sf->__toString (), Zend_Log::CRIT );
				return false;
			} catch ( Exception $e ) {
				// Log Exception from Connection

				Mage::helper ( 'paymentech' )->Log ( $e->__toString (), Zend_Log::CRIT );
				return false;
			}
		}

		return Mage::registry ( 'soapclient' ); // Return the client to the user
	}

	public function fetchProfile($id) {

		$req = new Gorilla_Paymentech_Model_Source_ProfileFetchRequestElement ();
		$req->customerRefNum = $id;
		$soap_env = new Gorilla_Paymentech_Model_Source_ProfileFetch ();
		$soap_env->profileFetchRequest = $req;
		$response = $this->doCall ( self::TRANS_GET_PROFILE, $soap_env );
		return $response;
	}

	public function processPhone($phone)
	{
		$phone = preg_replace('/\D/', '', $phone);
		return substr($phone, 0,14);
		return $phone;
	}
	public function createOrder($paymentInfo, $type, $amount, $save = false, $customerId = null) {
		$req = new Gorilla_Paymentech_Model_Source_NewOrderRequestElement ();

		Mage::helper ( 'paymentech' )->Log ( print_r ( $paymentInfo->debug (), true ) );
		$last4 = "";
		if (is_object ( $paymentInfo ) && $customerId == null) {


			$req->ccAccountNum = $paymentInfo ['cc_number'];
			$last4 = substr($req->ccAccountNum , -4);
			$month = ( int ) $paymentInfo ['cc_exp_month'];
			if ($paymentInfo ['cc_exp_month'] < 10) {
				$month = "0" . $month;
			}
			$req->ccExp = $paymentInfo ['cc_exp_year'] . $month;


			$req->ccCardVerifyNum = $paymentInfo ['cc_cid'];

			$req->ccCardVerifyPresenceInd = "";
			$cctype =  $paymentInfo ['cc_type'];
			if($cctype == "Visa" || $cctype == "DI")
			if($req->ccCardVerifyNum)
			$req->ccCardVerifyPresenceInd = 1; // ccv has been entered






			$req->avsZip = $this->getBillingAddress ()->getPostcode ();

			$st = $this->getBillingAddress ()->getStreet ();

			$req->avsAddress1 = $st [0]; // / address and street

			if (count ( $st ) > 1) {
				$req->avsAddress2 = $st [1]; // apartment or unit number
			}

			$req->avsCity = $this->getBillingAddress ()->getCity ();





			if(is_numeric($req->avsZip))
			{
				$req->avsCountryCode = "US";
			}else{
				$req->avsCountryCode = "CA";
			}




			$req->avsName = $this->getBillingAddress ()->getFirstname () . " " . $this->getBillingAddress ()->getLastname ();
			$req->customerName = $this->getBillingAddress ()->getFirstname () . " " . $this->getBillingAddress ()->getLastname ();

			$req->avsPhone = $this->processPhone($this->getBillingAddress ()->getTelephone ());
			$req->avsState = Mage::helper ( 'paymentech' )->state_to_twoletter ( $this->getBillingAddress ()->getRegion () );

			if ($save) {
				$req->addProfileFromOrder = "A";
				$req->profileOrderOverideInd = "NO";
			}

			$req->customerRefNum = "";
		} else {

			if ($customerId) {
				$req->customerRefNum = $customerId;


				//get last 4 from paymenttech

				$cardid = $order->getPayment()->getAdditionalInformation('paymentech_card');
				$profile = Mage::getModel('paymentech/profile_soap');
				$data = $profile->fetchProfile($customerId);
				$ccnum = $data->return->ccAccountNum;
				$last4 = substr($ccnum, -4);


			} else {

				$this->addError ( "Unknown error" );
				return $this;
			}
		}

		if(!Mage::helper ( 'paymentech' )->useCents())
		{
			$amount = floor($amount);
		}

		$req->amount = $amount * 100; // need to figure out why paymentech does
		// not like decimals
		$req->comments = "";

		$order = Mage::getModel ( 'sales/order' );
		$order->load ( Mage::getSingleton ( 'sales/order' )->getLastOrderId () );
		$lastOrderId = $order->getIncrementId ();
		$oid = Mage::getSingleton ( 'sales/order' )->getLastOrderId ();
		$realOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		//$order = Mage::getModel('sales/order')->loadByIncrementId();
		//$order->getGrandTotal();




		$id = Mage::getModel('checkout/session')->getData('quote_id_1');

		$quote = Mage::getModel('sales/quote')->load($id);




		Mage::helper ( 'paymentech' )->Log ( "--------------need to find order id-----------------" );
		//Mage::helper ( 'paymentech' )->Log (
		//		 Mage::getSingleton('checkout/type_multishipping')->getCheckoutSession()->debug()
		//		);
		Mage::helper ( 'paymentech' )->Log ($quote);
		// $oid ." - ".$lastOrderId. " - ".$realOrderId. " - ".Mage::getSingleton('checkout/session')->getQuoteId() );
		Mage::helper ( 'paymentech' )->Log ( "-----------------need to find order id END----------------------" );

		$orderid = Mage::getSingleton('checkout/type_multishipping')->getCheckoutSession()->getData('quote_id_1');



			




		Mage::helper ( 'paymentech' )->Log ( $orderid );
		if (isset ( $paymentInfo ['parent_id'] )) {
			$req->orderID = $paymentInfo ['parent_id'];
		} else {
			$req->orderID = $orderid;
		}

		$req->orderID = $req->orderID . $last4;


		switch ($type) {
			case "capture" :
				$req->transType = "AC";
				break;
			case "auth" :
				$req->transType = "A";
				break;
			case "refund" :
				$req->transType = "R";
				$req->ccCardVerifyPresenceInd = "";
				break;
		}

		$soap_env = new Gorilla_Paymentech_Model_Source_NewOrder ();
		$soap_env->newOrderRequest = $req;

		Mage::helper ( 'paymentech' )->Log ( "--------------Request -----------------" );
		Mage::helper ( 'paymentech' )->Log ( print_r ( $soap_env, true ) );
		Mage::helper ( 'paymentech' )->Log ( "---------------------------------------" );

		$this->_response = new Gorilla_Paymentech_Model_Source_NewOrderResponseElement ( $this->doCall ( self::TRANS_NEW_ORDER, $soap_env )->return );

		Mage::helper ( 'paymentech' )->Log ( "--------------Response -----------------" );
		Mage::helper ( 'paymentech' )->Log ( print_r ( $this->_response, true ) );
		Mage::helper ( 'paymentech' )->Log ( "-----------------------------------------" );

		// exit;
		$data = ( array ) $this->_response;

		Mage::helper ( 'paymentech' )->Log ( "The approvalStatus is " . $this->_response->approvalStatus );

		if ($this->_response->approvalStatus == "0" || $this->_response->approvalStatus == "") {
			Mage::helper ( 'paymentech' )->Log ( "I got 0" );
			$this->addError ( "Sorry, Error with payment. " . $this->_response->procStatusMessage, $this->_response->respCode );
			return $this;
		}

		if ($this->_response->approvalStatus == "2") {
			Mage::helper ( 'paymentech' )->Log ( "I got 2" );
			$this->addError ( "Sorry, problem with the system. Please try again.", $this->_response->respCode );
			return $this;
		}
		if ($this->_response->customerRefNum != "") {

			$data ['customer_id'] = $this->getCurrentCustomer ()->getId ();
			$data ['customer_ref_num'] = $this->_response->customerRefNum;

			$model = Mage::getModel ( "paymentech/profile" );
			$model->setData ( $data );
			$model->save ();
		}

		$quote = Mage::getModel ( 'checkout/cart' )->getQuote ();
		$payment = $quote->getPayment ();
		$payment->setAdditionalInformation ( 'paymentech_card', $data );

		$quote->setCustomerRefNum ( $this->_response->customerRefNum );
		$quote->setTxRefNum ( $this->_response->txRefNum );
		$quote->setAuthorizationCode ( $this->_response->authorizationCode );

		$quote->save ();

		if ($this->_response->error != null) {
			foreach ( $this->_response->error as $error ) {

				if (strpos ( $error, self::SOAPFAULT_ERROR_VALIDATING )) {
					$this->addError ( $this->_response->error );
				} else {
					$this->addError ( $this->_response->error );
				}
			}
		}
		return $this;
	}

	public function Refund($paymentInfo, $amount = null) {

		$txId = $paymentInfo->getRefundTransactionId ();

		$data = new Gorilla_Paymentech_Model_Source_ReversalElement ();

		$data->txRefNum = $txId;
		$data->reversalRetryNumber = null;

		if(!Mage::helper ( 'paymentech' )->useCents())
		{
			$amount = floor($amount);
		}




		$data->adjustedAmount = $amount * 100; // need to figure out why
		// paymentech does not like
		// decimals
		$data->onlineReversalInd = "Y";

		$soap_env = new Gorilla_Paymentech_Model_Source_Reversal ();
		$soap_env->reversalRequest = $data;

		Mage::helper ( 'paymentech' )->Log ( "------------Request ---------------" );
		Mage::helper ( 'paymentech' )->Log ( print_r ( $soap_env, true ) );
		Mage::helper ( 'paymentech' )->Log ( "----------------------------------" );

		$this->_response = new Gorilla_Paymentech_Model_Source_ReversalResponseElement ( $this->doCall ( self::TRANS_REVERSAL, $soap_env )->return );

		Mage::helper ( 'paymentech' )->Log ( "--------------Response -----------------" );
		Mage::helper ( 'paymentech' )->Log ( print_r ( $this->_response, true ) );
		Mage::helper ( 'paymentech' )->Log ( "-----------------------------------------" );

		if ($this->_response->error != null) {
			foreach ( $this->_response->error as $error ) {

				if (strpos ( $error, self::SOAPFAULT_LOCKED_DOWN )) {

					Mage::helper ( 'paymentech' )->Log ( print_r ( $error, true ), Zend_Log::ERR );

					$this->refundOrder ( $paymentInfo, $amount );
				} else {
					$this->addError ( $this->_response->procStatusMessage );
				}
			}
		}

		// exit;
		// $data = (array) $this->_response;

		return $this;
	}

	private function refundOrder($paymentInfo, $amount = null) {
		Mage::helper ( 'paymentech' )->Log ( "REFUND : " . print_r ( $paymentInfo->debug (), true ) );

		$txRefNum = $paymentInfo->getRefundTransactionId ();
		$req = new Gorilla_Paymentech_Model_Source_NewOrderRequestElement ();




		if(!Mage::helper ( 'paymentech' )->useCents())
		{
			$amount = floor($amount);
		}

		$req->amount = $amount * 100; // need to figure out why paymentech does
		// not like decimals
		$req->comments = "Refund";
		$req->orderID = $paymentInfo->getCreditmemo ()->getOrderId ();
		$req->txRefNum = $txRefNum;
		$req->transType = "R";

		$soap_env = new Gorilla_Paymentech_Model_Source_NewOrder ();
		$soap_env->newOrderRequest = $req;

		$this->_response = new Gorilla_Paymentech_Model_Source_NewOrderResponseElement ( $this->doCall ( self::TRANS_NEW_ORDER, $soap_env )->return );

		Mage::helper ( 'paymentech' )->Log ( "-----------refundOrder---Response -----------------" );
		Mage::helper ( 'paymentech' )->Log ( print_r ( $this->_response, true ) );
		Mage::helper ( 'paymentech' )->Log ( "-----------------------------------------" );

		if ($this->_response->approvalStatus != 1) {
			$this->addError ( $this->_response->procStatusMessage );
		}

		return $this;
	}

	public function deleteProfile($id) {
		// print_r($id);
		$req = new Gorilla_Paymentech_Model_Source_ProfileDeleteRequestElement ();
		$req->customerRefNum = $id;

		$soap_env = new Gorilla_Paymentech_Model_Source_ProfileDelete ();
		$soap_env->profileDeleteRequest = $req;
		$response = new Gorilla_Paymentech_Model_Source_ProfileResponseElement ( $this->doCall ( self::TRANS_DELETE_PROFILE, $soap_env )->return );
		return $response;
	}

	public function doCall($transaction, $data) {
		//print_r($data);
		//exit;
		try {

			$client = $this->getSoapClient ();

			$response = $client->$transaction ( $data );
		} Catch ( SoapFault $sf ) {
			$response->return->error [] = $sf->getMessage ();
		} Catch ( Exception $e ) {
			$response->return->error [] = $e->getMessage ();
		}
		return $response;
	}

	/*
	 * protected function _addTransaction(Mage_Sales_Model_Order_Payment
	 * $payment, $transactionId, $transactionType, array $transactionDetails =
	 * array(), array $transactionAdditionalInfo = array(), $message = false ) {
	 * $payment->setTransactionId($transactionId);
	 * $payment->setLastTransId($transactionId);
	 * $payment->resetTransactionAdditionalInfo(); foreach ($transactionDetails
	 * as $key => $value) { $payment->setData($key, $value); } foreach
	 * ($transactionAdditionalInfo as $key => $value) {
	 * $payment->setTransactionAdditionalInfo($key, $value); } $transaction =
	 * $payment->addTransaction($transactionType, null, false, $message);
	 * //foreach ($transactionDetails as $key => $value) { //
	 * $payment->unsetData($key); //} //$payment->unsLastTransId();
	 * $transaction->setMessage($message); return $transaction; }
	 */

	public function createProfile($data) {
		$this->error = "";
		$req = new Gorilla_Paymentech_Model_Source_ProfileAddRequestElement ();

		Mage::helper ( 'paymentech' )->Log ( print_r ( $data, true ) );

		// get billing address name

		$cart = Mage::getModel ( 'checkout/cart' )->getQuote ();

		$address = $cart->getBillingAddress ();

		$req->customerName = $data ['cc_name'];
		$req->avsName = $data ['cc_name'];
		$req->customerRefNum = "";
		$req->customerAddress1 = $data ['cc_billing_address1'];
		$req->customerAddress2 = $data ['cc_billing_address1'];
		$req->customerCity = $data ['cc_billing_city'];
		$req->customerState = Mage::helper ( 'paymentech' )->id_to_twoletter ( $data ['cc_billing_state_id'] );
		$req->customerZip = $data ['cc_billing_zip'];
		// $req->customerEmail = Mage::helper('customer')->getData("email");
		$req->customerPhone = "";
		$req->customerCountryCode = $data ['cc_country_id'];
		$req->customerProfileOrderOverideInd = "NO";
		$req->customerProfileFromOrderInd = self::CUST_CONT_AUTO;
		$req->orderDefaultDescription = "";
		$req->customerAccountType = "CC";
		$req->ccAccountNum = $data ['cc_number'];
		$req->ccExp = $data ['cc_exp_year'] . $data ['cc_exp_month'];
		$req->status = "A";

		Mage::helper ( 'paymentech' )->Log ( print_r ( $req, true ) );

		$soap_env = new Gorilla_Paymentech_Model_Source_ProfileAdd ();
		$soap_env->profileAddRequest = $req;
		$response = new Gorilla_Paymentech_Model_Source_ProfileResponseElement ( $this->doCall ( self::TRANS_NEW_PROFILE, $soap_env )->return );
		// echo "<pre>";
		// echo print_r($response,true);
		// echo "</pre>";
		if ($response->procStatus == self::SUCCESS) {
			return $response->customerRefNum;
		}
		return false;
	}

	public function getSoapClient() {
		if (! Mage::registry ( 'soap_client' )) {

			try {
				$soap_client = new SoapClient ( $this->getWsdlUrl (), array ('connection_timeout' => 2, 				// If
				// Authorize.net
				// isn't
				// responding
				// within
				// two
				// seconds,
				// it's
				// down
				'exceptions' => true, 				// Throw exceptions if encountered - these
				// should be caught by code
				'trace' => (Mage::helper ( 'paymentech' )->isDebug ()) ? 1 : 0, 				// If
				// debug
				// is
				// enabled,
				// use
				// the
				// trace
				'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP ) )				// request
				// a
				// compressed
				// response
				;

				Mage::register ( 'soap_client', $soap_client );
			} catch ( SoapFault $sf ) {
				// Log SOAP fault from connection
				// return $sf;

				Mage::helper ( 'paymentech' )->Log ( "Soap Fault " . $sf->__toString (), Zend_Log::ERR );
				return false;
			} catch ( Exception $e ) {
				Mage::helper ( 'paymentech' )->Log ( "Execption " . $e->__toString (), Zend_LoG::ERR );
				// Log Exception from Connection

				return false;
			}
		}

		return Mage::registry ( 'soap_client' );
	}

	private function getWsdlUrl() {
		return Mage::helper ( 'paymentech' )->getWsdlUrl ();
	}

	private function getCurrentCustomer() {
		$quote = Mage::getModel ( 'checkout/cart' )->getQuote ();
		return $quote->getCustomer ();
	}

	private function getBillingAddress() {
		$cart = Mage::getModel ( 'checkout/cart' )->getQuote ();

		return $cart->getBillingAddress ();

		// return
		// Mage::getModel('customer/address')->load($this->getCurrentCustomer()->getDefaultBilling());
	}

	public function getErrors() {
		Mage::helper ( 'paymentech' )->Log ( "getting errors" . print_r ( $this->_error, true ) );
		return $this->_error;
	}

	private function addError($error) {
		if (is_array ( $this->_error )) {
			return $this->_error [] = $error;
		}
		return $this->_error = array ($error );
	}

	/**
	 * It sets card`s data into additional information of payment model
	 *
	 * @param $response Gorilla_AuthorizenetCim_Model_Gateway_Result
	 * @param $payment Mage_Sales_Model_Order_Payment
	 * @return Varien_Object
	 */
	protected function _registerCard(Varien_Object $response, Mage_Sales_Model_Order_Payment $payment) {
		$cardsStorage = $this->getCardsStorage ( $payment );
		$card = $cardsStorage->registerCard ();
		$card->setRequestedAmount ( $response->getRequestedAmount () )->setBalanceOnCard ( $response->getBalanceOnCard () )->setLastTransId ( $response->getTransactionId () )->setProcessedAmount ( $response->getAmount () )->setCcType ( $response->getCardType () )->setCcOwner ( $payment->getCcOwner () )->setCcLast4 ( $response->getCcLast4 () )->setCcExpMonth ( $payment->getCcExpMonth () )->setCcExpYear ( $payment->getCcExpYear () )->setCcSsIssue ( $payment->getCcSsIssue () )->setCcSsStartMonth ( $payment->getCcSsStartMonth () )->setCcSsStartYear ( $payment->getCcSsStartYear () );

		$cardsStorage->updateCard ( $card );
		return $card;
	}

	/**
	 * Init cards storage model
	 *
	 * @param $payment Mage_Payment_Model_Info
	 */
	protected function _initCardsStorage($payment) {
		$this->_cardsStorage = Mage::getModel ( 'authorizenetcim/gateway_cards' )->setPayment ( $payment );
	}

	/**
	 * Return cards storage model
	 *
	 * @param $payment Mage_Payment_Model_Info
	 * @return Gorilla_AuthorizenetCim_Model_Gateway_Cards
	 */
	public function getCardsStorage($payment = null) {
		if (is_null ( $payment )) {
			$payment = $this->getInfoInstance ();
		}
		if (is_null ( $this->_cardsStorage )) {
			$this->_initCardsStorage ( $payment );
		}
		return $this->_cardsStorage;
	}

	public function getResponse() {
		return $this->_response;
	}

}

