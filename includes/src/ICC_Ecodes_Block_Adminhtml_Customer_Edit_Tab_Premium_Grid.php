<?php

class ICC_Ecodes_Block_Adminhtml_Customer_Edit_Tab_Premium_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function _construct()
    {   //Mage::log('grid block called', null, 'premium-grid-block.log');
        parent::_construct();
        $this->setId('PremiumGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('reg_id');
        $this->setDefaultDir('desc');
        $this->setDefaultLimit(100);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
       $customer_id = (int)$this->getRequest()->getParam('id'); 
       $collection = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
       $collection->addFieldToFilter('main_table.customer_id', array('eq' => $customer_id)); 
       $this->setCollection($collection);
       return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('product_name', array(
            'header'    => $this->__('Product Name'),            
            'sortable' => true,
            'index'     => 'product_name',
            'type'  => 'string',
        ));
        $this->addColumn('sku', array(
            'header'    => $this->__('Sku'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'sku',
            'type'  => 'string',
        ));
        $this->addColumn('seats_total', array(
            'header'    => $this->__('Purchased Use(s)'),
            'width'     => '30px',
            'sortable' => true,
            'index'     => 'seats_total',
            'type'  => 'string',
        ));
        $this->addColumn('registered_count', array(
            'header'    => $this->__('Total Use(s)'),
            'width'     => '30px',
            'sortable' => true,
            'index'     => 'registered_count',
            'type'  => 'string',
        ));
        $this->addColumn('expiration', array(
            'header'    => $this->__('Expiration Date'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'expiration',
            'type'  => 'date',
        ));
        /*
        $this->addColumn('parent_customer_id', array(
            'header'    => $this->__('Owner status'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'parent_customer_id',
            'type'  => 'string',
        ));
        */
        
        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '70px',
            'options' => array(0 => 'Disabled', 1=>'Enabled', 2 =>'Expiry' ),
        ));
        
       
        $this->addColumn('action',
            array(
                'header'    =>  $this->__('Action'),
                'width'     => '100px',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(                     
                    array(
                        'caption'   => $this->__('View Details'),
                        'url'       =>  array('base' => '*/premiumcustomers/index'),
                        'field'     => 'id',
                       // 'confirm'   => 'Are you sure you would like to manage the users this order item?',
                    ),                    
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
    }
    // /*
    public function getGridUrl()
    {
    	//die;
        return $this->getUrl('*/premium/customers', array('_current'=> true));
    }
    /* */
    // /*
    public function getRowUrl($row)
    {
        //return $this->getUrl('*/premium/edit', array('id'=>$row->getId()));
    }
    /* */
}
