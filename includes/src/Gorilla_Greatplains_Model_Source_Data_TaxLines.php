<?php

/*
 * <icc:TaxLines> <icc:OrderLineTaxes> </icc:OrderLineTaxes> </icc:TaxLines>
 */
class Gorilla_Greatplains_Model_Source_Data_TaxLines {
	
	public function construct($line) {
		return new Gorilla_Greatplains_Model_Source_Data_OrderLineTaxes ( $line );
	
	}
}