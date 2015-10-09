<?php

class Gorilla_Greatplains_Model_Source_GetProductBySKU extends Gorilla_Greatplains_Model_Source_SoapModel {
	
	public $sku;
	private $_return;
	private $_errors = null;
	
	public function Process($data) {
		// print_r($data);
		$this->_return = new Gorilla_Greatplains_Model_Source_Data_Product ( $data );
		
		return $this;
	}
	
	public function getErrors() {
		return $this->_errors;
	}
	public function getData() {
		return $this->_return;
	}

}

?>