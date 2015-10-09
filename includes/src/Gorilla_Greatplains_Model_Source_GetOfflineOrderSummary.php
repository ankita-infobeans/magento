<?php

class Gorilla_Greatplains_Model_Source_GetOfflineOrderSummary extends Gorilla_Greatplains_Model_Source_SoapModel {
    //public $CustomerId;
    public $INDNumber = "";
    public $ORGNumber = "";
    public $ContactLastName = "";

    public $_errors;

    public $_return;

    public function __construct($indnumber = "", $orgnumber = "", $lastname = "") {


        $this -> INDNumber = $indnumber;
        $this -> ORGNumber = $orgnumber;
        $this -> ContactLastName = $lastname;
        //$this->CustomerId = $data;
       //print_r($this);
       // die ;
    }

    public function Process($data) {

    	
        if (isset($data->return)) {
            $this -> _errors = $data->return -> error;
            return $this;
        }
        if (!isset($data) || !isset($data -> GetOfflineOrderSummaryResult)) {
            return $this;
        }
        $data = $data -> GetOfflineOrderSummaryResult -> OfflineOrder;
        //Mage::Log( print_r( $data ),true);
        //die;
        // return;
        foreach ($data as $single)
            $this -> _return[] = new Gorilla_Greatplains_Model_Source_Data_OfflineOrder($single);

        return $this;
    }

    public function getErrors() {
        return $this -> _errors;
    }

    public function getData() {

    }

}
?>