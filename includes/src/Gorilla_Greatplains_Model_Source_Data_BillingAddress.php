<?php

/*
 * <icc:BillToAddress> <icc:City>435345</icc:City>
 * <icc:ContactName>sdfgsdfgsdfg</icc:ContactName>
 * <icc:Country>sdfgsdfg</icc:Country> <icc:Line1>sdfgsdf</icc:Line1>
 * <icc:Line2>sdfgsdfg</icc:Line2> <icc:Line3>sdfgsdfg</icc:Line3>
 * <icc:Phone1>5555555555</icc:Phone1> <icc:State>IL</icc:State>
 * <icc:UPSZone>?</icc:UPSZone> <icc:Zip>60169</icc:Zip> </icc:BillToAddress>
 */

class Gorilla_Greatplains_Model_Source_Data_BillingAddress extends Gorilla_Greatplains_Model_Source_Data_MemberAddress {

    public function __construct($data) {
        parent::__construct($data);
        return $this;
    }

}