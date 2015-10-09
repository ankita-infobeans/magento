<?php

class Gorilla_Greatplains_Model_Order
{
    const DEBUG = true;

    private $_orders;
    private $_gp;

    const MAX_PROCESS = 10;

    private $_order;
    private $_shipment;
    private $totalQty;
    private $_convertOrder;
    private $_lastShipment;
    private $_orderStatus;
    private $_orderchanged;
    private $_shipmentCreated = false;

    function updateOrders()
    {
        $this->log("Update Orders cron run");
        $orderIds = $this->getAllOpenOrders();

        //print_r($orderIds);
        $count = 0;
        $p_orders = array();
        foreach ($orderIds as $o) {
            //     $this -> log(  $o."\n";
            if ($count < self::MAX_PROCESS) {
                $p_orders[] = $o;
                $count++;
            } else {
                // $this->log('about to process these orders numbers: ' . print_r($p_orders, true));
                $this->processOrders($p_orders);
                $p_orders = array();
                $count = 0;
            }
        }
        $this->processOrders($p_orders);
    }

    /**
     * Query GP for order updates. We pass GP a list of orders we want to hear about. GP may respond with a list that
     * includes other orders (e.g. orders that we had as complete but have since been cancelled).
     *
     * @param array $p_orders - array of order numbers to ask GP about.
     */
    function processOrders($p_orders)
    {

        print_r($p_orders);

        // Send request to GP and get response
        $gp_orders = $this->getOrdersFromGp($p_orders);


        print_r($gp_orders);
        foreach ($gp_orders as $gp_order) {
            $this->_orderchanged = false;
            $this->_shipmentCreated = false;
            if ($gp_order->OrderNumber == "") {
                continue;
            }

            // load Magento order object
            $do = $this->loadOrder($gp_order->OrderNumber);
            if ($do) {

                $this->_orderStatus = strtolower(trim($gp_order->Status));

                $this->log($gp_order->OrderNumber . " " . $this->_orderStatus . " | " . $do->getStatus() . " | " . $do->getState());

                // check if this order has been cancelled
                if ($this->_orderStatus == "cancelled" || $this->_orderStatus == "canceled") {
                    $this->log('cancelling the order in Magento.');
                    $this->_order->setStatus('canceled');
                    $this->_order->addStatusToHistory('canceled', 'canceled', false);
                    $this->_order->setState(Mage_Sales_Model_Order::STATE_CANCELED);
                    $this->_order->save();
                    $this->log('Order successfully cancelled');
                    continue; // We're done with this order.
                }

                // Process order items
                $items = $this->processItems($gp_order->Items);


                foreach ($items as $item) {
                    if (!$item) {

                        echo " Item is wrong " . print_r($item) . "\n";

                        continue;
                    }
                    if (!$this->_shipmentCreated) {
                        $this->createShipment();
                    }
                    echo "\n\n\n";
                    echo $item['QuantityShipped'];
                    echo "\n\n\n";
                    $this->addItemToShipment($item['Item'], $item['QuantityShipped']);
                }

                // Process tracking numbers for the order
                // Code currently assumes if there's a tracking number, it's FEDEX. GP does not return a carrier.
                $trackers = $this->processTrackers($gp_order->TrackingNumber);


                if ($gp_order->Status == "Shipped") {

                    echo "Status is shipped\n";
                    //die;
                    if (!$trackers) {
  //                      $trackers = array("NO TRACKER");
                    }

                }

//die;


                if ($trackers) {
                    foreach ($trackers as $tracker) {
                        $this->_orderchanged = true;
                        $this->log("Adding Tracker $tracker");
                        $arrTracking = array('carrier_code' => 'fedex', 'title' => "FEDEX", 'number' => $tracker,);
                        $track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);
                        if ($this->_shipmentCreated) {
                            $this->_shipment->addTrack($track);
                        } elseif ($this->_lastShipment) {
                            $this->_lastShipment->addTrack($track);
                        } else {
                            $this->createShipment();
                            $this->_shipment->addTrack($track);
                        }
                    }
                }

                // If items were shipped or a tracking number was added, let's save this order.
                if ($this->_orderchanged) {
                    try {
                        if($trackers)
                        {
                            $this->saveShipment();
                        }
                        //if($this->_orderStatus != "complete" && $this->_orderStatus != "shipped")
                        //{
						if ($this->_order->getStatus() != "partially_shipped") {
							$this->_order->addStatusToHistory('processing',null, true);
							$this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
						}
                        //}
                        $this->_order->save();
                    } catch (Exception $e) {
                        $this->log("_orderchanged Error saving Shipment : " . $e->getMessage());
                        continue;
                    }

                    $this->saveOrder();
                    $this->completeOrder();
                }


                if (!$this->_orderchanged && $this->_order) {
                    $this->log("order has NOT changed " . $this->_order->getStatus());



                    if ($this->_orderStatus == "complete" || $this->_orderStatus == "shipped") {
                        $this->forceCompleteOrder();
                    }


                    if ($this->_orderStatus == "partially shipped" && $this->_order->getStatus() != "partially_shipped") {
                        echo "PARTIALLY SHIPPED YO\n";
                        $this->_order->addStatusToHistory('partially_shipped', 'Partially Shipped', false);
                        $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
                        $this->_order->save();
                    }

                }
                unset($this->_order);
                unset($this->_shipment);
                unset($this->_convertOrder);
                unset($this->_lastShipment);

                unset($this->_orderStatus);
                unset($this->_orderchanged);
                unset($this->_shipmentCreated);
            }
        }
    }

    function getAllOpenOrders()
    {
        $this->log(__CLASS__ . ":" . __METHOD__ . "(line " . __LINE__ . ")");

        $os = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToFilter('status', array('neq' => 'complete'))
            ->addAttributeToFilter('status', array('neq' => 'canceled'))
            ->addAttributeToFilter('status', array('neq' => 'closed'))
            ->addAttributeToSort('increment_id', 'DESC');
        $a = array();
        foreach ($os as $o) {
            $a[] = $o->getIncrementId();
        }

        return $a;
    }

    function processTrackers($data)
    {
        $trackerArray = array();

        if (is_array($data[0])) {
            $data = $data[0];
        }

        $existingTrackers = $this->getExistingShipmentTrackingNumbers();

        foreach ($data as $tracker) {
            if (!in_array($tracker, $existingTrackers)) {
                $trackerArray[] = $tracker;
            }
        }

        if (count($trackerArray) > 0)
            return $trackerArray;

        return false;
    }

    function processItems($data)
    {

        $orderItemsArray = array();

        print_r($data);


        if (isset($data->ItemStatusSummary)) {

            $data = $data->ItemStatusSummary;
        }

        /*
            Single item so put in array
        fix by rsuess
        */

        //		print_r($data);
        if (isset($data->SKU)) {
            $data = array($data);
        }
        //print_r($data);
        //die;

        foreach ($data as $item) {
            echo "---------------------------------\n";
            print_r($item);
            echo "\n------------------\n";
            $item = (array)$item;

            print_r($item);
            $sku = $item['SKU'];
            $qty = $item['QuantityShipped'];
            echo "\n\n\n:" . $sku . "::\n\n\n";

            $m_item = $this->getItem($sku);

            $this->log("Checking if product $sku:$qty is NOT in magento");
            if (!empty($m_item)) {
                $m_qty_shipped = $m_item->getQtyShipped();
                if ($m_qty_shipped == $qty) { // gp has not shipped any new products
                    continue;
                }

                $this->_orderchanged = true;
                $qtyShippedThisTime = $qty - $m_qty_shipped;
                if ($qtyShippedThisTime > 0) {
                    if ($qtyShippedThisTime > $m_item->getQtyOrdered()) {
                        $qtyShippedThisTime = $m_item->getQtyOrdered() - $m_qty_shipped;
                    }
                    $this->log("QTY SHIPPED THIS TIME " . $qtyShippedThisTime);
                    $orderItemsArray[] = array('QuantityShipped' => $qtyShippedThisTime, 'SKU' => $sku, 'Item' => $m_item);
                }
            }
        }
        return $orderItemsArray;
    }

    function getItem($sku)
    {
        $this->log("Getting $sku");
        $m_items = $this->_order->getAllItems();
        //print_r($m_items);
        foreach ($m_items as $item) {
            $this->log("checking $sku vs " . $this->getGpSku($item->getSku()));
            if ($sku == $this->getGpSku($item->getSku())) {
                return $item;
            }
        }

        return;
    }

    function getGpSku($sku)
    {
        //$this -> log(  "going to get gpsku of $sku\n";
        $product = Mage::getModel('catalog/product');
        $productId = $product->getIdBySku($sku);

        //$this -> log(  $productId . "\n";

        $product = $product->load($productId);
        //print_r($product -> getData());
        //$this -> log(  ("GP SKU : ".$product->getGpSku()."\n");
        return $product->getGpSku();

        //-> getGpSku();
    }

    /**
     * Load all the tracking numbers currently associated with shipments on our order.
     * Also sets $this->_lastShipment to the most recent shipment for this order (again, if any).
     *
     * @return array - tracking numbers for this order, if any.
     */
    function getExistingShipmentTrackingNumbers()
    {
        $this->log("Getting existing shipment tracking number");
        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')->setOrderFilter($this->_order)->load();
        $tracknums = array();
        foreach ($shipmentCollection as $shipment) {
            foreach ($shipment->getAllTracks() as $tracknum) {
                $tracknums[] = $tracknum->getNumber();
            }
            $this->_lastShipment = $shipment;
        }
        echo "done " . $this->_lastShipment . "\n";
        return $tracknums;
    }

    /**
     *  Load an Magento order by it's Increment ID
     *
     * @param $id - Increment ID of an order we want to load.
     * @return mixed - returns order on success, false on failure.
     */
    private function loadOrder($id)
    {
        //  $this->log("loading order id : " . $id);
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($id);
        if ($this->_order) {
            //    $this->log("order found");
            return $this->_order;
        }
        return false;
    }

    /**
     * Query GP for updates. Provides GP with a list of orders. Response may include other orders.
     *
     * @param $orderarray - array of order numbers we want to know the status of from GP.
     * @return mixed - response from GP
     */
    private function getOrdersFromGp($orderarray)
    {
        $gp = new Gorilla_Greatplains_Model_Soap();
        $d = $gp->updateOrder($orderarray);
        return $d->getData();
    }

    /**
     * Create a new shipment for the current order.
     */
    private function createShipment()
    {
        $this->log("Creating shipment");
        $this->_convertOrder = new Mage_Sales_Model_Convert_Order();
        $this->_shipment = $this->_convertOrder->toShipment($this->_order);
        $this->_shipmentCreated = true;
        $this->totalQty = 0;
    }

    /**
     * Save the shipment object, if one was created/modified.
     */
    private function saveShipment()
    {
        $this->log("Saving Shipment");
        if ($this->_shipmentCreated) {
            $this->_shipment->setTotalQty($this->totalQty);
            $this->_shipment->register();
            $this->_shipment->getOrder()->setIsInProcess(true);
            try {

                $saveTransaction = Mage::getModel('core/resource_transaction')->addObject($this->_shipment)->addObject($this->_shipment->getOrder())->save();
            } catch (Exception $e) {
                //$this->log("Cannot save shipment :" . print_r($e, true));
                $this->log("Cannot save shipment :" .$e->getMessage());

            }

        } else {
            if ($this->_lastShipment) {
                $this->_lastShipment->setTotalQty($this->totalQty);
                $this->_lastShipment->save();
            }
        }

        // Send shipment email.
        if ($this->_shipmentCreated) {
            if (!self::DEBUG) {
                $this->log("About to send shipment email");
                $this->_shipment->setEmailSent(true);
                $this->_shipment->sendEmail();
                $this->log("Shipment email sent.");
            }
        }

        // Wrap up.
        $this->_shipmentCreated = false;
        unset($this->_lastShipment);
        unset($this->_shipment);
        unset($this->_convertOrder);
        $this->log("Done saving shipment\n");
    }


    private function createInvoice()
    {

        echo "creating invoice";
        //$order = $this->_order
        if (! $this->_order->getId()) {
            echo " no order id\n";
            return false;
        }

        if (! $this->_order->canInvoice()) {
            echo " cannot invoice";
            return false;
        }

        $savedQtys = array();
        $invoice = Mage::getModel('sales/service_order',  $this->_order)->prepareInvoice($savedQtys);
        if (!$invoice->getTotalQty()) {
            echo "empty total quantity\n";
            return false;
        }
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $invoice->getOrder()->setCustomerNoteNotify(false);
        $invoice->getOrder()->setIsInProcess(true);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
        ->addObject($invoice->getOrder());

        $transactionSave->save();
    }
    private function saveOrder()
    {

        $this->log("Saving order\n");

        try {
			if ($this->_order->getStatus() != "partially_shipped") {
				$this->_order->addStatusToHistory('partially_shipped', 'partially_shipped', true);
				$this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
			}
            try {
                if ($this->_order->getPayment())
                    $this->_order->save();
            } catch (Exception $e) {
                $this->log("Error saving order : " . $e->getMessage());
            }
            // check to see if order is complete by comparing ordered products and shipped products
        } catch (Exception $e) {
            $this->log("Error saving order " . $e->getMessage());
        }

        $this->log("Done saving order\n");
    }


    private function forceCompleteOrder()
    {
        $this->log("Force Completing order");
        $m_items = $this->_order->getAllItems();
        $orderComplete = true;
        foreach ($m_items as $item) {
            if ($item->getQtyOrdered() != $item->getQtyShipped()) {
                if (!$this->_shipmentCreated) {
                    $this->createShipment();
                }
                $qty = $item->getQtyShipped() - $item->getQtyOrdered();
                $this->addItemToShipment($item, $qty);
            }
        }

        $this->saveShipment();

        try {

            $this->createInvoice();


            $this->_order->addStatusToHistory('complete', 'complete', false);
            echo "set state\n";
            $this->_order->setStatus('complete');
            //$this->_order->setState(Mage_Sales_Model_Order::STATE_COMPLETE);

            try {
                if ($this->_order->getPayment())
                    $this->_order->save();
            } catch (Exception $e) {
                $this->log("1 Error saving order : " . $e->getMessage());
            }
            // check to see if order is complete by comparing ordered products and shipped products
        } catch (Exception $e) {
            $this->log("2 Error saving order " . $e->getMessage());
        }

    }


    private function completeOrder()
    {

        $this->log("Completing order");
        $m_items = $this->_order->getAllItems();
        $orderComplete = true;
        foreach ($m_items as $item) {
            if ($item->getQtyOrdered() != $item->getQtyShipped()) {
                $orderComplete = false;
                break;
            }
        }

        if ($orderComplete) {

            //$this -> _order -> setState(Mage_Sales_Model_Order::STATE_COMPLETE);
            //$this -> _order -> save();
            $this->Log("Order is complete");

            echo "set status\n";

            $this->_order->setStatus('complete');
            $this->_order->addStatusToHistory($this->_order->getStatus(), 'Order Completed And Shipped', false);
            if ($this->_order->getPayment())
                $this->_order->save();

            $this->log("Order complete has been saved");
        }

        if ($this->_orderStatus == "in progress") {
            $this->log("Order is in progress so setting status as processing");
            if ($this->_order->getStatus() != 'processing') {
                $this->log("Saving order as processing");
                $this->_order->setStatus('processing');
                $this->_order->save();
            }
        }
        unset($this->_orderchanged);
        unset($this->_orderStatus);
        unset($this->_order);
    }

    private function addItemToShipment($item, $qty)
    {
        //$qty = 1;
        $this->_orderchanged = true;
        $this->log("Adding item to shipment " . $item->getSku() . " : " . $qty);
        $_eachShippedItem = $this->_convertOrder->itemToShipmentItem($item);
        $_eachShippedItem->setQty($qty);
        //$this -> log(  $_eachShippedItem->getQty();
        //print_r($_eachShippedItem);

        $this->_shipment->addItem($_eachShippedItem);
        $this->totalQty += $qty;
    }

    function log($message)
    {
        echo $message . "\n";

        Mage::Log($message, Zend_Log::DEBUG, 'gp_order_cron.log');
    }

}

