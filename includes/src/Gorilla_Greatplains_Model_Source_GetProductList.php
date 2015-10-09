<?php

class Gorilla_Greatplains_Model_Source_GetProductList extends Gorilla_Greatplains_Model_Source_SoapModel {
	
	public $skus;
	public $criteria;
	private $_return;
	private $_errors = null;
	
	public function __construct($data, $criteria = null) {
		// echo "------------------------------\n";
		// print_r($data);
		/*
		 * if ($criteria) { $this->criteria = new
		 * Gorilla_Greatplains_Model_Source_Data_ProductCriteria (); } else {
		 * $this->criteria = new
		 * Gorilla_Greatplains_Model_Source_Data_ProductCriteria (); }
		 */
		
		$this->criteria = array ('Inventory' => true, 'Shippability' => true, 'Taxability' => true, 'TierPricing' => true, 'Weight' => true );
		
		$this->skus = $data;
		
		foreach ( $data as $sku ) {
			//
			// $this->skus [] = $sku;
		}
		
		return $this;
	}
	public function Process($data) {
		
		if (isset ( $data->return )) {
			$this->_errors = $data->return->error;
			return $this;
		}
		
		echo "--------------DONE--------\n";
		// print_r($data);
		
		foreach ( $data->GetProductListResult->Product as $p ) {
			echo ".";
			$this->_return [] = new Gorilla_Greatplains_Model_Source_Data_Product ( $p );
		}
		echo "\n";
		// print_r($data->GetProductBySKUResult);
		
		// print_r($p);
		
		return $this;
	
	}
	
	public function getErrors() {
		return $this->_errors;
	}
	public function getData() {
		
		$d ['skus'] = $this->skus;
		$d ['criteria'] = $this->criteria;
		$d ['products'] = $this->_return;
		return $d;
	
	}

}

?>