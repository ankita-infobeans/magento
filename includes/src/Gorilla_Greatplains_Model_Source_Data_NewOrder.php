<?php

/*
 * <tem:NewOrder> <icc:BatchNumber>353421</icc:BatchNumber> <icc:BillToAddress>
* ... </icc:BillToAddress>
* <icc:CCAuthorizationNumber>123</icc:CCAuthorizationNumber>
* <icc:CCCardHolderName>asdfasdf</icc:CCCardHolderName>
* <icc:CCExpirationDate>2014-05-30T00:00:00</icc:CCExpirationDate>
* <icc:CCName>asdfasdf</icc:CCName>
* <icc:CCNumber>4111111111111111</icc:CCNumber>
* <icc:CustomerEmail>234@234.com</icc:CustomerEmail>
* <icc:CustomerId>0111693</icc:CustomerId>
* <icc:CustomerName>56534</icc:CustomerName>
* <icc:CustomerPhone>5555555555</icc:CustomerPhone>
* <icc:FreightAmount>0</icc:FreightAmount>
* <icc:MemberFlag>true</icc:MemberFlag>
* <icc:OrderComment>23423423</icc:OrderComment>
* <icc:OrderDate>2014-05-30T00:00:00</icc:OrderDate> <icc:OrderDetails> ...
* </icc:OrderDetails> <icc:OrderNumber>67872</icc:OrderNumber>
* <icc:OrderTotal>10</icc:OrderTotal> <icc:PaymentAmount>0</icc:PaymentAmount>
* <icc:ShipToAddress> ... </icc:ShipToAddress>
* <icc:ShippingMethod>asdf</icc:ShippingMethod> <icc:SubTotal>10</icc:SubTotal>
* <icc:TaxSchedule>00601</icc:TaxSchedule> <icc:TaxTotal>0</icc:TaxTotal>
* </tem:NewOrder>
*/

class Gorilla_Greatplains_Model_Source_Data_NewOrder {

	public $BatchNumber = "";
	public $BillToAddress = "";
	public $CCAuthorizationNumber = "";
	public $CCCardHolderName = "";
	public $CCExpirationDate;
	public $CCName;
	public $CCNumber = "";
	public $CustomerRefNumber = "";
	public $TrxRefNum = "";
	public $CustomerEmail = "";
	public $CustomerId = "";
	public $CustomerName = "";
	public $CustomerPhone = "";
	public $Error = "";
	public $FreightAmount = "";
	public $MemberFlag = "";
	public $OrderComment = "";
	public $OrderDate = "";
	public $OrderDetails = "";
	public $OrderNumber = "";
	public $OrderTotal = "";
	public $PaymentAmount = "";
	public $ShipToAddress = "";
	public $ShippingMethod = "";
	public $SubTotal = "";
	public $TaxSchedule = "";
	public $TaxTotal = "";
	public $OrderPromotionAmount = "";
	public $OrderPromotionID = "";
	//public $OrganizationId;
	public $ORGNumber = " ";
	public $INDNumber = " ";

	public function __construct($order) {

		$this->OrderComment = "";
		$this->CustomerId = "";

		$orderdate = $order->getCreatedAt();

		$this->BatchNumber = "WEB_" . date('Y_m_d', strtotime($orderdate));
		$this->OrderDate = date("Y-m-d", Mage::getModel('core/date')->timestamp(time())) . "T00:00:00";

		// Billing Address (and Shipping Address if none on order)
		$this->BillToAddress = new Gorilla_Greatplains_Model_Source_Data_BillingAddress($order->getBillingAddress());
		if ($order->getShippingAddress() != null) {
			$this->ShipToAddress = new Gorilla_Greatplains_Model_Source_Data_ShippingAddress($order->getShippingAddress());
		}

		// Credit Card
		$this->setCreditCardData($order);

		// Customer info
		$this->setCustomerData($order);

		// Shiping
		$this->FreightAmount = number_format($order->getShippingAmount(),2,'.','');
                $shippingTaxAmount = number_format($order->getShippingTaxAmount(),2,'.','');
                
                //freight tax included in the freight amount
                $this->FreightAmount += $shippingTaxAmount;
                
		$shippingDiscount = number_format($order->getShippingDiscountAmount(),2,'.','');
		$remainingShippingDiscount = $shippingDiscount = empty($shippingDiscount) ? 0 : round($shippingDiscount, 2);
		if ($shippingDiscount > 0) {
			if ($shippingDiscount <= $this->FreightAmount) {    // if we need to subtract from shipping amount
				$this->FreightAmount -= $shippingDiscount;
				$remainingShippingDiscount = 0;
			} else {   // if there is more than shipping amount, we should have leftover (but this shouldn't happen)
				$remainingShippingDiscount -=  $this->FreightAmount;
				$this->FreightAmount = 0;
			}
		}
                
		/* jinal khakharia changed in 5 august 2015 for IMS-67*/
                $freightAmount = floatval($this->FreightAmount);

                if($freightAmount == 0)
                {
                    $this->ShippingMethod = 'FEDEX NOCHG';
                }
                else
                {
                    $this->ShippingMethod = Mage::helper('greatplains')->getShippingMethod($order->getShippingMethod());
                }
                /*end*/

		// Order details (e.g. line items)
		$od = new Gorilla_Greatplains_Model_Source_Data_OrderDetail($order);
		$this->OrderDetails = $od->_orderdetails;
		$this->OrderNumber = $order->getRealOrderId();

		// Discount
		$orderDiscount = $order->getDiscountAmount();
		if ($orderDiscount < 0) {
			$orderDiscount = -1 * $orderDiscount;
		}
		$orderDiscount -= $od->totalLineDiscounts;
		$orderDiscount -= $shippingDiscount;
		$orderDiscount += $remainingShippingDiscount;
		$orderDiscount = max($orderDiscount, 0);
		$roundedOrderDiscount = round($orderDiscount, 2);
		if ($orderDiscount - $roundedOrderDiscount > 0.01) {
			Mage::helper("greatplains")->Log("Discount and rounded discount are off by more than a cent.");
		}
		$this->OrderPromotionAmount = $roundedOrderDiscount;
		$couponCode = substr($order->getCouponCode(), 0, 20);
		$this->OrderPromotionID = (empty($couponCode)) ? "" : $couponCode;

		// Order totals
		$this->OrderTotal   = number_format($order->getGrandTotal(),2,'.','');
		if(isset( $od->taxdata[0]['detail']))
			$this->TaxSchedule  = $od->taxdata[0]['detail'];
		else
			$this->TaxSchedule = "FOREIGN";
		$totalPaid = $order->getTotalPaid();
		$this->PaymentAmount = (!empty($totalPaid)) ? $totalPaid : 0;
                $this->PaymentAmount = number_format($this->PaymentAmount,2,'.','');

		$this->SubTotal     = $od->subtotal;
		$this->TaxTotal     = number_format($order->getTaxAmount(),2,'.','');
                
                //freight tax included in the freight amount
                $this->TaxTotal -= $shippingTaxAmount;
		// $this -> OrderComment = "";

		Mage::helper("greatplains")->Log(print_r($this, true));

		return $this;
	}

	protected function setCustomerData($order)
	{
		// name
		$this->CustomerName = trim($order->getCustomerFirstname() . " " . $order->getCustomerLastname());
		if (empty($this->CustomerName)) {
			$this->CustomerName = $this->BillToAddress->ContactName;
		}

		$this->CustomerEmail = $order->getCustomerEmail();
		$this->CustomerPhone = preg_replace("[^0-9]", "", $order->getBillingAddress()->getTelephone());

		// Data not stored on order.
		// Will always be blank if this is a guest.

		$avcustomer = new ICC_Avectra_Model_Account();
		$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
		if ($customer->getCustomerNo()) {
			$this->CustomerId   = $customer->getCustomerNo();
			$this->INDNumber    = $customer->getCustomerNo();
		//	if( $customer->getOrgCustomerNo() != null)
		//		$this->ORGNumber    = $customer->getOrgCustomerNo();
		
			$user = $avcustomer->getUserByRecNo($this->CustomerId)->WEBWebUserGetByRecno_CustomResult;
	 	        $orgcustno = null;
	                $orgcustno = $avcustomer->getUserOrgCustomerNo($user->CurrentKey);

 if($orgcustno)
            {
                if(is_array($orgcustno))
                {
                    $orgcustno = $orgcustno[0];
                }
                 $this->ORGNumber = $orgcustno;
            }

	              
		}
		if ($customer->getMemberStatus()) {
			$this->MemberFlag = $customer->getMemberStatus();
		} else {
			$this->MemberFlag = "";
		}

	}

	/**
	 * Set all the credit card information needed by the GP createNewOrder xml
	 *
	 * @param $order
	 */
	protected function setCreditCardData($order)
	{
		$payment = $order->getPayment();

		// defaults
		$last4      = $payment->getCcLast4();
		$last4      = (!empty($last4)) ? $last4 : "0000";
		$ccnum      = $this->createCCnumber($order->getIncrementId(), $last4);
		$ccexpm     = $ccexpy = null;
		$ccname     = "CHKMO"; //default to check/mo
		$ccauthnum  = "";
		$custrefnum = "";
		$trxrefnum  = "";

		// if it's a real credit card (stored or new)
		$ccinfo = $payment->getAdditionalInformation();
		if ( !empty($ccinfo) && isset($ccinfo['authorize_cards']) && !empty($ccinfo['authorize_cards']) ) {
			$ccinfo = end($ccinfo['authorize_cards']); // assumes only a single card per order (no split payments)
			if (!empty($ccinfo)) {
				$ccnum      = $ccinfo['unique_order_number'];
				$ccname     = $this->translateCCName($ccinfo['cc_type']);
				$ccexpm     = $ccinfo['cc_exp_month'];
				$ccexpy     = $ccinfo['cc_exp_year'];
				$ccauthnum  = isset($ccinfo['authorization_code'])  ? $ccinfo['authorization_code'] : "";
				$custrefnum = isset($ccinfo['customer_ref_num'])    ? $ccinfo['customer_ref_num']   : "";
				$trxrefnum  = isset($ccinfo['last_trans_id'])       ? $ccinfo['last_trans_id']      : "";
			}
		} elseif ($payment->getMethod() == 'icc_billmember') {
			$ccname = "BILLMEMBER";
		}

		// @deprecated - used with old paymentech module
		//if (empty($last4)) {
			//    $cardid = $order->getPayment()->getAdditionalInformation('paymentech_card');
			//    $profile = Mage::getModel('paymentech/profile_soap');
			//    $data = $profile->fetchProfile($cardid);
			//    $ccnum = $data->return->ccAccountNum;
			//    $last4 = substr($ccnum, -4);
			//}

			$this->setCCExpirationDate($ccexpm, $ccexpy, "MMYY");
			$this->CCName                   = $ccname;
			$this->CCCardHolderName         = $order->getBillingAddress()->getName();
			$this->CCNumber                 = $ccnum;
			$this->CCAuthorizationNumber    = $ccauthnum;
			$this->CustomerRefNumber        = $custrefnum;
			$this->TrxRefNum                = $trxrefnum;
	}

	/**
	 * @deprecated
	 */
	function processShipping($id) {
		//FIRST_OVERNIGHT; PRIORITY_OVERNIGHT; STANDARD_OVERNIGHT; FEDEX_2_DAY_AM; FEDEX_2_DAY; FEDEX_EXPRESS_SAVER;
		return "FEDEXPRIOR";
		return "FEDEX";
	}

	/**
	 * Compose the "CC Number". Actually a unique value to be sent to GP using
	 * the GP CC number field, but not containing the full actual CC number.
	 *
	 * @param string $order - order id
	 * @param string $ccnumber - last 4 digits of the CC number
	 * @return string
	 */
	function createCCnumber($order, $ccnumber) {
		$last4 = substr($ccnumber, -4);
		$ccnumber = (string) $order . (string) $last4;
		$ccnumber = str_pad($ccnumber, 13, "0", STR_PAD_LEFT);
		$ccnumber = substr($ccnumber, -13);
		return $ccnumber;
	}

	/**
	 * Composes and sets the CCExpirationDate field for GP.
	 * If we have a value, use that. Otherwise, use right now.
	 *
	 * @param string $month
	 * @param string $year
	 */
	protected function setCCExpirationDate($month, $year, $format="GPTIMESTAMP")
	{
		$timestamp = Mage::getModel('core/date')->timestamp(time());
		if (!empty($month) && !empty($year)) {
			$timestamp = mktime(0, 0, 0, $month, 1, $year);
		}

		$exp = "";
		switch ($format) {
			case "GPTIMESTAMP":
				$exp = date("Y-m-d", $timestamp);
				$exp .= "T00:00:00";
				break;
			case "MMYY":
				$exp = date("my", $timestamp);
				break;
			default:
				$exp = (string) $month . $year;
				break;
		}

		$this->CCExpirationDate = $exp;
	}

	public function translateCCName($cc)
	{
		$ccnames = array(
				"AX" => "AMEX",
				"AE" => "AMEX",
				"DI" => "DISC",
				"MC" => "MC",
				"VI" => "VISA"
		);
		if (isset($ccnames[$cc])) {
			return $ccnames[$cc];
		}
		return $cc;
	}

}
