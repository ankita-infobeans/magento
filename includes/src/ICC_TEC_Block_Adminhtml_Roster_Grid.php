<?php

class ICC_TEC_Block_Adminhtml_Roster_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function _construct()
    {           
        parent::_construct();
        $this->setId('RosterGrid');
        $this->setUseAjax(false); // set to false because of troubles removing the root node
        $this->setDefaultSort('main_table.created_at');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    
    protected function _prepareCollection()
    {
        $filter = new Zend_Filter_Int();
        $product_id = $filter->filter($this->getRequest()->getParam('id'));
        $roster_collection = Mage::getModel('icc_tec/roster')
                ->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('main_table.product_id', $product_id)
                // ->addFieldToFilter( '( ord_item.qty_canceled + ord_item.qty_refunded )', array( 'lt' => array( 'attribute' => 'ord_item.qty_ordered' )) )
                ->join(
                    array(
                        'ord_item' => 'sales/order_item' // $this->getTable('sales/adminhtml_order_item')
                    ), 
                    'main_table.order_item_id = ord_item.item_id'
                )
                ->join(
                    array(
                        'order' => 'sales/order'
                    ), 
                    'ord_item.order_id = order.entity_id'
                    , array('order.entity_id AS order_entity_id', 'order.customer_email AS customer_email')
                )
                ->join(
                    array(
                        'address' => 'sales/order_address'
                    ), 
                    'order.billing_address_id = address.entity_id AND address.address_type = \'billing\' ',
                    array('address.telephone AS telephone', 'address.email AS address_email', 'CONCAT(address.street, \' \', address.city, \', \', address.region, \' \', address.postcode, \' \', address.country_id) as full_address')        
                )
                ;
        $roster_collection->getSelect()->where('( ord_item.qty_canceled + ord_item.qty_refunded ) < ord_item.qty_ordered' );
        //echo $roster_collection->getSelect();
        //die();
        $this->setCollection($roster_collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => $this->__('Roster ID'),
            'width'     => '35px',
            'sortable'  => true,
            'index'     => 'entity_id',
            'filter_index' => 'main_table.entity_id',
            'type'      => 'number',
        ));
        
        $this->addColumn('fullname', array(
            'header'    => $this->__('Registrant Name'),
            'width'     => '50px',
            'index'     => 'fullname',
            'filter_index' => 'main_table.fullname',
            'type'      => 'string',
            'sortable'  => true,
        ));
        
        $this->addColumn('address_email', array(
            'header'    => $this->__('Billing Email'),
            'width'     => '50px',
//            'index'     => 'address_email',
//            'filter_index' => 'address.email',
            'index'     => 'customer_email',
            'filter_index' => 'order.customer_email',
            'type'      => 'string',
            'sortable'  => true,
        ));
        
         $this->addColumn('full_address', array(
            'header'    => $this->__('Address'),
            'width'     => '50px',
            'index'     => 'full_address',
            'filter_index' => 'CONCAT(address.street, \' \', address.city, \', \', address.region, \' \', address.postcode, \' \', address.country_id)',
            'type'      => 'string',
            'sortable'  => true,
        ));

        $this->addColumn('telephone', array(
            'header'    => $this->__('Phone'),
            'width'     => '50px',
            'index'     => 'telephone',
            'filter_index' => 'address.telephone',
            'type'      => 'string',
            'sortable'  => true,
        ));

        
        $this->addColumn('special_interest', array(
            'header'    => $this->__('Special Needs'),
            'width'     => '100px',
            'index'     => 'special_interest',
            'type'      => 'string',
            'sortable'  => true,
        ));
        
        $this->addColumn('job_title', array(
            'header'    => $this->__('Job Title'),
            'width'     => '100px',
            'index'     => 'job_title',
            'type'      => 'string',
            'sortable'  => true,
        ));

        $this->addColumn('order_entity_id', array(
            'header'    => $this->__('Order ID'),
            'width'     => '40px',
            'index'     => 'order_entity_id',
            'filter_index' => 'order.entity_id',
            'type'      => 'number',
            'sortable'  => true,
        ));

        $this->addColumn('payment_amount', array(
            'header'    => $this->__('Amount of Payment'),
            'width'     => '40px',
            'index'     => 'payment_amount',
            'type'      => 'number',
            'sortable'  => true,
        ));
        
        $this->addExportType('*/*/exportSearchCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportSearchExcel', Mage::helper('reports')->__('Excel XML'));
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/sales_order/view', array('order_id' => $row->getOrderId()));
    }
    public function getGridUrl()
    {
        return $this->getUrl('*/*/roster/grid', array('_current'=> true));
    }
}