<?php

class Gorilla_Greatplains_Model_Source_Data_OfflineOrder {
	
	public $OrderNumber;
	public $OrderDate;
	public $ShippingName;
	public $Total;
	public $Status;
	
	public function __construct($d) {
		
		$this->OrderNumber = $d->OrderNumber;
		$this->OrderDate = $d->OrderDate;
		$this->ShippingName = $d->ShippingName;
		$this->Status = $d->Status;
		$this->Total = $d->Total;
	
	}
	public function getOrderNumber() {
		return $this->OrderNumber;
	}
	public function getOrderDate() {
		return $this->OrderDate;
	}
	public function getShippingName() {
		return $this->ShippingName;
	}
	public function getTotal() {
		return $this->Total;
	}
	public function getStatus() {
		return $this->Status;
	}
	public function __toArray() {
		
		$a = array ('OrderNumber' => $this->OrderNumber, 'OrderDate' => $this->OrderDate, 'ShippingName' => $this->ShippingName, 'Status' => $this->Status, 'Total' => $this->Total );
		
		return $a;
	
	}

}

?>