<?php

class Gorilla_Greatplains_Model_Source_Data_OrderStatusSummary {

    public $OrderNumber;
    public $Status;
    public $Items;
    public $TrackingNumber;
    //public $data;
    public function __construct($data) {
        //$this->data = $data;
        // return $this;
        $this -> OrderNumber = $data -> OrderNumber;
        $this -> Status = $data -> Status;
        $this -> Items = $data -> Items;
        if (isset($data -> TrackingInfo)) {
            foreach($data -> TrackingInfo as $k=>$v)
            {
                 $this -> TrackingNumber[] = $v;
            }
           
        }
        //foreach ( $data->Items as $d ) {
        //		$this->Items [] = new Gorilla_Greatplains_Model_Source_Data_ItemStatusSummary ( $d );

        //	}

    }

}
?>