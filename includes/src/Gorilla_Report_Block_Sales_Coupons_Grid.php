<?php

class Gorilla_Report_Block_Sales_Coupons_Grid extends Mage_Adminhtml_Block_Report_Sales_Coupons_Grid
{
    /**
     * Rows per page for import
     *
     * @var int
     */
    protected $_exportPageSize = 2500;

    public function getResourceCollectionName()
    {
        return 'sales/order_collection';
    }

    protected function _prepareCollection()
    {
        $filterData = $this->getFilterData();
//print_R($filterData);
        if ($filterData->getData('report_type') == 'updated_at_order') {
            $dateFilterField = 'updated_at';
            $dateField = 'updated_at';
        } else {
            $dateField = 'main_table.created_at';
            $dateFilterField = 'created_at';
        }

        $orderStatuses = $filterData->getData('order_statuses');
        $ruleList = $filterData->getData('rules_list');//print_r($ruleList);
        $couponCode = $filterData->getData('coupon_codes');
        $fromDate = $filterData->getData('f_report_date');
        $toDate = $filterData->getData('t_report_date');
        if (is_array($orderStatuses))
        {
            if (count($orderStatuses) == 1 && strpos($orderStatuses[0],',')!== false)
                $filterData->setData('order_statuses', explode(',',$orderStatuses[0]));
        }

        $storeIds = $this->_getStoreIds();
        $resource = Mage::getSingleton('core/resource');

        $from = Mage::getModel('core/date')->gmtDate(null,$filterData['from']);
        $to = Mage::getModel('core/date')->gmtDate(null,$filterData['to']);
        $collection = Mage::getResourceModel($this->getResourceCollectionName());
                if ($ruleName != '') {
                    $collection->addFieldToFilter('name', $ruleName);
                }
                
                
                
                $collection->addFieldToFilter('coupon_code', array('notnull' => true));
                if ($couponCode != '') {
                    $collection->addFieldToFilter('coupon_code', array(
                        array('like' => '%'.$couponCode.'%'), //spaces on each side
                        array('like' => '%'.$couponCode), //space before and ends with $needle
                        array('like' => $couponCode.'%') // starts with needle and space after
                    ));
                }
                if ($fromDate != '') {
                    $collection->addFieldToFilter('from_date', array(
                            'from' => $fromDate,
                            'date' => true
                    ));
                }
                if ($toDate != '') {
                    $collection->addFieldToFilter('to_date', array(
                            'to' => $toDate,
                            'date' => true
                    ));
                }

                  $collection ->addFieldToFilter($dateField, array(
                        'from' => $from,
                        'to' => $to,
                        'date' => true
                    ))
                    ->addFieldToFilter('store_id', array('in' => $storeIds))
                    ->addFieldToSelect(
                        array('store_id','created_at','updated_at','status','state','coupon_code','customer_email','increment_id',
                                'customer_firstname','customer_lastname','base_currency_code')
                    )
                    ->addOrder($dateField, 'ASC');

        $collection->addExpressionFieldToSelect('order_subtotal', '(COALESCE(main_table.subtotal,0) - COALESCE(main_table.subtotal_canceled,0))');
        $collection->addExpressionFieldToSelect('order_discount', '(COALESCE(main_table.discount_amount,0) - COALESCE(main_table.discount_canceled,0))');
        $collection->addExpressionFieldToSelect('order_total', '(COALESCE(main_table.subtotal,0) + COALESCE(main_table.discount_amount,0))');
        $collection->addExpressionFieldToSelect('real_subtotal', '(COALESCE(main_table.subtotal_invoiced,0) - COALESCE(main_table.subtotal_refunded,0))');
        $collection->addExpressionFieldToSelect('real_discount', '(COALESCE(main_table.discount_invoiced,0) - COALESCE(main_table.discount_refunded,0))');

        if ($filterData->getData('order_statuses'))
            $collection->addFieldToFilter('status', array('in' => $filterData->getData('order_statuses')));
            $rulesList = Mage::helper('icc_couponsystem')->getUniqRulesNamesList($dateField, $from, $to);
            $rulesFilterSqlParts = array();
            $ruleList = explode(',', $ruleList[0]);
            foreach ($ruleList as $ruleId) {
                if (!isset($rulesList[$ruleId])) {
                    continue;
                }
                $ruleName = $rulesList[$ruleId];
                $rulesFilterSqlParts[] = "name = '".$ruleName."'";
            }
        $collection->getSelect()
            ->joinLeft(array('sc' => $resource->getTableName('salesrule/coupon')), 'main_table.coupon_code=sc.code',null)
            ->joinLeft(array('sr' => $resource->getTableName('salesrule/rule')), 'sr.rule_id=sc.rule_id',array('rule_name' => 'sr.name', 'rule_from_date' => 'sr.from_date', 'rule_to_date' => 'sr.to_date'))
            ->joinLeft(array('soa' => $resource->getTableName('sales/order_address')), 'soa.parent_id=main_table.entity_id AND soa.address_type="billing"',array('ship_city' => 'soa.city', 'ship_zip' => 'soa.postcode','ship_region_id' => 'soa.region_id'));
          //   ->joinLeft(array('soa' => $resource->getTableName('sales/order_address')), 'soa.parent_id=main_table.entity_id AND soa.address_type="shipping"',array('ship_city' => 'soa.city', 'ship_zip' => 'soa.postcode','ship_region_id' => 'soa.region_id'));
//die;
        
        if (!empty($rulesFilterSqlParts)) {
            $collection->getSelect()->where(implode($rulesFilterSqlParts, ' OR '));
        }
       // echo $collection->getSelect(); 
        $totals = array('order_subtotal' => 0, 'order_discount' => 0, 'order_total' => 0, 'real_subtotal' => 0, 'real_discount' => 0);

        $intervals = Mage::helper('reports')->getIntervals($filterData->getData('from', null),$filterData->getData('to', null),$filterData->getData('period_type'));
;
        $periodTemplate = $this->getPeriodTemplate($filterData->getData('period_type'));

        $states = Mage::getModel('directory/country')->load('US')->getRegions();
        $statesArray = array();
        foreach ($states as $state)
        {
            $statesArray[$state->getData('region_id')] = $state->getData('code');
        }

        $dates = array();
        $skipped = array();
        $orderCollection = new Varien_Data_Collection();
        foreach ($collection as $order)
        {
            if ($order->getData('ship_region_id') && array_key_exists($order->getData('ship_region_id'), $statesArray))
                $order->setData('ship_region', $statesArray[$order->getData('ship_region_id')]);

            foreach (array_keys($totals) as $one)
            {
                $totals[$one] += abs($order->getData($one));
            }

            $date = Mage::getModel('core/date')->date($periodTemplate, strtotime($order->getData($dateFilterField)));
            foreach ($intervals as $interval)
            {
                if ($interval == $date)
                {
                    if (!in_array($interval, $dates))
                    {
                        $order->setPeriod($interval);
                        $skipped = $this->getSkippedIntervals($intervals, end($dates), $interval);
                        $dates[] = $interval;
                    }

                    break;
                }
            }

            if ($filterData->getData('show_empty_rows', false) && count($skipped))
            {
                foreach ($skipped as $single)
                {
                    $item = Mage::getModel('adminhtml/report_item');
                    $item->setPeriod($single);
                    $item->setIsEmpty();
                    $orderCollection->addItem($item);
                }
                $skipped = array();
            }

            $orderCollection->addItem($order);
        }
        unset($collection);

        if ($filterData->getData('show_empty_rows', false) && end($dates) != end($intervals))
        {
            foreach ($intervals as $interval)
            {
                if ($interval > end($dates))
                {
                    $item = Mage::getModel('adminhtml/report_item');
                    $item->setPeriod($interval);
                    $item->setIsEmpty();
                    $orderCollection->addItem($item);
                }
            }
        }

        $this->setTotals(new Varien_Object($totals));
        $this->setCollection($orderCollection);

        if ($this->_isExport)
            return $this;


        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

    public function getPeriodTemplate($period)
    {
        switch ($period) {
            case 'month' :
                $dateFormat = 'Y-m';
                break;
            case 'year' :
                $dateFormat = 'Y';
                break;
            default:
                $dateFormat = 'Y-m-d';
                break;
        }

        return $dateFormat;
    }

    public function getSkippedIntervals($intervals, $last, $next)
    {
        $result = array();

        foreach ($intervals as $interval)
        {
            if ($interval > $last && $interval < $next)
                $result[] = $interval;
        }

        return $result;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('period', array(
            'header'            => Mage::helper('gorilla_report')->__('Period'),
            'index'             => 'period',
            'width'             => 100,
            'sortable'          => false,
            'period_type'       => $this->getPeriodType(),
            'renderer'          => 'adminhtml/report_sales_grid_column_renderer_date',
            'totals_label'      => Mage::helper('gorilla_report')->__('Total'),
            'subtotals_label'   => Mage::helper('gorilla_report')->__('Subtotal'),
            'html_decorators' => array('nobr'),
        ));

        $this->addColumn('increment_id', array(
            'header'    => Mage::helper('gorilla_report')->__('Order #'),
            'sortable'  => false,
            'index'     => 'increment_id'
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('gorilla_report')->__('Order Date'),
            'sortable'  => false,
            'index'     => 'created_at'
        ));
        $this->addColumn('customer_lastname', array(
            'header'    => Mage::helper('gorilla_report')->__('Customer Name'),
            'sortable'  => false,
            'index'     => 'customer_lastname',
            'renderer'          => 'gorilla_report/adminhtml_customer_renderer_fullname'
        ));

        $this->addColumn('customer_email', array(
            'header'    => Mage::helper('gorilla_report')->__('Customer Email'),
            'index'     => 'customer_email',
            'sortable'          => false,
        ));

        //Coupon Name

        $this->addColumn('rule_name', array(
            'header'    => Mage::helper('gorilla_report')->__('Rule Name'),
            'index'     => 'rule_name',
            'sortable'          => false,
        ));

        $this->addColumn('coupon_code', array(
            'header'    => Mage::helper('gorilla_report')->__('Coupon Code'),
            'index'     => 'coupon_code',
            'sortable'          => false,
        ));

        $this->addColumn('rule_from_date', array(
            'header'    => Mage::helper('gorilla_report')->__('Start Date'),
            'index'     => 'rule_from_date',
            'sortable'          => false,
        ));

        $this->addColumn('rule_to_date', array(
            'header'    => Mage::helper('gorilla_report')->__('Date Expired'),
            'index'     => 'rule_to_date',
            'sortable'          => false,
        ));

        $this->addColumn('ship_city', array(
            'header'    => Mage::helper('gorilla_report')->__('City'),
            'index'     => 'ship_city',
            'sortable'          => false,
        ));

        $this->addColumn('ship_region', array(
            'header'    => Mage::helper('gorilla_report')->__('State'),
            'index'     => 'ship_region',
            'sortable'          => false,
        ));

        $this->addColumn('ship_zip', array(
            'header'    => Mage::helper('gorilla_report')->__('Zip'),
            'index'     => 'ship_zip',
            'sortable'          => false,
        ));

        $currencyCode = $this->getCurrentCurrencyCode();

        $this->addColumn('order_subtotal', array(
            'header'        => Mage::helper('gorilla_report')->__('Order Subtotal'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'order_subtotal',
            'sortable'          => false,
        ));

        $this->addColumn('order_discount', array(
            'header'        => Mage::helper('gorilla_report')->__('Order Discount'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'order_discount',
            'sortable'          => false,
            'renderer'          => 'gorilla_report/adminhtml_order_renderer_discount'
        ));

        $this->addColumn('order_total', array(
            'header'        => Mage::helper('gorilla_report')->__('Order Total'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'order_total',
            'sortable'          => false,
        ));

        $this->addColumn('real_subtotal', array(
            'header'        => Mage::helper('gorilla_report')->__('Subtotal'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'real_subtotal',
            'sortable'          => false,
        ));

        $this->addColumn('real_discount', array(
            'header'        => Mage::helper('gorilla_report')->__('Discount'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'real_discount',
            'sortable'          => false,
            'renderer'          => 'gorilla_report/adminhtml_order_renderer_discount'
        ));

        $this->addColumn('real_total', array(
            'header'        => Mage::helper('gorilla_report')->__('Total'),
            'type'          => 'currency',
            'currency_code' => $currencyCode,
            'index'         => 'order_total',
            'sortable'          => false,
        ));

        $this->addExportType('*/*/exportCouponsCsv', Mage::helper('adminhtml')->__('CSV'));
        $this->addExportType('*/*/exportCouponsExcel', Mage::helper('adminhtml')->__('Excel XML'));

        return Mage_Adminhtml_Block_Report_Grid_Abstract::_prepareColumns();
    }
    
     /**
     * Add price rule filter
     *
     * @param Mage_Reports_Model_Resource_Report_Collection_Abstract $collection
     * @param Varien_Object $filterData
     * @return Mage_Adminhtml_Block_Report_Grid_Abstract
     */
    protected function _addCustomFilter($collection, $filterData)
    {
        if ($filterData->getPriceRuleType()) {
            $rulesList = $filterData->getData('rules_list');
            if (isset($rulesList[0])) {
                $rulesIds = explode(',', $rulesList[0]);
                $collection->addRuleFilter($rulesIds);
            }
        }

        return parent::_addCustomFilter($filterData, $collection);
    }
}