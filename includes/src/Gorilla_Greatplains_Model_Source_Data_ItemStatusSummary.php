<?php

class Gorilla_Greatplains_Model_Source_Data_ItemStatusSummary {
	
	public $SKU;
	public $QuantityShipped;
	public function __construct($data) {
		$this->SKU = $data->SKU;
		$this->QuantityShipped = $data->QuantityShipped;
	}
}

?>