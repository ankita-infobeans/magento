<?php
class Gorilla_Greatplains_Block_Offlineorder_View extends Mage_Core_Block_Template {

    private $offlineid;
    private $offlineOrder;

    public function __construct() {
        $this -> offlineid = $this -> getRequest() -> getParam('order_number');

        parent::__construct();
        $this -> setTemplate('greatplains/offlineorder/info.phtml');

    }

    public function getOfflineid() {
        return $this -> offlineid;
    }

    public function getStatus() {
        return $this -> offlineOrder[0] -> GetOrderDetailResult -> Status;
    }

    public function getTax() {
        return Mage::helper('core') -> currency($this -> offlineOrder[0] -> GetOrderDetailResult -> Tax);
    }

    public function getSubTotal() {
        return Mage::helper('core') -> currency($this -> offlineOrder[0] -> GetOrderDetailResult -> SubTotal);
    }

    public function getTotal() {
        return Mage::helper('core') -> currency($this -> offlineOrder[0] -> GetOrderDetailResult -> OrderTotal);
    }

    public function getTrackingInfo() {
        $ta = $this -> offlineOrder[0] -> GetOrderDetailResult -> TrackingInfo;

        $trackingarray = array();
  
        foreach ($ta as $t => $tracker) {
            if (is_array($tracker)) {
                return $tracker;
            } else {
                $trackingarray[] = $tracker;

            }
        }
        return $trackingarray;
    }

    public function getShippingTotal() {
        return Mage::helper('core') -> currency($this -> offlineOrder[0] -> GetOrderDetailResult -> ShippingTotal);

    }

    public function getDate() {
        $datearray = explode(" ", $this -> offlineOrder[0] -> GetOrderDetailResult -> OrderDate);
        return $datearray[0];
    }

    public function getOfflineOrder($id) {
        // $customerId = $this->getCustomerId ();

        $offlineordermodel = Mage::getModel("greatplains/offlineorder");

        $offlineorders = $offlineordermodel -> getOfflineOrder();
        $isMine = false;
        foreach ($offlineorders as $oo) {
            if (trim($oo -> OrderNumber) == $id) {
                $isMine = true;
            }
        }

        if (!$isMine) {
           // return false;
        }

        $this -> gp = new Gorilla_Greatplains_Model_Soap();
        $data = $this -> gp -> getOrderDetail($id);

        $this -> offlineOrder = $data -> _return;

        return $this -> offlineOrder;

    }



    public function getItems() {
        if (is_array($this -> offlineOrder[0] -> GetOrderDetailResult -> OrderDetailLines -> OrderDetailLine)) {

            return $this -> offlineOrder[0] -> GetOrderDetailResult -> OrderDetailLines -> OrderDetailLine;
        }
 
        return $this -> offlineOrder[0] -> GetOrderDetailResult -> OrderDetailLines;
    }

    public function getShippingAddress() {
        return $this -> offlineOrder[0] -> GetOrderDetailResult -> ShipToAddress;

    }

    public function getBillingAddress() {
        return $this -> offlineOrder[0] -> GetOrderDetailResult -> BillToAddress;

    }

}
