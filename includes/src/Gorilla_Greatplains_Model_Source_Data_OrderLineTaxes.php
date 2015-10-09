<?php

/*
 * <icc:OrderLineTaxes> <icc:TaxAmount>0</icc:TaxAmount>
 * <icc:TaxScheduleDetail>TXSYPLANOCI001</icc:TaxScheduleDetail>
 * </icc:OrderLineTaxes>
 */

class Gorilla_Greatplains_Model_Source_Data_OrderLineTaxes {

    public $TaxScheduleDetail = "FOREIGN";
    public $TaxAmount = 0;
    public $TaxPercent = 0;

    public function __construct($line, $baseprice = 0, $totaltaxes) {

	if (isset($line['id'])) {
	    $this->TaxScheduleDetail = $line['id'];
	}

	if ($baseprice == 0)
	    return $this;

	if ($totaltaxes == 0 || $totaltaxes < 0)/* Bugfix for negative taxes #Ryan */ {
	    return $this;
	}

	$taxmoney = $baseprice * ($line['percent'] / 100);
	$this->TaxPercent = $line['percent'];
	$taxmoney = round($taxmoney, 3);
	if ($taxmoney < 0) {
	    $taxmoney = 0;
	}

	if (strpos($taxmoney, "-") !== false) {
	    $taxmoney = 0;
	}
	$this->TaxAmount = $taxmoney;
	return $this;
    }

}
?>
