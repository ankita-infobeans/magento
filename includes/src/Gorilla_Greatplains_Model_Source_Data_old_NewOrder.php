<?php
class Gorilla_Greatplains_Model_Source_Data_oldNewOrder extends Gorilla_Greatplains_Model_Source_Data_Order {
	
	public function __construct($order) {
		// Mage::Log(print_r($order->debug(),true));
		$this->CustomerName = $order->getCustomerFirstname () . " " . $order->getCustomerLastname ();
		$this->CustomerEmail = $order->getCustomerEmail ();
		
		$this->MemberFlag = $this->isMemberById ( $order->getCustomerId () );
		
		$this->CustomerNumber = $order->getCustomerId ();
		
		// $this->CustomerPhone = $order->get ();
		// $this->BatchNumber = $order->get ();
		$this->FreightAmount = $order->getShippingAmount ();
		// $this->OrderComment = $order->get ();
		// $this->CCCardHolderName = $order->get ();
		// $this->CCNumber = $order->get ();
		// $this->CCExpiredate = $order->get ();
		// $this->CCAuthorizationNumber = $order->get ();
		// $this->CCName = $order->get ();
		$this->PaymentAmount = $order->getGrandTotal ();
		$this->OrderTotal = $order->getGrandTotal ();
		// $this->TaxSchedule = $order-> ();
		$this->TaxTotal = $order->getTaxAmount ();
		
		$this->BillToAddress = new Gorilla_Greatplains_Model_Source_Data_BillingAddress ( $order->getBillingAddress () );
		
		$this->CustomerPhone = $this->BillToAddress->Phone1;
		
		$this->ShipToAddress = new Gorilla_Greatplains_Model_Source_Data_ShippingAddress ( $order->getShippingAddress () );
		
		// Mage::Log(print_r($order->getBillingAddress()->debug(),true));
		
		$this->OrderNumber = $order->getId ();
		
		$od = new Gorilla_Greatplains_Model_Source_Data_OrderDetails ( $order );
		
		$this->OrderDetails = $od->_orderdetails;
		
		$this->OrderDate = date ( "Y-m-d H:i:s", Mage::getModel ( 'core/date' )->timestamp ( time () ) );
		
		return $this;
	
	}
	
	private function isMemberById($id) {
		
		return false;
	}
}

//checkout_submit_all_after
//sales_model_service_quote_submit_success
//sales_order_place_after