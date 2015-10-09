<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */
class Amasty_Xcoupon_Block_Adminhtml_Report extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('amxcouponReport');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('amxcoupon/report_collection') 
            ->addFields();
            
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (version_compare(Mage::getVersion(), '1.4.1.0') >= 0){
            $rules = Mage::getModel('salesrule/rule')->getResourceCollection();
            $rules->getSelect()->from(null, array('id'=>'main_table.rule_id'));
            $rules = $rules->toOptionHash();
                
            $this->addColumn('applied_rule_ids', array(
                'header' => Mage::helper('amxcoupon')->__('Rule Name'),
                'index' => 'applied_rule_ids',
                'filter'    => 'amxcoupon/adminhtml_report_filter_rule',
                'type'      => 'options',
                'options'   => $rules,            
            )); 
        } 
        else {
            $this->addColumn('rule_name', array(
                'header' => Mage::helper('amxcoupon')->__('Rule Name'),
                'index' => 'rule_name',
            ));  
        }       
        
        $this->addColumn('coupon_code', array(
            'header' => Mage::helper('sales')->__('Coupon'),
            'index' => 'coupon_code',
        ));  
        
        $this->addColumn('tracking', array(
            'header' => Mage::helper('sales')->__('Tracking Number'),
            'index' => 'tracking',
        ));         
              
        
        $this->addColumn('real_order_id', array(
            'header'=> Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'increment_id',
        ));


        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '150px',
        ));


        $this->addColumn('grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type'  => 'currency',
            'currency' => 'order_currency_code',
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        )); 
        
        $this->addExportType('*/*/exportCsv', Mage::helper('salesrule')->__('CSV'));
        return parent::_prepareColumns();
    }
     
    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getEntityId()));
        }
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/index', array('_current'=>true));
    } 
      
}