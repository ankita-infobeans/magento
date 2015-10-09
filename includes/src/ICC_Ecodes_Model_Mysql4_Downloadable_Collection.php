<?php
class ICC_Ecodes_Model_Mysql4_Downloadable_Collection 
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    const NUM_SERIALS_REMAINIG_EXCEPTION = 'Number of serials remaining to be associated is less than or equal to zero.';
    /**
     * @var Varien_Object $_info
     */
    private $_info;
    
    /**
     * This flag attempts to stop the call being made multiple times
     * @var bool $_gpSkuHasBeenJoined
     */
    private $_gpSkuHasBeenJoined = false;

    public function _construct()
    {
        $this->_init('ecodes/downloadable');
    }
    
    public function attachAdminGridColumns()
    {
        $this->attachOrderInfo();
        return $this;
    }
    
    public function attachProductInfo()
    {
        // attach eav gp sku  
        $this->getSelect()
                ->joinLeft(
                            array('ecodes_order_item' => 'sales_flat_order_item' ),
                            'main_table.order_item_id = ecodes_order_item.item_id'
                        )
                ->joinLeft(
                        array('product_varchar_title' => 'catalog_product_entity_varchar'),
                        '`ecodes_order_item`.`product_id`=`product_varchar_title`.`entity_id` AND (SELECT attribute_id FROM `eav_attribute` WHERE `attribute_code` = \'name\' AND `entity_type_id` = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = \'catalog_product\')    ) ',
                        'product_varchar_title.value AS title'
                    );
        
        return $this;
    }
    
    public function attachOrderInfo()
    {
        // add a Yes/No column based on whether the serial's been attached to 
        // an order item.
        $this->getSelect()->columns(
                array('attached_to_order_item' => 'IF(`main_table`.`order_item_id`, \'Yes\', \'No\')'),
                'main_table'
                );

        // add customer_id if the serial number's been attatched to an order item.
        $this->getSelect()
                ->joinLeft(
                        array('order_item' => 'sales_flat_order_item'),
                        'main_table.order_item_id = order_item.item_id',
                        array()
                        )
//                ;
                ->joinLeft(
                        array('order' => 'sales_flat_order'),
                        'order_item.order_id = order.entity_id',
                        'customer_id'
                        );

        $this->getSelect()->columns('increment_id', 'order');
        return $this;
    }

    /**
     * @return ICC_Ecodes_Model_Mysql4_Downloadable_Collection
     */
    public function attachDowloadableInfo()
    {
        $this->getSelect()
            ->joinLeft(
                array('downloadable' => 'downloadable_link_purchased_item'),
                'order_item.item_id = downloadable.order_item_id',
                array('number_of_downloads_bought', 'number_of_downloads_used', 'link_hash')
            )
            ->group('main_table.id');

        return $this;
    }

    /**
     * @param $customerId
     * @return Mage_Downloadable_Model_Mysql4_Link_Purchased_Collection
     */
    public function getDownloadableHistory($customerId)
    {
        try{
            /* @var $downloadableHistory Mage_Downloadable_Model_Mysql4_Link_Purchased_Collection */
            //$downloadableHistory = Mage::getResourceModel('downloadable/link_purchased_collection') for overriding the getSelectCountSql() method to make COUNT() of collection working
            $downloadableHistory = Mage::getResourceModel('ecodes/link_purchased_collection')
                ->addFieldToFilter('main_table.customer_id', $customerId)
                ->addOrder('main_table.created_at', 'desc');
            $downloadableHistory->getSelect()->reset(Zend_Db_Select::COLUMNS);
            $downloadableHistory->getSelect()
                // Serials (eCodes)
                ->joinLeft(
                    array('ecodes' => 'ecodes_downloadable'),
                    'main_table.order_item_id = ecodes.order_item_id',
                    array('serial')
                )
                // All Downloadable Products
                ->joinLeft(
                    array('dlpi' => 'downloadable_link_purchased_item'),
                    'main_table.purchased_id = dlpi.purchased_id',
                    array('number_of_downloads_bought', 'number_of_downloads_used', 'link_hash', 'link_id', 'status', 'link_title', 'product_id') /* field `link_title` added by GEMS */
                )
                // Sales Information
                ->joinLeft(
                        array('order_item' => 'sales_flat_order_item'),
                        '`main_table`.`order_item_id` = `order_item`.`item_id`',
                        array('created_at', 'name')
                        )
                ->joinLeft(
                        array('order' => 'sales_flat_order'),
                        '`order_item`.`order_id` = `order`.`entity_id`',
                        array('increment_id')
                        )
                // can't use orWhere() 
                // since we need:
                //  "... AND (enabled=1 OR enabled is null) AND ..."
                // but orWhere results in:
                //  "... AND (enabled=1) OR (enabled is null) AND ..."
                ->where('ecodes.enabled = 1 OR ecodes.enabled is null');

            $downloadableHistory->addFieldToFilter('dlpi.status',
                    array(
                        'nin' => array(
                            Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PENDING_PAYMENT,
                            Mage_Downloadable_Model_Link_Purchased_Item::LINK_STATUS_PAYMENT_REVIEW,
                            ICC_Ecodes_Helper_Downloadable::LINK_STATUS_REFUNDED,
                            ICC_Ecodes_Helper_Downloadable::LINK_STATUS_DELETED                    
                        )
                    )
                );
//            Mage::log(__METHOD__ . ':' . __LINE__);
//            Mage::log($downloadableHistory->getSelect()->__toString());
        }catch(Exception $e){
            Mage::logException($e);
        }

        return $downloadableHistory;
    }

    /**
     * @param array $orderIds
     * @return ICC_Ecodes_Model_Mysql4_Downloadable_Collection
     */
    public function getOrderInfo(array $orderIds)
    {
        try{
            $this->attachOrderInfo()
                ->attachProductInfo()
                ->attachDowloadableInfo();
            $this->getSelect()->where('order_item.order_id in (?)', $orderIds);
        }catch(Exception $e){
            Mage::logException($e);
        }

        return $this;
    }
    
    
    /**
     * @param $threshold
     */
    /**
     * Prepares our collection for the remaining report by using it to filter a 
     * product collection and attach a usage stat
     * 
     * @param numeric $threshold
     * @return Mage_Catalog_Model_Resource_Product_Collection 
     */
    public function prepareForRemainingReport($threshold)
    {
//        $products = Mage::getModel('catalog/product')
//                        ->getCollection()
//                            ->addAttributeToFilter('serial_required', array('eq' => 1)) // filter for only downloadable and downloadable that need serials
//                            ->addAttributeToFilter('gp_sku', array('notnull'=>true))
//                            ->addAttributeToSelect('name')
//                            ->addAttributeToSelect('gp_sku')
////                            ->groupByAttribute('sku')
//                            ->groupByAttribute('gp_sku'); #@GPSKU uncomment if using gp_sku
//                            ;
//
//        $this->filterOutUnavailable();
//        $this->addRemainingUnusedInfo($threshold);
//
//        $products->getSelect()
//                    ->joinLeft(
//                            array('ecodes' => $this->getSelect()),
//                            '`ecodes`.`gp_sku` = `at_gp_sku`.`value`',
//                            'num_available_serials'
//                            );
//        return $products;



        $product = Mage::getModel('catalog/product');
        $prods = $product->getCollection();
        $prods
            ->addAttributeToFilter('serial_required', array('eq' => 1))
            ->addAttributeToFilter('attribute_set_id', 15)
            ->addAttributeToFilter('gp_sku', array('notnull'=>true))
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('gp_sku')
        ;

        $serials = Mage::getModel('ecodes/downloadable')->getCollection();

        $serials
            ->addExpressionFieldToSelect('num_available_serials', 'COUNT({{serial}})', 'serial')
            ->addFieldToFilter('enabled', array('eq'=>1))
        ;

        $serials->getSelect()
            ->group('gp_sku')
            ->where('order_item_id = "" OR order_item_id IS NULL')
        ;

        $prods
            ->getSelect()
            ->joinLeft(
            array('ecodes' => $serials->getSelect() ),
            '`ecodes`.`gp_sku` = `at_gp_sku`.`value`',
            'ecodes.*'
        );

        $prods
            ->getSelect()
            ->where('ecodes.num_available_serials < '.$threshold . ' OR ecodes.num_available_serials IS NULL' )
        ;
        return $prods;
    }
    
    /**
     * Make sure to only select rows that haven't been assigned to an order
     * and have enabled set to 1 (disabled should not count as available)
     */
    public function filterOutUnavailable()
    {
        $this->addFieldToFilter('enabled', array('eq'=>1));
//        $this->addFieldToFilter('order_item_id', array('eq'=>'', 'null'=>true ));
        $this->getSelect()->where( 'order_item_id = "" OR order_item_id IS NULL' );
        return $this;
    }
    
    /**
     * Add a count() 
     * Group by gp_sku
     * Remove any rows that are >= $threshold
     *
     * @param numeric $threshold 
     */
    public function addRemainingUnusedInfo($threshold)
    {
        $this->addExpressionFieldToSelect('num_available_serials', 'COUNT({{serial}})', 'serial');
        $this->getSelect()
                ->group('gp_sku')   #@GPSKU: need to change this to gp_sku
                ->having('num_available_serials < '.$threshold)
                ->orHaving('num_available_serials IS NULL');
    }
    
    /**
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param $numSerialsRemaining
     * @param array|null $serialIds
     * @return array
     */
    public function _getAvailableSerials(Mage_Sales_Model_Order_Item $orderItem, $numSerialsRemaining,
                                         array $serialIds = null
    )
    {
        $gpSku = $this->getGpSkuFromProductId( $orderItem->getProductId() );
        $select = $this->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->reset(Zend_Db_Select::WHERE)
            ->columns('serial')
            ->where('enabled = ?', true)
            ->where( 'order_item_id = "" OR order_item_id IS NULL' )
            ->where('gp_sku = ?', $gpSku)
            ->limit($numSerialsRemaining)
            ->forUpdate();
        if($serialIds !== null)
        {
            $select->where('id in (?)', $serialIds);
        }

        $serialsAvailable = array();
        foreach($this->getConnection()->fetchAll($select) as $serialAvailable)
        {
            $serialsAvailable[] = $serialAvailable['serial'];
        }
        return $serialsAvailable;
    }

    /**
     * Assign appropriate amount of serial numbers to the order item.
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return bool
     * @throws ICC_Ecodes_Exception
     */
    public function assignSerials(Mage_Sales_Model_Order_Item $orderItem)
    {
        $numSerialsTotal = $orderItem->getQtyInvoiced();
        $this->getConnection()->beginTransaction();

        // Only assign the remaining number of serials.
        $assignedSerials = $this->getAssignedSerials($orderItem);
        $numSerialsRemaining = (int) ($numSerialsTotal - count($assignedSerials));

        if($numSerialsRemaining == 0){
            return false;
        }elseif($numSerialsRemaining < 0){
            throw new ICC_Ecodes_Exception(self::NUM_SERIALS_REMAINIG_EXCEPTION);
        }

        $serialsAvailable = $this->_getAvailableSerials($orderItem, $numSerialsRemaining);
        if(count($serialsAvailable) >= 1)
        {
            // Try to assign what we have avaialable.
            try{
                $this->getConnection()
                    ->update(
                        $this->getMainTable(),
                        array(
                            'order_item_id' => $orderItem->getId()
                        ),
                        array('serial in (?)' => $serialsAvailable)
                    );
                $this->getConnection()->commit();
                $this->setInfo('serials_added', $serialsAvailable);
                if(($numSerialsNotAssigned = $numSerialsRemaining - count($serialsAvailable)) != 0)
                {
                    // Still some serials left to be assigned.
                    $this->_scheduleSerialAssignment($orderItem);
                    $this->setInfo('num_serials_not_assigned', $numSerialsNotAssigned);
                }elseif($queueItem = $this->getInfo('queue_item')){
                    $queueItem->update(array('status' => Gorilla_Queue_Model_Queue::STATUS_SUCCESS));
                }

                // Log in Order History.
                $this->_logInOrderHistory($orderItem);
                return true;
            }catch(Exception $e){
                $this->getConnection()->rollBack();
                Mage::logException($e);

                // We did not assign the available serials. Schedule with the Queue.
                $this->_scheduleSerialAssignment($orderItem);
                $this->setInfo('serials_added', array());
                $this->setInfo('num_serials_not_assigned', $numSerialsRemaining);

                // Log in Order History.
                $this->_logInOrderHistory($orderItem);
                return false;
            }
        }elseif(count($serialsAvailable) == 0){
            // Release the transaction.
            $this->getConnection()->rollBack();

            // No serials Available for this product, assign all to the Queue.
            $this->_scheduleSerialAssignment($orderItem);
            $this->setInfo('serials_added', array());
            $this->setInfo('num_serials_not_assigned', $numSerialsRemaining);
            return false;
        }

        // Assume something went wrong.
        return false;
    }

    public function assignSpecificSerials(Mage_Sales_Model_Order_Item $orderItem, array $serialIds)
    {
        $numSerialsRequested = count($serialIds);
        $numSerialsTotal = $orderItem->getQtyInvoiced();

        $this->getConnection()->beginTransaction();

        // Only assign the remaining number of serials.
        $assignedSerials = $this->getAssignedSerials($orderItem);
        $numSerialsRemaining = (int) ($numSerialsTotal - count($assignedSerials));

        if($numSerialsRemaining == 0){ //die('no serials need be applied: ' . $orderItem->getId());
            $this->addError('No more serials need to be applied to this order.');
            return false;
        }elseif($numSerialsRemaining < 0){
            throw new ICC_Ecodes_Exception(self::NUM_SERIALS_REMAINIG_EXCEPTION);
        }elseif($numSerialsRemaining - $numSerialsRequested < 0){
            $this->addError('Number of Serials requested exceeds the number of serials remaining for this order Item.');
            return false;
        }

        $serialsAvailable = $this->_getAvailableSerials($orderItem, $numSerialsRemaining, $serialIds);

        if(count($serialsAvailable) >= 1)
        {
            // Try to assign what we have avaialable.
            try{
                $this->getConnection()
                    ->update(
                        $this->getMainTable(),
                        array(
                            'order_item_id' => $orderItem->getId()
                        ),
                        array('serial in (?)' => $serialsAvailable)
                    );
                $this->getConnection()->commit();
                $this->setInfo('serials_added', $serialsAvailable);
                if($this->getInfo('assign_remaining_to_queue'))
                {
                    if(($numSerialsNotAssigned = count($serialsAvailable) - $numSerialsRemaining))
                    {
                        // Still some serials left to be assigned.
                        $this->_scheduleSerialAssignment($orderItem);
                        $this->setInfo('num_serials_not_assigned', $numSerialsNotAssigned);
                    }elseif($queueItem = $this->getInfo('queue_item')){
                        $queueItem->update(array('status' => Gorilla_Queue_Model_Queue::STATUS_SUCCESS));
                    }
                }

                // Log in Order History.
                $this->_logInOrderHistory($orderItem);
                return true;
            }catch(Exception $e){
                $this->getConnection()->rollBack();
                Mage::logException($e);

                if($this->getInfo('assign_remaining_to_queue'))
                {
                    // We did not assign the available serials. Schedule with the Queue.
                    $this->_scheduleSerialAssignment($orderItem);
                }

                $this->setInfo('serials_added', array());
                $this->setInfo('num_serials_not_assigned', $numSerialsRemaining);

                // Log in Order History.
                $this->_logInOrderHistory($orderItem);
                return false;
            }
        }elseif(count($serialsAvailable) == 0){
            // Release the transaction.
            $this->getConnection()->rollBack();

            // No serials Available for this product
            if($this->getInfo('assign_remaining_to_queue'))
            {
                $this->_scheduleSerialAssignment($orderItem);
                $this->setInfo('serials_added', array());
                $this->setInfo('num_serials_not_assigned', $numSerialsRemaining);
            }else{
                $this->addError('No serials were added');
            }
            return false;
        }

        // Assume something went wrong.
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return bool
     */
    private function _logInOrderHistory(Mage_Sales_Model_Order_Item $orderItem)
    {
        // Isolate exceptions.
        try{
            $downloadableHelper = Mage::helper('ecodes/downloadable');
            $orderItem->getOrder()
                ->addStatusHistoryComment(
                    $downloadableHelper->generateSerialsAssignedHistoryComment(
                        $this->getInfo('serials_added'),
                        $this->getInfo('num_serials_not_assigned')
                    )
                )
                ->setIsVisibleOnFront(false)
                ->setIsCustomerNotified(false)
                ->save();
            return true;
        }catch(Exception $e){
            Mage::logException($e);
            $this->addError($e->getMessage());
        }
    }

    private function _scheduleSerialAssignment(Mage_Sales_Model_Order_Item $orderItem)
    {
        $queueItem = $this->getInfo('queue_item');
        if(($queueItem instanceof Gorilla_Queue_Model_Queue) && $queueItem->getId())
        {
            try{
                $queueItem->update();
                return true;
            }catch(Exception $e){
                $ecodesException = new ICC_Ecodes_Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
                Mage::logException($ecodesException);
                $this->addError(
                    'There was an error registering with the Queue. Some eCodes may not recieve serial assignments.'
                );
                return false;
            }
        }else{
            try{
                Mage::getModel('gorilla_queue/queue')
                    ->addToQueue(
                                'ecodes/observer',
                                'assignSerialsFromQueue',
                                array(
                                    'order_item_id' => $orderItem->getId(),
                                    'sku' => $orderItem->getSku(),
                                    'gp_sku' => $this->getGpSkuFromProductId($orderItem->getProductId()) // Mage::getModel('catalog/product')->load($orderItem->getProductId())->getGpSku()
                                    ),
                                'ecodes-assign-serials'
                                )
                    ->setShortDescription('Order Item '.$orderItem->getId().' needs a serial. ['.$orderItem->getName().']')
                    ->save();
                return true;
            }catch(Exception $e){
                $ecodesException = new ICC_Ecodes_Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
                Mage::logException($ecodesException);
                $this->addError(
                    'There was an error registering with the Queue. Some eCodes may not recieve serial assignments.'
                );
                return false;
            }
        }
    }

    /**
     * @param $customerId
     * @return ICC_Ecodes_Model_Mysql4_Downloadable_Collection
     */
    public function filterByCustomerId($customerId)
    {
        $this->getSelect()
            ->joinLeft(
                array('sales_order_item' => 'sales_flat_order_item'),
                'main_table.order_item_id = sales_order_item.item_id',
                array('item_id', 'order_id')
            )
            ->joinLeft(
                array('sales_order' => 'sales_flat_order'),
                'sales_order.entity_id = sales_order_item.order_id',
                array('entity_id', 'customer_id')
            )
            ->where('sales_order.customer_id = ?', $customerId);

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return array
     */
    public function getAssignedSerials(Mage_Sales_Model_Order_Item $orderItem)
    {
        $gpSku = $this->getGpSkuFromProductId($orderItem->getProductId());
        $select = $this->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('serial')
            ->where('enabled = ?', true)
            ->where('order_item_id = ?', $orderItem->getId())
            ->where('gp_sku = ?', $gpSku );

        $assignedSerials = array();
        foreach($this->getConnection()->fetchAll($select) as $serial)
        {
            $assignedSerials[] = $serial['serial'];
        }
        return $assignedSerials;
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getInfo($code)
    {
        if(!isset($this->_info))
        {
            $this->_info = new Varien_Object(array());
        }
        return $this->_info->getData($code);
    }

    public function setInfo($code, $data)
    {
        if(!isset($this->_info))
        {
            $this->_info = new Varien_Object(array());
        }
        return $this->_info->setData($code, $data);
    }

    /**
     * @param $error
     * @return ICC_Ecodes_Model_Mysql4_Downloadable_Collection
     */
    public function addError($error)
    {
        $errors = $this->getInfo('errors');
        $errors[] = $error;
        $this->setInfo('errors', $errors);
        return $this;
    }

    /**
     * @return ICC_Ecodes_Model_Mysql4_Downloadable_Collection
     */
    public function flushErrors()
    {
        unset($this->_errors);
        return $this;
    }
    
    public function getGpSkuFromProductId($product_id)
    {
        $product = Mage::getModel('catalog/product')->load( (int) $product_id );
        return $product->getGpSku();
    }

    
    public function updateGpSkusFromProductId()
    {
        $this->load();
        try{
            $this->getConnection()->beginTransaction();
            foreach($this as $serial)
            {
                $prod_id = $serial->getProductId();
                if((int)$prod_id) {
                    $serial->setGpSku($this->getGpSkuFromProductId($prod_id));
                    $serial->save();
                }
            }  
            $this->getConnection()->commit();
            return sprintf('updated %d rows.', $this->count());
        } catch(Exception $e) {
            $this->getConnection()->rollBack();
            die((string)$e);
        }
    }
    
}
