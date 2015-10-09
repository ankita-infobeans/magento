<?php
class Gorilla_Greatplains_Model_Source_Data_ProductCriteria {
	
	public $Inventory = true;
	public $Shippability = true;
	public $Taxability = true;
	public $TierPricing = true;
	public $Weight = true;
	
	public function __construct() {
		return array ('Inventory' => true, 'Shippability' => true, 'Taxability' => true, 'TierPricing' => true, 'Weight' => true );
	
	}

}