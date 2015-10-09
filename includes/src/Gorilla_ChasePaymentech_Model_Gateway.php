<?php

/**
 *
 */
class Gorilla_ChasePaymentech_Model_Gateway extends Mage_Payment_Model_Method_Cc
{
    const REQUEST_METHOD_CC     = 'CC';

    // Validation Modes
    const VALIDATION_MODE_NONE = 'none';        // Just validates field content
    const VALIDATION_MODE_TEST = 'testMode';    // A mock $1.00 transaction performed
    const VALIDATION_MODE_LIVE = 'liveMode';    // Generates a $0 transaction to the customer's card

    // Delimiter Character for DirectResponse
    const RESPONSE_DELIM_CHAR = '(~)';

    const REQUEST_TYPE_AUTH_CAPTURE = 'AC';
    const REQUEST_TYPE_AUTH_ONLY    = 'A';
    const REQUEST_TYPE_CAPTURE_ONLY = 'MFC';
    const REQUEST_TYPE_REFUND       = 'R';

    // procStatus
    const RESPONSE_CODE_NEW_ORDER_SUCCESS = '0';

    // approvalStatus
    const RESPONSE_APPROVAL_STATUS = '1';

    // respCode
    const RESPONSE_CODE_APPROVED = '00';
    const RESPONSE_CODE_DECLINED = '05';        // @TODO confirm

    const METHOD_CODE = 'chasepaymentech';

    // Scenario types for customers
    const SCENARIO_CUSTOMER_STORED_COMPLETE = 'CustomerSavedCard';
    const SCENARIO_NO_SAVE = 'SingleUseCimProfile';


    /**
     * unique internal payment method identifier
     */
    protected $_code = self::METHOD_CODE;

    /**
     * this should probably be true if you're using this
     * method to take payments
     */
    protected $_formBlockType = 'chasepaymentech/form_cc';

    /**
     * The Block type for the Payment Info
     *
     * @var string
     */
    protected $_infoBlockType = 'chasepaymentech/info_cc';

    /**
     * @var string
     */
    protected $_realTransactionIdKey = 'real_transaction_id';

    protected $_splitTransactionRefIdx = 'trans_ref_idx';

    /**
     * Key for storing locking gateway actions flag in additional information of payment model
     * @var string
     */
    protected $_isGatewayActionsLockedKey = 'is_gateway_actions_locked';

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway               = true;

    /**
     * Can authorize online?
     */
    protected $_canAuthorize            = true;

    /**
     * Can capture funds online?
     */
    protected $_canCapture              = true;

    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial       = true;

    /**
     * Can refund online?
     */
    protected $_canRefund               = true;

    /**
     * Can void transactions online?
     */
    protected $_canVoid                 = true;

    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal          = true;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout          = true;

    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping  = true;

    /**
     * Is this payment method able to partially refund an invoice?
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;

    /**
     * What currencies may be used with this gateway?
     */
    protected $_allowCurrencyCode = array('USD');

    /**
     * Returns the Module internal payment code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Return array of currency codes supplied by Payment Gateway
     *
     * @return array
     */
    public function getAcceptedCurrencyCodes()
    {
        if (!$this->hasData('_accepted_currency')) {
            $acceptedCurrencyCodes = $this->_allowCurrencyCode;
            $acceptedCurrencyCodes[] = $this->getConfigData('currency');
            $this->setData('_accepted_currency', $acceptedCurrencyCodes);
        }
        return $this->_getData('_accepted_currency');
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->getAcceptedCurrencyCodes())) {
            return false;
        }
        return true;
    }

    /**
     * Validate the provided payment information - happens after customer clicks
     * next from payment section of checkout.
     *
     * @return Gorilla_ChasePaymentech_Model_Gateway
     */
    public function validate()
    {
        $paymentInfo = $this->getInfoInstance();

        // if the payment_card 'id' is designated as a new card
        if ($paymentInfo->getAdditionalInformation('chasepaymentech_card') != Gorilla_ChasePaymentech_Model_Profile::CARD_TYPE_NEW) {
            if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
                $billingCountry = $paymentInfo->getOrder()->getBillingAddress()->getCountryId();
            } else {
                $billingCountry = $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
            }

            if (!$this->canUseForCountry($billingCountry)) {
                Mage::throwException($this->_getHelper()->__('Selected payment type is not allowed for billing country.'));
            }

            $profile = new Gorilla_ChasePaymentech_Model_Profile();
            if (!$profile->getCustomerPaymentProfile($paymentInfo->getAdditionalInformation('chasepaymentech_card'))) {
                Mage::throwException($this->_getHelper()->__('Error with saved credit card information.'));
            }

            return $this;
        } else {
            return parent::validate();
        }
    }

    /**
     * Send authorize request to gateway
     *
     * @param Varien_Object $payment
     * @param $amount
     * @return Gorilla_ChasePaymentech_Model_Gateway
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException($this->_getHelper()->__('Invalid amount for authorization.'));
        }

        $this->_initCardsStorage($payment);

        //$payment->setRequestType();
        $this->_place($payment, $amount, Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_AUTH_ONLY);
        $payment->setSkipTransactionCreation(true);

        return $this;
    }

    /**
     * this method is called if we are authorising AND
     * capturing a transaction
     *
     * @param Varien_Object $payment
     * @param $amount
     * @return bool|Gorilla_ChasePaymentech_Model_Gateway
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException($this->_getHelper()->__('Invalid amount for capture.'));
        }

        $this->_initCardsStorage($payment);
        if ($this->_isPreauthorizeCapture($payment)) {
            $this->_preauthorizeCapture($payment, $amount);
        } else {
            $this->_place($payment, $amount, Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_AUTH_CAPTURE);
        }

        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * Cancel the payment through gateway
     *
     * @param Mage_Payment_Model_Info|Varien_Object $payment
     * @return Gorilla_ChasePaymentech_Model_Gateway
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }

    /**
     * Called if refunding
     *
     * @param Varien_Object $payment
     * @param $requestedAmount
     * @return Gorilla_ChasePaymentech_Model_Gateway
     */
    public function refund(Varien_Object $payment, $requestedAmount)
    {
        $cardsStorage = $this->getCardsStorage($payment);

        if ($this->_formatAmount($cardsStorage->getCapturedAmount() - $cardsStorage->getRefundedAmount()) < $requestedAmount) {
            Mage::throwException($this->_getHelper()->__('Invalid amount for refund.'));
        }

        // check to make sure we have a credit memo invoice
        $credit_memo = Mage::registry('current_creditmemo');
        if (!$credit_memo->getInvoice()->getTransactionId()) {
            Mage::throwException($this->_getHelper()->__('This invoice does not have a valid transaction ID assigned to it.'));
        }

        $messages = array();
        $isSuccessful = false;
        $isFiled = false;
        foreach($cardsStorage->getCards() as $card) {
            if ($requestedAmount > 0) {
                $cardAmountForRefund = $this->_formatAmount($card->getCapturedAmount() - $card->getRefundedAmount());
                if ($cardAmountForRefund <= 0) {
                    continue;
                }
                if ($cardAmountForRefund > $requestedAmount) {
                    $cardAmountForRefund = $requestedAmount;
                }

                try {
                    $newTransaction = $this->_refundCardTransaction($payment, $cardAmountForRefund, $card);
                    $messages[] = $newTransaction->getMessage();
                    $isSuccessful = true;
                } catch (Exception $e) {
                    Mage::throwException($this->_getHelper()->__('The Payment Gateway declined the refund. Please ensure that this transaction has been fully settled and is not older than 60 days.'));
                }

                $card->setRefundedAmount($this->_formatAmount($card->getRefundedAmount() + $cardAmountForRefund));
                $cardsStorage->updateCard($card);
                $requestedAmount = $this->_formatAmount($requestedAmount - $cardAmountForRefund);
            } else {
                $payment->setSkipTransactionCreation(true);
                return $this;
            }
        }

        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * called if voiding a payment
     *
     * @param Varien_Object $payment
     * @return Gorilla_ChasePaymentech_Model_Gateway
     */
    public function void(Varien_Object $payment)
    {
        $cardsStorage = $this->getCardsStorage($payment);

        $messages = array();
        foreach($cardsStorage->getCards() as $card) {
            try {
                $newTransaction = $this->_voidCardTransaction($payment, $card);
                $messages[] = $newTransaction->getMessage();
            } catch (Exception $e) {
                $messages[] = $e->getMessage();
                Mage::throwException($e->getMessage());
                //continue;
            }
            $cardsStorage->updateCard($card);
        }

        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * Send transaction request to the payment gateway. Only used for Auth Only
     * and AuthCapture
     *
     * @param Mage_Payment_Model_Info $payment
     * @param decimal $amount
     * @param string $requestType
     * @return Gorilla_ChasePaymentech_Model_Gateway
     * @throws Mage_Core_Exception
     */
    protected function _place($payment, $amount, $requestType)
    {
        $payment->setChasePaymentechTransType($requestType);
        $payment->setAmount($this->_formatAmount($amount));

        // Handle saving data for a new profile
        $info = $this->getInfoInstance($payment);

        // Check to see what extra CIM stuff we need to perform this type of transaction
        switch ($payment->getChasePaymentechTransType()) {
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_AUTH_ONLY:
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_AUTH_CAPTURE:
                if ($info->getAdditionalInformation('chasepaymentech_card') !== Gorilla_ChasePaymentech_Model_Profile::CARD_TYPE_NEW) {
                    $payment->setChasePaymentechCustomerRefNum($info->getAdditionalInformation('chasepaymentech_card'));
                }
                break;
            default:
                //Handle other requests
                break;
        }

        // Process the request to the payment gateway
        $result = $this->_postRequest($payment);

        switch ($requestType) {
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_AUTH_ONLY:
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                $defaultExceptionMessage = $this->_getHelper()->__('Payment authorization error.');
                break;
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_AUTH_CAPTURE:
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_CAPTURE:
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                $defaultExceptionMessage = $this->_getHelper()->__('Payment capturing error.');
                break;
        }

        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_APPROVED:
                $payment->setCcLast4($result->getCcLast4());
                $payment->setCcType($this->_formatCcType($result->getCardType()));
                $info->setCcLast4($payment->getCcLast4());
                $info->setCcType($payment->getCcType());

                $quotePayment = $this->_getSession()->getQuote()->getPayment();
                $quotePayment->setCcLast4($payment->getCcLast4())->setCcType($payment->getCcType());

                $card = $this->_registerCard($result, $payment);

                /**
                 * Save the credit card information in Magento
                 */
                if ($result->getCustomerRefNum()) {
                    // Make sure there is no row stored for this CustomerRefNum already
                    $profiles = Mage::getModel("chasepaymentech/profile")
                                        ->getCollection()
                                        ->addFieldToFilter('customer_ref_num', $result->getCustomerRefNum());
                    if ($profiles->getSize() < 1) {
                        // Save profile
                        $model = Mage::getModel("chasepaymentech/profile");
                        $model->setData(array(
                            'customer_id' => $result->getCustomerId(),
                            'customer_ref_num' => $result->getCustomerRefNum()
                        ));
                        $model->save();
                    }
                }

                $this->_addTransaction(
                    $payment,
                    $card->getLastTransId(),
                    $newTransactionType,
                    array('is_transaction_closed' => 0),
                    array($this->_realTransactionIdKey => $card->getLastTransId()),
                    Mage::helper('paygate')->getTransactionMessage(
                        $payment, $requestType, $card->getLastTransId(), $card, $amount
                    )
                );

                if ($requestType == Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_AUTH_CAPTURE) {
                    $card->setCapturedAmount($card->getProcessedAmount());
                    $this->getCardsStorage()->updateCard($card);
                }
                return $this;
            //case self::RESPONSE_CODE_DECLINED:
            //case self::RESPONSE_CODE_ERROR:
            default:
                Mage::throwException($this->_wrapGatewayError($result->getResponseReasonText()));
            //default:
                //Mage::throwException($defaultExceptionMessage);
        }
        return $this;
    }

    /**
     * Send capture request to gateway for capture authorized transactions.
     * Re-authorize if necessary.
     *
     * @param Mage_Payment_Model_Info $payment
     * @param $requestedAmount
     * @return Mage_Paygate_Model_Authorizenet
     */
    protected function _preauthorizeCapture($payment, $requestedAmount)
    {
        $cardsStorage = $this->getCardsStorage($payment);

        if ($this->_formatAmount($cardsStorage->getProcessedAmount() - $cardsStorage->getCapturedAmount()) < $requestedAmount) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for capture.'));
        }

        $messages = array();
        $isSuccessful = false;
        $isFiled = false;
        foreach($cardsStorage->getCards() as $card) {
            if ($requestedAmount > 0) {
                $prevCaptureAmount = $card->getCapturedAmount();
                $cardAmountForCapture = $card->getProcessedAmount();

                if ($cardAmountForCapture > $requestedAmount) {
                    $cardAmountForCapture = $requestedAmount;
                }

                try {
                    $newTransaction = $this->_preauthorizeCaptureCardTransaction($payment, $cardAmountForCapture , $card);
                    $messages[] = $newTransaction->getMessage();
                    $isSuccessful = true;
                    // Perform re-auth if first capture was successful
                } catch (Exception $e) {
                    $messages[] = $e->getMessage();
                    $isFiled = true;
                    continue;
                }

                $newCapturedAmount = $prevCaptureAmount + $cardAmountForCapture;
                $card->setCapturedAmount($newCapturedAmount);
                $cardsStorage->updateCard($card);
                $requestedAmount = $this->_formatAmount($requestedAmount - $cardAmountForCapture);
            }
        }

        if ($isFiled) {
            $this->_processFailureMultitransactionAction($payment, $messages, $isSuccessful);
        }

        /*if ($isFiled && !$isSuccessful) {
            Mage::throwException(Mage::helper('paygate')->convertMessagesToMessage($messages));
        }*/

        return $this;
    }

    /**
     * Send capture request to gateway for capture authorized transactions of card
     *
     * @param Mage_Payment_Model_Info $payment
     * @param decimal $amount
     * @param Varien_Object $card
     * @return bool|Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _preauthorizeCaptureCardTransaction($payment, $amount, $card)
    {
        $authTransactionId = $card->getLastTransId();
        $authTransaction = $payment->getTransaction($authTransactionId);
        $realAuthTransactionId = $authTransaction->getAdditionalInformation($this->_realTransactionIdKey);

        $payment->setChasePaymentechTransType(Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_CAPTURE);
        $payment->setTransId($realAuthTransactionId);
        $payment->setAmount($amount);

        // Get the result
        $result = $this->_postRequest($payment);

        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_NEW_ORDER_SUCCESS:
                if ($result->getApprovalCode() == self::RESPONSE_APPROVAL_STATUS) {
                    $captureTransactionId = $result->getTransactionId() . '-capture';

                    // If split then save the parent transaction id as the splitRefIdx returned in MFC response
                    if ($result->getSplitTransactionId()) {
                        $captureTransactionId = $result->getSplitTransactionId();
                    }

                    $card->setLastCaptureTransId($captureTransactionId);
                    $card->setSplitAuthTransId($result->getSplitTransactionId());
                    $card->setTransRefIdx($result->getTransactionRefIdx());

                    return $this->_addTransaction(
                        $payment,
                        $captureTransactionId,
                        Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
                        array(
                            'is_transaction_closed' => 0,
                            'parent_transaction_id' => $authTransactionId
                        ),
                        array($this->_realTransactionIdKey => $result->getTransactionId()),
                        Mage::helper('paygate')->getTransactionMessage($payment, $payment->getChasePaymentechTransType(), $result->getTransactionId(), $card, $amount)
                    );
                }

                $exceptionMessage = $this->_wrapGatewayError($result->getResponseReasonText());
                Mage::throwException($exceptionMessage);
                return false;
                break;
            case self::RESPONSE_CODE_DECLINED:
            //case self::RESPONSE_CODE_HELD:
            //case self::RESPONSE_CODE_ERROR:
                $exceptionMessage = $this->_wrapGatewayError($result->getResponseReasonText());
                break;
            default:
                $exceptionMessage = Mage::helper('paygate')->__('Payment capturing error.');
                break;
        }

        //$exceptionMessage = Mage::helper('paygate')->getTransactionMessage(
        //    $payment, Mage_Paygate_Model_Authorizenet::REQUEST_TYPE_CAPTURE_ONLY, $realAuthTransactionId, $card, $amount, $exceptionMessage
        //);
        Mage::throwException($exceptionMessage);
        return false;
    }

    /**
     * Void the card transaction through gateway
     *
     * @param Mage_Payment_Model_Info $payment
     * @param Varien_Object $card
     * @return bool|Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _voidCardTransaction($payment, $card)
    {
        $authTransactionId = $card->getLastTransId();
        $authTransaction = $payment->getTransaction($authTransactionId);
        //$captureTransactionId = $card->getLastCaptureTransId();
        $balance = $card->getProcessedAmount() - $card->getCapturedAmount();

        $realAuthTransactionId = $authTransaction->getAdditionalInformation($this->_realTransactionIdKey);

        $payment->setChasePaymentechTransType(Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_VOID);
        $payment->setTransId($realAuthTransactionId);
        $payment->setAmount($this->_formatAmount($balance));
        //$payment->setTransId($captureTransactionId);
        //$payment->setTransRefIdx($card->getTransRefIdx());

        $result = $this->_postRequest($payment);

        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_NEW_ORDER_SUCCESS:
                if ($result->getApprovalCode() == self::RESPONSE_APPROVAL_STATUS) {
                    $voidTransactionId = $result->getTransactionId() . '-void';
                    $card->setLastTransId($voidTransactionId);
                    return $this->_addTransaction(
                        $payment,
                        $voidTransactionId,
                        Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID,
                        array(
                            'is_transaction_closed' => 1,
                            'should_close_parent_transaction' => 1,
                            'parent_transaction_id' => $authTransactionId
                        ),
                        array($this->_realTransactionIdKey => $result->getTransactionId()),
                        Mage::helper('paygate')->getTransactionMessage(
                            $payment, Mage_Paygate_Model_Authorizenet::REQUEST_TYPE_VOID, $result->getTransactionId(), $card
                        )
                    );
                }
                $exceptionMessage = $this->_wrapGatewayError($result->getResponseReasonText());
                break;
            default:
                $exceptionMessage = $this->_getHelper()->__('Payment voiding error.');
                //Mage::log($result->getResponseReasonText());
                if ($result->getResponseReasonText()) {
                    $exceptionMessage = $this->_wrapGatewayError($result->getResponseReasonText());
                }
                //Mage::log($exceptionMessage);
                break;
        }

        $exceptionMessage = Mage::helper('paygate')->getTransactionMessage(
            $payment, Mage_Paygate_Model_Authorizenet::REQUEST_TYPE_VOID, $realAuthTransactionId, $card, false, $exceptionMessage
        );
        Mage::throwException($exceptionMessage);
        return false;
    }

    /**
     * Refund the card transaction through gateway
     *
     * @param Mage_Payment_Model_Info $payment
     * @param $amount
     * @param Varien_Object $card
     * @return bool|Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _refundCardTransaction($payment, $amount, $card)
    {
        /**
         * Card has last transaction with type "refund" when all captured amount is refunded.
         * Until this moment card has last transaction with type "capture".
         */
        $credit_memo = Mage::registry('current_creditmemo');
        $captureTransactionId = $credit_memo->getInvoice()->getTransactionId();
        $captureTransaction = $payment->getTransaction($captureTransactionId);
        $realCaptureTransactionId = $captureTransaction->getAdditionalInformation($this->_realTransactionIdKey);

        $payment->setChasePaymentechTransType(Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_REFUND);
        $payment->setTransId($realCaptureTransactionId);
        $payment->setAmount($amount);

        $result = $this->_postRequest($payment);

        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_NEW_ORDER_SUCCESS:
                if ($result->getApprovalCode() == self::RESPONSE_APPROVAL_STATUS) {
                    $refundTransactionId = $result->getTransactionId() . '-refund';
                    $shouldCloseCaptureTransaction = 0;
                    /**
                     * If it is last amount for refund, transaction with type "capture" will be closed
                     * and card will has last transaction with type "refund"
                     */
                    if ($this->_formatAmount($card->getCapturedAmount() - $card->getRefundedAmount()) == $amount) {
                        $card->setLastTransId($refundTransactionId);
                        $shouldCloseCaptureTransaction = 1;
                    }
                    return $this->_addTransaction(
                        $payment,
                        $refundTransactionId,
                        Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND,
                        array(
                            'is_transaction_closed' => 1,
                            'should_close_parent_transaction' => $shouldCloseCaptureTransaction,
                            'parent_transaction_id' => $captureTransactionId
                        ),
                        array($this->_realTransactionIdKey => $result->getTransactionId()),
                        Mage::helper('paygate')->getTransactionMessage($payment, $payment->getChasePaymentechTransType(), $result->getTransactionId(), $card, $amount)
                    );
                }
                $exceptionMessage = $this->_wrapGatewayError($result->getResponseReasonText());
                break;
            case self::RESPONSE_CODE_DECLINED:
            //case self::RESPONSE_CODE_ERROR:
                $exceptionMessage = $this->_wrapGatewayError($result->getResponseReasonText());
                break;
            default:
                $exceptionMessage = $this->_getHelper()->__('Payment refunding error.');
                break;
        }

        $exceptionMessage = Mage::helper('paygate')->getTransactionMessage($payment, $payment->getChasePaymentechTransType(), $realCaptureTransactionId, $card, $amount, $exceptionMessage);
        Mage::throwException($exceptionMessage);
        return false;
    }

    /**
     * Post the request to the Soap Client in the Profile Model
     *
     * @param Mage_Payment_Model_Info $payment
     * @return Gorilla_ChasePaymentech_Model_Gateway_Result
     */
    public function _postRequest($payment)
    {
        $payment->setAmount($this->_formatAmount($payment->getAmount()));

        /** @var $model Gorilla_ChasePaymentech_Model_Profile */
        $model = Mage::getModel('chasepaymentech/profile');
        switch ($payment->getChasePaymentechTransType()) {
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_AUTH_ONLY:
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_AUTH_CAPTURE:
                $response = $model->createOrderTransaction($payment, $this->isSavingCc());
                break;
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_REFUND:
                $response = $model->createOrderTransaction($payment);           // should be merged with other requests since all are same.
                break;
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_VOID:
                $response = $model->createReversalTransaction($payment);
                break;
            case Gorilla_ChasePaymentech_Model_Profile::TRANS_TYPE_CAPTURE:
                $response = $model->createMarkForCaptureTransaction($payment);
                break;
            default:
                $response = false;
                break;
        }

        /** @var $result Gorilla_ChasePaymentech_Model_Gateway_Result */
        $result = Mage::getModel('chasepaymentech/gateway_result');

        // Parse the direct response
        if ($response) {
            $r = (array) $response;
        } else {
            $r = false;
        }

        if ($r) {
            $result->setResponseCode((int)str_replace('"','',isset($r['respCode']) ? $r['respCode'] : null))
                ->setProcStatusCode((int)str_replace('"','',$r['procStatus']))
                ->setResponseReasonCode((int)str_replace('"','',$r['procStatus']))
                ->setResponseReasonText($r['procStatusMessage'])
                ->setApprovalCode($r['approvalStatus'])
                ->setAvsResultCode(isset($r['avsRespCode']) ? $r['avsRespCode'] : null)
                ->setTransactionId($r['txRefNum'])
                ->setTransactionRefIdx(isset($r['txRefIdx']) ? $r['txRefIdx'] : null)
                ->setSplitTransactionId(isset($r['splitTxRefIdx']) ? $r['splitTxRefIdx'] : null)
                ->setInvoiceNumber($r['orderID'])
                //->setDescription($r['procStatusMessage'])
                ->setAmount($payment->getAmount())
                //->setMethod($r['cardBrand'])
                ->setTransactionType(isset($r['transType']) ? $r['transType'] : null)
                ->setCardCodeResponseCode($r['respCode'])
                ->setCAVVResponseCode(isset($r['cvvRespCode']) ? $r['cvvRespCode'] : null)
                ->setCardType(isset($r['cardBrand']) ? $r['cardBrand'] : null)
                ->setRequestedAmount($payment->getAmount())
                //->setBalanceOnCard($r[54])
                ->setAuthorizationCode(isset($r['authorizationCode']) ? $r['authorizationCode'] : null)
                ->setCcLast4(substr($payment->getCcNumber(), -4))
                ->setCustomerId($this->getCustomer()->getId())
                ->setCustomerRefNum(isset($r['customerRefNum']) ? $r['customerRefNum'] : null)
            ;

            // if we used a stored credit card we need to set the customer ref number and the last 4 of the cc
            if ($payment->getChasePaymentechCustomerRefNum()) {
                $cardObj = $model->getCustomerPaymentProfile($payment->getChasePaymentechCustomerRefNum());
                $result->setCustomerRefNum($cardObj->customerRefNum)
                    ->setCcLast4(substr($cardObj->ccAccountNum, -4));
            }
        } else {
            if ($model->getErrorMessages()) {
                //Mage::log($model->getErrorMessages());
                Mage::throwException(Mage::helper('paygate')->convertMessagesToMessage($model->getErrorMessages()));
            } else {
                Mage::throwException($this->_getHelper()->__('Error in payment gateway.'));
            }
        }

        return $result;
    }

    /**
     * Gateway response wrapper
     *
     * @param string $text
     * @return string
     */
    protected function _wrapGatewayError($text)
    {
        return $this->_getHelper()->__('Gateway error: %s', $text);
    }

    /**
     * Retrieve session object
     *
     * @return Mage_Core_Model_Session_Abstract
     */
    protected function _getSession()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::getSingleton('adminhtml/session_quote');
        } else {
            return Mage::getSingleton('checkout/session');
        }
    }

    /**
     * It sets card`s data into additional information of payment model
     *
     * @param Gorilla_ChasePaymentech_Model_Gateway_Result|Varien_Object $response
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Varien_Object
     */
    protected function _registerCard(Varien_Object $response, Mage_Sales_Model_Order_Payment $payment)
    {
        $cardsStorage = $this->getCardsStorage($payment);
        $card = $cardsStorage->registerCard();
        $card->setRequestedAmount($payment->getAmount())
            ->setBalanceOnCard($response->getBalanceOnCard())
            ->setLastTransId($response->getTransactionId())
            ->setUniqueOrderNumber($response->getInvoiceNumber())       // adds the unique identifier to the card information
            ->setCustomerRefNum($response->getCustomerRefNum())
            ->setAuthorizationCode($response->getAuthorizationCode())
            ->setProcessedAmount($response->getAmount())
            ->setCcType($response->getCardType())
            ->setCcOwner($payment->getCcOwner())
            ->setCcLast4($response->getCcLast4())
            ->setCcExpMonth($payment->getCcExpMonth())
            ->setCcExpYear($payment->getCcExpYear())
            ->setCcSsIssue($payment->getCcSsIssue())
            ->setCcSsStartMonth($payment->getCcSsStartMonth())
            ->setCcSsStartYear($payment->getCcSsStartYear());

        $cardsStorage->updateCard($card);
        //$this->_clearAssignedData($payment);
        return $card;
    }

    /**
     * Add payment transaction
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param string $transactionId
     * @param string $transactionType
     * @param array $transactionDetails
     * @param array $transactionAdditionalInfo
     * @param bool $message
     * @return null|Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _addTransaction(Mage_Sales_Model_Order_Payment $payment, $transactionId, $transactionType,
        array $transactionDetails = array(), array $transactionAdditionalInfo = array(), $message = false
    ) {
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);
        $payment->resetTransactionAdditionalInfo();
        foreach ($transactionDetails as $key => $value) {
            $payment->setData($key, $value);
        }
        foreach ($transactionAdditionalInfo as $key => $value) {
            $payment->setTransactionAdditionalInfo($key, $value);
        }
        $transaction = $payment->addTransaction($transactionType, null, false , $message);
        //foreach ($transactionDetails as $key => $value) {
        //    $payment->unsetData($key);
        //}
        //$payment->unsLastTransId();

        /**
         * It for self using
         */
        $transaction->setMessage($message);

        return $transaction;
    }

    /**
     * Init cards storage model
     *
     * @param Mage_Payment_Model_Info $payment
     */
    protected function _initCardsStorage($payment)
    {
        $this->_cardsStorage = Mage::getModel('chasepaymentech/gateway_cards')->setPayment($payment);
    }

    /**
     * Return cards storage model
     *
     * @param Mage_Payment_Model_Info $payment
     * @return Gorilla_ChasePaymentech_Model_Gateway_Cards
     */
    public function getCardsStorage($payment = null)
    {
        if (is_null($payment)) {
            $payment = $this->getInfoInstance();
        }
        if (is_null($this->_cardsStorage)) {
            $this->_initCardsStorage($payment);
        }
        return $this->_cardsStorage;
    }

    /**
     * Is this an existing customer?
     *
     * @return mixed
     */
    private function isCustomer()
    {
        // If we have a customer ID, then this is a customer, nothing else to check
        return $this->getCustomer()->getId();
    }

    /**
     * Is this a new registrant?
     *
     * @return bool
     */
    private function isRegistrant()
    {
        // If this has no customer ID and has no password hash OR is from the admin panel, this is a new register
        if (!$this->getCustomer()->getId() && ($this->getInfoInstance()->getOrder()->getQuote()->getPasswordHash() || $this->isAdmin())) {
            return true;
        }
        return false;
    }

    /**
     * Is this a guest?
     *
     * @return bool
     */
    private function isGuest()
    {
        // If no customer ID and no password hash, then this is a guest.
        if (!$this->getCustomer()->getId() && !$this->isAdmin()) { // && !$this->getInfoInstance()->getOrder()->getQuote()->getPasswordHash() && !$this->isAdmin()) {
            return true;
        }
        return false;
    }

    /**
     * Check to see if we're inside the admin panel
     *
     * @return bool
     */
    public function isAdmin()
    {
        return Mage::app()->getStore()->isAdmin();
    }

    public function isSavingCc()
    {
        //Mage::log("is guest: " . $this->isGuest());
        //Mage::log("optional: " . $this->getConfigData('save_optional'));
        //Mage::log("additional: " . $this->getInfoInstance()->getAdditionalInformation('cc_save_card'));

        if (!$this->isGuest() && $this->getConfigData('save_optional') && $this->getInfoInstance()->getAdditionalInformation('cc_save_card')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve the customer for this quote
     *
     * @return Mage_Customer_Model_Customer
     */
    protected function getCustomer()
    {
        if ($this->isAdmin()) {
            return Mage::getModel('customer/customer')->load(Mage::getSingleton('adminhtml/session_quote')->getCustomerId()); // Get customer from admin panel quote
        } else {
            return Mage::getModel('customer/session')->getCustomer(); // Get customer from frontend quote
        }
        return $this->magentoCustomer;
    }

    /**
     * Sets up the data on the object
     *
     * @param $data
     * @return Gorilla_ChasePaymentech_Model_Gateway
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        if ($data->getCcSaveCard() == "Yes") {
            $cc_save_card = true;
        } else {
            $cc_save_card = false;
        }

        $info = $this->getInfoInstance();
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear())
            ->setCcSaveCard('true')
            ->setAdditionalInformation('chasepaymentech_card', $data->getChasepaymentechCard())
            ->setAdditionalInformation('cc_save_card', $cc_save_card);
        return $this;
    }

    /**
     * Construct a new profile request for the customer
     *
     * @param $payment
     * @param bool $forcedCustomerId
     * @return Varien_Object
     */
    protected function _buildProfileRequest($payment, $forcedCustomerId = false)
    {
        $customer_id = ($forcedCustomerId) ? null : $this->getCustomer()->getId();
        if (!$customer_id || !$this->isSavingCc()) {
            $customer_id = $payment->getOrder()->getIncrementId() . now();      // Make up a customer ID so we don't ever have duplicate issues
            $description = "Guest or Unsaved Card";
        } else {
            $description = "Magento Customer ID: $customer_id";
        }

        $customerRefNum = $this->getCustomer()->getChasePaymentechCustomerRefNum();

        $billingAddress = $payment->getOrder()->getBillingAddress();
        $customer = new Varien_Object;
        $customer->setEmail($payment->getOrder()->getCustomerEmail())
                ->setId($customer_id)
                ->setDescription($description)
                ->setCustomerRefNum($customerRefNum)
                ->setFirstname($billingAddress->getFirstname())
                ->setLastname($billingAddress->getLastname())
                ->setCompany($billingAddress->getCompany())
                ->setAddress($billingAddress->getStreet(true))
                ->setCity($billingAddress->getCity())
                ->setState($billingAddress->getRegion())
                ->setZip($billingAddress->getPostcode())
                ->setCountry($billingAddress->getCountryId());

        $payment_profile = new Varien_Object;
        $customer->setPaymentProfile($payment_profile);

        $customer->getPaymentProfile()
                ->setCc($payment->getCcNumber())
                ->setCcv($payment->getCcCid())
                ->setExpiration(sprintf('%04d-%02d', $payment->getCcExpYear(), $payment->getCcExpMonth()));

        return $customer;
    }

    /**
     * Check void availability
     *
     * @param Varien_Object $payment
     * @internal param Varien_Object $invoicePayment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment)
    {
        if ($this->_isGatewayActionsLocked($this->getInfoInstance())) {
            return false;
        }
        return $this->_isPreauthorizeCapture($this->getInfoInstance());
    }

    /**
     * Return true if there are authorized transactions
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    protected function _isPreauthorizeCapture($payment)
    {
        if ($this->getCardsStorage()->getCardsCount() <= 0) {
            return false;
        }

        foreach($this->getCardsStorage()->getCards() as $card) {
            $lastTransaction = $payment->getTransaction($card->getLastTransId());
            if (!$lastTransaction || $lastTransaction->getTxnType() != Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
                return false;
            }
        }

        return true;
    }

    /**
     * If gateway actions are locked return true
     *
     * @param  Mage_Payment_Model_Info $payment
     * @return bool
     */
    protected function _isGatewayActionsLocked($payment)
    {
        return $payment->getAdditionalInformation($this->_isGatewayActionsLockedKey);
    }

    /**
     * Mark capture transaction id in invoice
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processInvoice($invoice, $payment)
    {
        $lastCaptureTransId = '';
        $cardsStorage = $this->getCardsStorage($payment);
        foreach($cardsStorage->getCards() as $card) {
            $lastCapId = $card->getData('last_capture_trans_id');
            if ($lastCapId && !empty($lastCapId) && !is_null($lastCapId)) {
                $lastCaptureTransId = $lastCapId;
                break;
            }
        }

        $invoice->setTransactionId($lastCaptureTransId);
        return $this;
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture()
    {
        if ($this->_isGatewayActionsLocked($this->getInfoInstance())) {
            return false;
        }

        if ($this->_isPreauthorizeCapture($this->getInfoInstance())) {
            return true;
        }

        /**
         * If there are not transactions it is placing order and capturing is available
         */
        foreach($this->getCardsStorage()->getCards() as $card) {
            $lastTransaction = $this->getInfoInstance()->getTransaction($card->getLastTransId());
            if ($lastTransaction) {
                return false;
            }
        }
        return true;
    }

    /**
     * Round up and cast specified amount to float or string
     *
     * @param string|float $amount
     * @param bool $asFloat
     * @return string|float
     */
    protected function _formatAmount($amount, $asFloat = false)
    {
        $amount = sprintf('%.2F', $amount);         // "f" depends on locale, "F" doesn't
        //$amount = $amount * 100;
        return $asFloat ? (float)$amount : $amount;
    }

    /**
     * Format the full card name into the 2 character Magento short code for the
     * card type.
     *
     * @param string $ccType
     * @return string $ccType
     */
    protected function _formatCcType($ccType)
    {
        $allTypes = Mage::getSingleton('payment/config')->getCcTypes();
        $allTypes = array_flip($allTypes);
        if (isset($allTypes[$ccType]) && !empty($allTypes[$ccType])) {
            return $allTypes[$ccType];
        }

        return $ccType;
    }

    /**
     * Process exceptions for gateway action with a lot of transactions
     *
     * @param  Mage_Payment_Model_Info $payment
     * @param  string $messages
     * @param  bool $isSuccessfulTransactions
     */
    protected function _processFailureMultitransactionAction($payment, $messages, $isSuccessfulTransactions)
    {
        if ($isSuccessfulTransactions) {
            $currentOrderId = $payment->getOrder()->getId();
            $copyOrder = Mage::getModel('sales/order')->load($currentOrderId);
            foreach($messages as $message) {
                $copyOrder->addStatusHistoryComment($message);
            }
            $copyOrder->save();
        }
        Mage::throwException(Mage::helper('paygate')->convertMessagesToMessage($messages));
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    protected function _debug($debugData)
    {
        if ($this->getDebugFlag()) {
            Mage::getModel('core/log_adapter', 'payment_' . $this->getCode() . '.log')
               ->setFilterDataKeys($this->_debugReplacePrivateDataKeys)
               ->log($debugData);
        }
    }

    /**
     * Used to call debug method from not Paymant Method context
     *
     * @param mixed $debugData
     */
    public function debugData($debugData)
    {
        $this->_debug($debugData);
    }
}
