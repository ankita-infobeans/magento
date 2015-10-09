<?php

class Gorilla_Greatplains_Model_Source_UpdateOrder extends Gorilla_Greatplains_Model_Source_SoapModel {
    public $orders;

    public $_errors;

    public $_return;

    public function __construct($data) {
        $this -> orders = $data;
    }

    public function Process($data) {

     

        $updateorderresult = $data -> UpdateOrderResult;
        $orderstatussummary = $updateorderresult -> OrderStatusSummary;

        $this -> _return = array();

        if (count($orderstatussummary) == 1) {
            $this -> _return[] = new Gorilla_Greatplains_Model_Source_Data_OrderStatusSummary($orderstatussummary);
        } else {

            foreach ($orderstatussummary as $order) {
                $this -> _return[] = new Gorilla_Greatplains_Model_Source_Data_OrderStatusSummary($order);
            }
        }

        
        return $this;
    }

    public function getErrors() {
        return $this -> _errors;
    }

    public function getData() {

        return $this -> _return;

    }

}
