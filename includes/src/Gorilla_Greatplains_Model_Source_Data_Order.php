<?php

class Gorilla_Greatplains_Model_Source_Data_Order {
	
	public $OrderNumber;
	public $OrderDate;
	public $LineItems;
	public $SubTotal;
	public $Tax;
	public $TrackingInfo;
	public $Status;
	
	public $BillToAddress;
	public $ShipToAddress;
	
	public $MemberFlag;
	public $CustomerName;
	public $CustomerNumber;
	public $CustomerEmail;
	public $CustomerPhone;
	public $BatchNumber;
	public $FreightAmount;
	public $OrderComment;
	public $CCCardHolderName;
	public $CCNumber;
	public $CCExpiredate;
	// public $CCAuthorizationNumber;
	public $CCName;
	public $PaymentAmount;
	public $OrderTotal;
	public $TaxSchedule;
	public $TaxTotal;
	
	public $OrderDetails;
	
	public function __construct($data) {
		
		foreach ( $data as $k => $v ) {
			$this->$k = $v;
		}
		/*
		 * $this->OrderNumber 		= $data->OrderNumber; $this->OrderDate 		=
		 * $data->OrderDate; $this->LineItems 		= $data->LineItems;
		 * $this->SubTotal 		= $data->SubTotal; $this->Tax 				= $data->Tax;
		 * $this->TrackingInfo		= $data->TrackingInfo; $this->Status 			=
		 * $data->Status; if(isset($data->MemberFlag)) $this->MemberFlag	=
		 * $data->MemberFlag; if(isset($data->CustomerName)) $this->CustomerName
		 * = $data->; if(isset($data->MemberFlag)) $this->CustomerNumber=
		 * $data->; if(isset($data->MemberFlag)) $this->CustomerRmail= $data->;
		 * if(isset($data->MemberFlag)) $this->CustomerPhone= $data->;
		 * if(isset($data->MemberFlag)) $this->BatchNumber= $data->;
		 * if(isset($data->MemberFlag)) $this->FreightAmount= $data->;
		 * if(isset($data->MemberFlag)) $this->OrderComment= $data->;
		 * if(isset($data->MemberFlag)) $this->CCCardHolderName= $data->;
		 * if(isset($data->MemberFlag)) $this->CCNumber= $data->;
		 * if(isset($data->MemberFlag)) $this->CCExpiredate= $data->;
		 * if(isset($data->MemberFlag)) $this->CCAuthorizationNumber= $data->;
		 * if(isset($data->MemberFlag)) $this->CCName= $data->;
		 * if(isset($data->MemberFlag)) $this->PaymentAmount= $data->;
		 * if(isset($data->MemberFlag)) $this->SubTotal= $data->;
		 * if(isset($data->MemberFlag)) $this->OrderTotal= $data->;
		 * if(isset($data->MemberFlag)) $this->TaxSchedule= $data->;
		 * if(isset($data->MemberFlag)) $this->TaxTotal= $data->;
		 */
		if (isset ( $data->BillToAddress ))
			$this->BillToAddress = new Gorilla_Greatplains_Model_Source_Data_MemberAddress ( $data->BillToAddress );
		
		if (isset ( $data->ShipToAddress ))
			$this->ShipToAddress = new Gorilla_Greatplains_Model_Source_Data_MemberAddress ( $data->ShipToAddress );
	
	}
}

