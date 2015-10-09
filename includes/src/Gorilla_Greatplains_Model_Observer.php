<?php

class Gorilla_Greatplains_Model_Observer extends Mage_Core_Model_Abstract
{

    function addButton($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $class = get_class($block);
        $id = $block->getId();

        if ($id == "product_edit") {   // $class ==
            // 'Mage_Adminhtml_Block_Catalog_Product_Edit')
            // Mage::Log( "-------------------------------");
            // Mage::Log(print_r($block->debug(),true));
            $block->setChild('fill_form_with_gp', $block->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                        'label'   => Mage::helper('catalog')->__('Get Data from Great Plains'), 'onclick' => 'checkSku()',
                        'class'   => 'add')));
        }
        return $this;
    }

  function afterSaveOrder($observer)
   {
       $event = $observer->getEvent();
       $order = $event->getOrder();
       if($order->getParentOrderId() == null)
       {
           $orderId = null; //$order->getId();
           $this->orderQueuelog("Putting queue in " . $orderId);
           $this->orderQueuelog(print_r($observer->debug(), true));

           // add order to queue. does not run it yet.
           try {
               $this->runQueueOnOrder($orderId, $order->getIncrementId(), $order->getCustomer()->getName());
           } catch (Exception $e) {

           }
       }

       return $this;
   }

    public function runQueueOnOrder($orderId, $incid, $customername)
    {
        if (empty($customername)) {
            $customername = "[guest]";
        }

        $this->orderQueuelog("-----------------------------------------");
        $this->orderQueuelog("Adding order # " . $incid . " to queue. customer: " . $customername);
        if (empty($incid)) {
            return false;
        }

        $q = $this->getQueueInstance();
        $q->addToQueue(
                        $this->getMageModelClass(), 'processOrder', array('orderId' => $incid), $code = 'process-order'
                )
                ->setShortDescription('Send Order Info ' . $incid . ' : ' . $customername . ' to Great Plains')->save();
        return $q;
    }

    public function processOrder($queue)
    {


        //Mage::Log(print_r($queue,true));

        $this->orderQueuelog("Starting processesing order");
        $q = $queue['queue_item'];
        //Mage::Log(print_r($q,true));
        //Mage::Log(print_r($q,true));
        //if(isset($q->getQueueId))

        try {
            // Mage::Log(print_r($function_args,true));
            if (isset($q)) {
                $this->orderQueuelog("Running single queue " . print_r($q->debug(), true));
                $this->run($q);
            } else {
                $this->orderQueuelog("Running all queued objects");
                $qc = Mage::getModel('gorilla_queue/queue')
                        ->getCollection()
                        // ->addFieldToFilter('status', Gorilla_Queue_Model_Queue::STATUS_PROCESSING)
                        ->addFieldToFilter('status', array('neq' => Gorilla_Queue_Model_Queue::STATUS_SUCCESS))
                        //->addAttributeToFilter('status', array('neq' => Gorilla_Queue_Model_Queue::STATUS_SUCCESS))
                        ->addFieldToFilter('status', array('neq' => Gorilla_Queue_Model_Queue::STATUS_ABORTED))
                        ->addFieldToFilter('status', array('neq' => Gorilla_Queue_Model_Queue::STATUS_ABORTED_NOTIFIED))
                        ->addFieldToFilter('model_class', 'greatplains/observer')
                        ->addFieldToFilter('number_attempts', array(array('null' => NULL), array('lt' => 20)));
                ;

                $qc->getSelect()->limit(30);

                $this->orderQueuelog("total is " . $qc->getSize());
                foreach ($qc as $q) {
                    $this->orderQueuelog(".");
                    $this->run($q);
                    // Mage::Log(print_r($q->debug(),true));
                }
            }
            return $this;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function run($q)
    {
        $function_args = unserialize($q->getQueueItemData());

        if (!$q->testAndSetLock()) {
            return; // already processing this queue item
        }

        $id = $function_args['orderId'];
        $this->orderQueuelog("Running order id $id");
        return $this->run2($id, $q);
    }

    public function run2($id, $q)
    {
        $status = Gorilla_Queue_Model_Queue::STATUS_OPEN;

        $order = Mage::getModel('sales/order')->loadByIncrementId($id);
        $this->orderQueuelog('get by order increment id ');
        $orderId = (is_object($order)) ? $order->getId() : null;
        if ((!is_object($order)) || empty($orderId)) {
            $this->orderQueuelog('didnt work. get by order id instead.');
            $order = Mage::getModel('sales/order')->load($id);
        }
        $orderId = (is_object($order)) ? $order->getId() : null;
        if ((!is_object($order)) || empty($orderId)) {
            $status = Gorilla_Queue_Model_Queue::STATUS_ABORTED;
            $q->releaseLock($status);
            return $q;
        }

        $gp = new Gorilla_Greatplains_Model_Soap();
        $this->orderQueuelog(print_r($order->getData(), true));
        $no = $gp->createNewOrder($order);

        $orderCreationResponse = $no;
        Mage::log("gp->createNeOrder($id)", null, 'fed.log', true);
        
        $error = $no->getErrors();
        //Mage::log($orderCreationResponse, null, 'fed.log', true);
        
        $no = !empty($no->_return) && !empty($no->_return[0]) ? $no->_return[0]->CreateNewOrderResult : '';
        $pos = stripos($no, "Success");
        Mage::log('no 2'.$no, null, 'fed.log', true);
        if ($pos === false) 
        {
            /**
             * Add soap request and response logging
             */
            $soapClient = $gp->getSoapClient();
            if ($soapClient instanceof SoapClient) {
                $q->setSoapRequest($soapClient->__getLastRequest());
            }

            if($soapClient->__getLastResponse())
            {
                $q->setErrorMessage($error);
                $q->setSoapResponse($soapClient->__getLastResponse());
            }
            else
            {
                $q->setErrorMessage($orderCreationResponse->_return[0]);
                $q->setSoapResponse($orderCreationResponse->_return[0]);
                
            }
            
            $q->updateFailedAttempt();
            //$q->setShortDescription($error);
            $status = Gorilla_Queue_Model_Queue::STATUS_PROCESSING;
            Mage::log('Export failed:', null, 'fed.log', true);
            Mage::log($no, null, 'fed.log', true);
        } 
        else 
        {
            Mage::log('in success', null, 'fed.log', true);
            $q->setErrorMessage($error);
            /**
             * Add soap request and response logging
             */
            $soapClient = $gp->getSoapClient();
            if ($soapClient instanceof SoapClient) {
                $q->setSoapRequest($soapClient->__getLastRequest());
                $q->setSoapResponse($soapClient->__getLastResponse());
            }
            Mage::log('Success orderId=' . $orderId . ' return=' . $no->_return[0]->CreateNewOrderResult, null, 'fed.log', true);
            $q->updateSuccessfulAttempt();
            $status = Gorilla_Queue_Model_Queue::STATUS_SUCCESS;
        }
        
        Mage::log("releaseLock($status) order=$id", null, 'fed.log', true);
        $q->releaseLock($status);
        return $q;
    }

    private function orderQueuelog($q)
    {
        Mage::Log($q, Zend_Log::DEBUG, 'gp_OrderQueue.log');
    }

    private function getQueueInstance()
    {
        return Mage::getModel('gorilla_queue/queue');
    }

    private function getMageModelClass()
    {
        return 'greatplains/observer';
    }

}
