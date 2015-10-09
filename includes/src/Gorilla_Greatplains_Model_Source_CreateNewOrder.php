<?php

/*
 * <tem:CreateNewOrder> <tem:NewOrder> </tem:NewOrder> </tem:CreateNewOrder>
 */

class Gorilla_Greatplains_Model_Source_CreateNewOrder extends Gorilla_Greatplains_Model_Source_SoapModel {

    public $NewOrder;
    public $_errors;
    public $_return;

    public function __construct($order) {
        $this->NewOrder = new Gorilla_Greatplains_Model_Source_Data_NewOrder($order);
    }

    private function isMemberById($id) {
        return false;
    }

    public function Process($data) {
        $d = $data->CreateNewOrderResult;

        if (stristr($d, "Error occured Creating Order")) {
            $a = explode("Node Identifier Par", $d);
            $d = $a[0];
            $d = explode("Error Description = ", $d);
            $error = $d[1];
            $error = $a[0];

            Mage::Log($error, 1, "gp_new_order_error.log");
            Mage::Log(print_r($d, true), 1, "gp_new_order_error.log");
            $this->_errors = $error;
            return $this;
        }

        if (isset($data->return)) {
            $this->_errors = $data->return->error;
            return $this;
        }

        $this->_return [] = $data;
        return $this;
    }

    public function getErrors() {
        return $this->_errors;
    }

    public function getData() {
        
    }

}

?>