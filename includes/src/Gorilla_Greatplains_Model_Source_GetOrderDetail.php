<?php

class Gorilla_Greatplains_Model_Source_GetOrderDetail extends Gorilla_Greatplains_Model_Source_SoapModel {
	
	public $OrderNumber;
	
	public $_errors;
	
	public $_return;
	
	public function __construct($data) {
		$this->OrderNumber = $data;
	
	}
	
	public function Process($data) {
		
		if (isset ( $data->return )) {
			$this->_errors = $data->return->error;
			return $this;
		}
		
		$this->_return [] = new Gorilla_Greatplains_Model_Source_Data_Order ( $data );
		
		return $this;
	}
	public function getErrors() {
		return $this->_errors;
	}
	public function getData() {
	
	}
}

?>