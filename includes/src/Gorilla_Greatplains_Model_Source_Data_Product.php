<?php

class Gorilla_Greatplains_Model_Source_Data_Product {
	public $SKU;
	public $Description;
	public $Weight;
	public $Inventory;
	public $Shippability;
	public $Taxability;
	public $TierPricing;
	
	public function __construct($data) {
		
		// print_r($data);
		//Mage::Log(print_r($data,true));
		if (isset ( $data->GetProductBySKUResult )) {
			$data = $data->GetProductBySKUResult;
		}
		
		$this->Description = $data->Description;
		$this->SKU = $data->SKU;
		$this->Shippability = $data->Shippability;
		$this->Weight = $data->Weight;
		$this->Inventory = $data->Inventory;
		$this->Taxability = $data->Taxability;
		$parser = simplexml_load_string ( $data->TierPricing );
		//Mage::Log(print_r($parser,true));
		if ($parser)
			$this->TierPricing = $parser->Pricing;
			
			// print_r($this);
		return $this;
	}

}

